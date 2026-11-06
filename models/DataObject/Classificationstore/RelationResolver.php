<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\Classificationstore;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Exception;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\DerivedValueInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Exception\DerivedValueNotStorableException;
use Pimcore\Model\DataObject\Exception\UnsupportedEngineFeatureException;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * Turns the stored descriptors of a relation-typed classification store key into its elements, and
 * answers which of them no longer have one without instantiating anything.
 *
 * Two costs are kept apart here, because only one of them is this field's to bound.
 *
 * Deciding whether a descriptor still resolves, and describing the target it names, cost one
 * chunked `IN` query per element type whatever the number of targets, the way
 * {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Relations\ManyToManyRelationTrait::loadRelationSummaries()}
 * does it. That is what the sweep, {@see self::findDangling()}, the dependencies,
 * {@see self::describeDependencies()}, and the edit mode payload, {@see self::summarize()} through
 * {@see RelationEditModeResolver}, are answered from: none of them reads a target.
 *
 * Handing back the elements themselves costs one element load per surviving target, because
 * {@see \Pimcore\Model\Element\Service::getElementById()} is the only way to instantiate one and
 * Pimcore has no bulk form of it - {@see \Pimcore\Model\DataObject\AbstractObject::getById()} is
 * per id, {@see \Pimcore\Model\DataObject\Listing\Dao::load()} loops over it, and
 * {@see \Pimcore\Model\DataObject\Concrete\Dao::getData()} lets every non-lazy field of the loaded
 * element read its own data. A plain class field pays exactly the same:
 * {@see \Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation::loadData()} loads
 * one object per relation row. So the resolution of a key adds no statement of its own per target,
 * and the descriptors of a key that is never read cost nothing at all. That price is paid only
 * where the caller asked for the elements - reading a key - and not by the edit mode payload,
 * which needs the columns of a target rather than the target.
 *
 * Which descriptors a value carries is read off the shape {@see \Pimcore\Normalizer\NormalizerInterface::normalize()}
 * writes rather than off a list of type names, so a project relation type is answered as well: a
 * single `{type, id}` map for a to-one relation, a list of them for a to-many one, and a list of
 * entries carrying that map under `element` for the advanced pair.
 *
 * @internal
 */
final class RelationResolver
{
    /**
     * The relation types core ships, which are the ones whose constraints and whose stored shape
     * this class reproduces from descriptors. The one list the two questions asked of it are
     * answered from - whether a stored value may be validated without its elements,
     * {@see self::supportsStoredValidity()}, and whether it may be rendered without them,
     * {@see RelationEditModeResolver::RENDERED_TYPES} - so that a sixth core type cannot be learnt
     * by one of them and not the other.
     *
     * Matched exactly rather than by inheritance everywhere it is used: a type a project adds is
     * resolved into its elements and asked its own methods.
     */
    public const CORE_RELATION_TYPES = [
        ManyToOneRelation::class,
        ManyToManyRelation::class,
        ManyToManyObjectRelation::class,
        AdvancedManyToManyRelation::class,
        AdvancedManyToManyObjectRelation::class,
    ];

    /**
     * The element tables a descriptor can address, and the id column of each. In the order
     * {@see \Pimcore\Model\Element\Service::getElementType()} names them.
     */
    private const TABLES = [
        'object' => 'objects',
        'asset' => 'assets',
        'document' => 'documents',
    ];

    /**
     * The columns a target is described by without being instantiated, per element table. They are
     * the ones {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Relations\ManyToManyRelationTrait::loadRelationSummaries()}
     * reads, plus the two dates {@see \Pimcore\Model\Element\Service::gridElementData()} carries.
     */
    private const SUMMARY_COLUMNS = [
        'object' => 'SELECT `id`, `parentId`, `path`, `key` AS `filename`, `type`, `className`,'
            . ' `published`, `creationDate`, `modificationDate` FROM `objects`',
        'asset' => 'SELECT `id`, `parentId`, `path`, `filename`, `type`, NULL AS `className`,'
            . ' 1 AS `published`, `creationDate`, `modificationDate` FROM `assets`',
        'document' => 'SELECT `id`, `parentId`, `path`, `key` AS `filename`, `type`,'
            . ' NULL AS `className`, `published`, `creationDate`, `modificationDate` FROM `documents`',
    ];

    private const ELEMENT_MEMBER = 'element';

    /**
     * Between the type and the id of a descriptor, in the key the lookups are held under.
     */
    private const KEY_SEPARATOR = ':';

    /**
     * Ids per statement, as {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Relations\ManyToManyRelationTrait::loadRelationSummaries()}
     * chunks them.
     */
    private const LOOKUP_CHUNK_SIZE = 1000;

    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * Whether the values of this key are relations, and therefore stored as descriptors until they
     * are read.
     */
    public function isRelation(?Data $fieldDefinition): bool
    {
        return $fieldDefinition instanceof AbstractRelations;
    }

    /**
     * Whether the value of this key is computed from other elements rather than stored on the
     * owning object, which a classification store has nowhere to put.
     */
    public function isDerived(?Data $fieldDefinition): bool
    {
        return $fieldDefinition instanceof DerivedValueInterface && $fieldDefinition->isValueDerived();
    }

    /**
     * Refuses a key whose value the store cannot hold, before anything is written.
     *
     * @param bool $relationsSupported what the engine answers for {@see EngineFeature::Relations}
     *
     * @throws DerivedValueNotStorableException for a key whose value is computed from other elements
     * @throws UnsupportedEngineFeatureException for a relation key on an engine without relations
     */
    public function assertStorable(?Data $fieldDefinition, int $groupId, int $keyId, bool $relationsSupported): void
    {
        if ($fieldDefinition === null) {
            return;
        }

        if ($this->isDerived($fieldDefinition)) {
            throw new DerivedValueNotStorableException($groupId, $keyId, $fieldDefinition->getFieldtype());
        }

        if (!$relationsSupported && $this->isRelation($fieldDefinition)) {
            throw new UnsupportedEngineFeatureException(
                EngineFeature::Relations,
                $groupId,
                $keyId,
                $fieldDefinition->getFieldtype()
            );
        }
    }

    /**
     * The elements of one stored value, with the descriptors whose target is gone left out.
     *
     * No lookup of its own is made: every relation type's `denormalize()` already omits a descriptor
     * it cannot instantiate, so asking the database which ids are still there would read the same
     * rows the element loads below read anyway.
     *
     * @param Classificationstore|null $owner the store the value belongs to, which the items of an
     *                                        advanced relation are bound to {@see self::bindToOwner()}
     *
     * @throws Exception
     */
    public function resolve(
        Data $fieldDefinition,
        mixed $normalizedValue,
        ?Concrete $object,
        ?Classificationstore $owner = null
    ): ResolvedRelation {
        $resolved = $this->denormalize($fieldDefinition, $normalizedValue, $object);

        $this->bindToOwner($resolved->getData(), $owner);

        return $resolved;
    }

    /**
     * Binds the items of an advanced relation to the store that holds them, so that editing one of
     * their column values in place reaches the store's dirty map.
     *
     * Both advanced denormalizers hand their items the Concrete object as the owner and the key
     * definition's name as the owner field name, which is the right pair for a class field and the
     * wrong one here: {@see \Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait::markMeDirty()}
     * would then mark a field of the object that does not exist, while
     * {@see \Pimcore\Model\DataObject\Classificationstore\Engine\DocumentEngine::save()} asks the
     * store whether anything about it is dirty and returns without writing when nothing is. An
     * in-place column edit would be discarded with no error at all.
     *
     * `_self` is the field name the store marks dirty for every value of its own.
     */
    private function bindToOwner(mixed $data, ?Classificationstore $owner): void
    {
        if ($owner === null || !is_array($data)) {
            return;
        }

        foreach ($data as $item) {
            if ($item instanceof OwnerAwareFieldInterface) {
                $item->_setOwner($owner);
                $item->_setOwnerFieldname('_self');
            }
        }
    }

    /**
     * Checks the stored descriptors of one relation-typed key against the definition as it stands
     * now, without instantiating a target.
     *
     * A key nobody read holds the descriptors the document was loaded from, and a save writes them
     * back unchanged - but the definition they were written under may not be the one in force now.
     * Making a key mandatory, lowering its item limit, taking a class out of its allowed ones or
     * turning multiple assignments off all make a stored value invalid without anything touching
     * it, and the key's own `checkValidity()` cannot be asked: it reads the element of every entry,
     * which is the load this field exists to avoid. So the same four constraints are checked here
     * against what a descriptor says and what {@see self::summarize()} answers for its target - one
     * chunked statement per element type for a whole store, and no element load at all. The messages
     * are the ones the key's own check raises, so a reader cannot tell the two apart.
     *
     * A descriptor whose target is gone is not among the summaries and is skipped: the read drops
     * it and the next save rewrites the document without it, {@see self::resolve()}, and refusing
     * the save instead would leave the object no way back.
     *
     * Every one of the four constraints is therefore measured over the surviving descriptors alone,
     * which is what the key's own check is measured over: it is handed the denormalized value, and
     * `denormalize()` has already left the dead ones out. Counting a dead descriptor towards the
     * item limit, or towards whether a mandatory key holds anything, would make the outcome depend
     * on whether the key had been read - reading it first drops the dead targets, and the same save
     * then passes.
     *
     * @param array<string, array<int, array<string, mixed>>> $summaries {@see self::summarize()},
     *                                                                   over the whole store
     *
     * @throws ValidationException
     */
    public function checkStoredValidity(
        AbstractRelations $fieldDefinition,
        mixed $normalizedValue,
        array $summaries,
        bool $omitMandatoryCheck = false
    ): void {
        $seen = [];

        // duplicates are retained, as every relation type counts the entries of its value rather
        // than the distinct targets among them: a key allowing multiple assignments reaches its
        // limit on repeats of one target
        $surviving = 0;

        foreach ($this->describe($normalizedValue) as $descriptor) {
            $summary = $summaries[$descriptor['type']][$descriptor['id']] ?? null;

            if ($summary === null) {
                continue;
            }

            if (!$this->allowsTarget($fieldDefinition, $summary)) {
                throw new ValidationException(sprintf(
                    'Invalid relation in field `%s` [type: %s]',
                    $fieldDefinition->getName(),
                    $fieldDefinition->getFieldtype()
                ));
            }

            $key = $this->keyOf($descriptor);

            if (isset($seen[$key]) && !$this->allowsMultipleAssignments($fieldDefinition)) {
                throw new ValidationException(
                    'Passing relations multiple times not allowed anymore: ' . $key
                    . ' multiple times in field ' . $fieldDefinition->getName()
                );
            }

            $seen[$key] = true;
            $surviving++;
        }

        // the key's own check raises this before looking at the entries; with nothing surviving
        // none of the two above can have been raised, so the message a reader sees is the same one
        if (!$omitMandatoryCheck && $fieldDefinition->getMandatory() && $surviving === 0) {
            throw new ValidationException('Empty mandatory field [ ' . $fieldDefinition->getName() . ' ]');
        }

        $maxItems = method_exists($fieldDefinition, 'getMaxItems') ? $fieldDefinition->getMaxItems() : null;

        if ($maxItems && $surviving > $maxItems) {
            throw new ValidationException(
                'Number of allowed relations in field `' . $fieldDefinition->getName()
                . '` exceeded (max. ' . $maxItems . ')'
            );
        }
    }

    /**
     * Whether this key is one of the relation types core ships, {@see self::CORE_RELATION_TYPES},
     * which is what a caller has to ask before answering anything about its stored value from the
     * descriptors instead of from the key itself.
     *
     * The types are matched exactly rather than by inheritance, the way
     * {@see RelationEditModeResolver::supports()} matches them. What is reproduced from descriptors
     * is what those five declare: the four constraints of {@see self::checkStoredValidity()} and
     * the dependency rows of {@see self::describeDependencies()}. A type a project adds - whether
     * it extends one of them or replaces one of them in the data type map - may answer any of that
     * differently from an overridden method, so its value is resolved into its elements and its own
     * methods are asked instead. Nothing a save writes or refuses then depends on whether the key
     * had been read.
     */
    public function isCoreRelation(?Data $fieldDefinition): bool
    {
        return $fieldDefinition !== null
            && in_array($fieldDefinition::class, self::CORE_RELATION_TYPES, true);
    }

    private function allowsMultipleAssignments(AbstractRelations $fieldDefinition): bool
    {
        return method_exists($fieldDefinition, 'getAllowMultipleAssignments')
            && $fieldDefinition->getAllowMultipleAssignments();
    }

    /**
     * Whether this key may hold the described target, read off the same declarations the three
     * allow-traits read: {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AllowObjectRelationTrait},
     * {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AllowAssetRelationTrait} and
     * {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AllowDocumentRelationTrait}.
     * They take an element rather than a description of one, which is why the decision is made from
     * the declarations here instead of by calling them - everything they look at is a column of the
     * target, and {@see self::describeTarget()} carries all of it.
     *
     * The two object-only types answer it differently and are asked first, the advanced one before
     * the type it extends. That this agrees with what each of the five types refuses is asserted
     * over a fixture per type rather than here - see `DocumentRelationTest`.
     *
     * @param array<string, mixed> $summary
     */
    private function allowsTarget(AbstractRelations $fieldDefinition, array $summary): bool
    {
        // the advanced object relation names the one class it allows in a field of its own and
        // compares the target's class name against it, rather than going through the allow-trait
        if ($fieldDefinition instanceof AdvancedManyToManyObjectRelation) {
            return $summary['type'] === 'object'
                && $summary['subtype'] !== 'folder'
                && (string) $summary['className'] === (string) $fieldDefinition->getAllowedClassId();
        }

        // an object relation requires a Concrete, which a folder is not - whatever its allowed
        // classes say
        if ($fieldDefinition instanceof ManyToManyObjectRelation && $summary['subtype'] === 'folder') {
            return false;
        }

        return match ($summary['type']) {
            'object' => $fieldDefinition->getObjectsAllowed()
                && $this->isAllowedBy(
                    array_column($fieldDefinition->getClasses(), 'classes'),
                    $summary['subtype'] === 'folder' ? 'folder' : (string) $summary['className']
                ),
            'asset' => $fieldDefinition->getAssetsAllowed()
                && $this->isAllowedBy(
                    array_column($fieldDefinition->getAssetTypes(), 'assetTypes'),
                    (string) $summary['subtype']
                ),
            'document' => $fieldDefinition->getDocumentsAllowed()
                && $this->isAllowedBy(
                    array_values(array_filter(array_column($fieldDefinition->getDocumentTypes(), 'documentTypes'))),
                    (string) $summary['subtype']
                ),
            default => false,
        };
    }

    /**
     * An empty list of allowed values allows everything, which is how all three allow-traits read
     * one.
     *
     * @param list<mixed> $allowed
     */
    private function isAllowedBy(array $allowed, string $value): bool
    {
        return $allowed === [] || in_array($value, $allowed, true);
    }

    /**
     * Describes the targets of the given descriptors without instantiating one of them, in one
     * chunked statement per element type whatever the number of descriptors.
     *
     * A descriptor whose target no longer exists is simply absent from the result, which is how
     * {@see RelationEditModeResolver} tells a dead descriptor from a live one.
     *
     * @param list<array{type: string, id: int}> $descriptors
     *
     * @return array<string, array<int, array<string, mixed>>> type => id => the target's columns
     *
     * @throws Exception
     */
    public function summarize(array $descriptors): array
    {
        $summaries = [];

        foreach ($this->groupIdsByType($descriptors) as $type => $ids) {
            foreach (array_chunk(array_unique($ids), self::LOOKUP_CHUNK_SIZE) as $chunk) {
                $rows = $this->db->fetchAllAssociative(
                    self::SUMMARY_COLUMNS[$type] . ' WHERE `id` IN (?)',
                    [$chunk],
                    [ArrayParameterType::INTEGER]
                );

                foreach ($rows as $row) {
                    $summaries[$type][(int) $row['id']] = $this->describeTarget($type, $row);
                }
            }
        }

        return $summaries;
    }

    /**
     * One target as the edit mode renders it from, in the shape
     * {@see \Pimcore\Model\DataObject\ClassDefinition\Data\LightweightRelationLoadingInterface::loadRelationSummaries()}
     * documents, with the two dates added.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function describeTarget(string $type, array $row): array
    {
        $id = (int) $row['id'];
        $isObject = $type === 'object';

        return [
            'id' => $id,
            'parentId' => (int) $row['parentId'],
            'type' => $type,
            'subtype' => (string) $row['type'],
            'className' => $isObject && $row['type'] !== 'folder' ? $row['className'] : null,
            'fullpath' => $id === 1 && $isObject ? '/' : $row['path'] . $row['filename'],
            'filename' => (string) $row['filename'],
            'published' => (bool) $row['published'],
            'creationDate' => (int) $row['creationDate'],
            'modificationDate' => (int) $row['modificationDate'],
        ];
    }

    /**
     * The dependencies one stored value registers, read off its descriptors rather than off its
     * elements: a save of an object whose relation keys were never read must not instantiate a
     * target to find out what the object depends on.
     *
     * The shape is the one every relation type's `resolveDependencies()` returns.
     *
     * @return array<string, array{id: int, type: string}>
     */
    public function describeDependencies(mixed $normalizedValue): array
    {
        $dependencies = [];

        foreach ($this->describe($normalizedValue) as $descriptor) {
            // the member order every relation type's resolveDependencies() returns
            $dependencies[$descriptor['type'] . '_' . $descriptor['id']] = [
                'id' => $descriptor['id'],
                'type' => $descriptor['type'],
            ];
        }

        return $dependencies;
    }

    /**
     * The descriptors of this value whose target no longer exists.
     *
     * @return list<array{type: string, id: int}>
     *
     * @throws Exception
     */
    public function findDangling(mixed $normalizedValue): array
    {
        $descriptors = $this->describe($normalizedValue);
        $existing = $this->findExistingTargets($descriptors);

        return $this->dangling($descriptors, $existing);
    }

    /**
     * Whether this stored value still names a target that exists, decided against existence results
     * the caller already holds, {@see self::findExistingTargets()}.
     *
     * No lookup of its own is made, so a store asking it of value after value pays for one round of
     * lookups rather than one round per value. What the answer decides: a value whose every target
     * has been deleted is the key holding no relation, so the fallback language, the default
     * language and the inheritance chain have to be consulted for it exactly as they are for a key
     * that holds nothing at all - otherwise what a save refuses would depend on whether the key had
     * been read, reading it first being what drops the dead targets.
     *
     * @param array<string, true> $existing {@see self::findExistingTargets()}, over at least the
     *                                      descriptors this value names
     */
    public function hasSurvivingTarget(mixed $normalizedValue, array $existing): bool
    {
        foreach ($this->describe($normalizedValue) as $descriptor) {
            if (isset($existing[$this->keyOf($descriptor)])) {
                return true;
            }
        }

        return false;
    }

    /**
     * The same value without the given descriptors: null for a to-one relation, and the surviving
     * entries - reindexed, so that the order they were stored in is kept - for the rest.
     *
     * Nothing is looked up here, so a caller that already asked {@see self::findDangling()} pays for
     * one round of lookups rather than two.
     *
     * @param list<array{type: string, id: int}> $remove
     */
    public function without(mixed $normalizedValue, array $remove): mixed
    {
        return $this->filter($normalizedValue, $this->keys($remove));
    }

    /**
     * @param list<array{type: string, id: int}> $descriptors
     *
     * @return array<string, true> "<type>:<id>" per descriptor
     */
    private function keys(array $descriptors): array
    {
        $keys = [];

        foreach ($descriptors as $descriptor) {
            $keys[$this->keyOf($descriptor)] = true;
        }

        return $keys;
    }

    /**
     * @param list<array{type: string, id: int}> $descriptors
     * @param array<string, true> $existing
     *
     * @return list<array{type: string, id: int}>
     */
    private function dangling(array $descriptors, array $existing): array
    {
        return array_values(array_filter(
            $descriptors,
            fn (array $descriptor): bool => !isset($existing[$this->keyOf($descriptor)])
        ));
    }

    /**
     * The descriptors a normalized value carries.
     *
     * @return list<array{type: string, id: int}>
     */
    public function describe(mixed $normalizedValue): array
    {
        if (!is_array($normalizedValue)) {
            return [];
        }

        $single = $this->describeOne($normalizedValue);
        if ($single !== null) {
            return [$single];
        }

        $descriptors = [];
        foreach ($normalizedValue as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $descriptor = $this->describeOne($entry);
            if ($descriptor !== null) {
                $descriptors[] = $descriptor;
            }
        }

        return $descriptors;
    }

    /**
     * The descriptor one entry of a normalized value carries, and null for an entry that carries
     * none - which is how a caller tells a to-one value, a single descriptor, from a to-many one.
     *
     * @return array{type: string, id: int}|null
     */
    public function describeOne(array $entry): ?array
    {
        $element = $entry[self::ELEMENT_MEMBER] ?? $entry;

        if (!is_array($element) || !isset($element['type'], $element['id'])) {
            return null;
        }

        if (!is_string($element['type']) || !is_scalar($element['id'])) {
            return null;
        }

        return ['type' => $element['type'], 'id' => (int) $element['id']];
    }

    /**
     * Which of the given descriptors still have an element, in one chunked statement per element
     * type rather than one lookup per descriptor.
     *
     * @param list<array{type: string, id: int}> $descriptors
     *
     * @return array<string, true> "<type>:<id>" of every descriptor that resolves
     *
     * @throws Exception
     */
    public function findExistingTargets(array $descriptors): array
    {
        $existing = [];

        foreach ($this->groupIdsByType($descriptors) as $type => $ids) {
            foreach (array_chunk(array_unique($ids), self::LOOKUP_CHUNK_SIZE) as $chunk) {
                $rows = $this->db->fetchFirstColumn(
                    'SELECT `id` FROM ' . $this->db->quoteIdentifier(self::TABLES[$type]) . ' WHERE `id` IN (?)',
                    [$chunk],
                    [ArrayParameterType::INTEGER]
                );

                foreach ($rows as $id) {
                    $existing[$this->keyOf(['type' => $type, 'id' => (int) $id])] = true;
                }
            }
        }

        return $existing;
    }

    /**
     * The ids the given descriptors name, per element type, with a type no element table answers
     * left out.
     *
     * @param list<array{type: string, id: int}> $descriptors
     *
     * @return array<string, list<int>>
     */
    private function groupIdsByType(array $descriptors): array
    {
        $idsByType = [];

        foreach ($descriptors as $descriptor) {
            if (isset(self::TABLES[$descriptor['type']])) {
                $idsByType[$descriptor['type']][] = $descriptor['id'];
            }
        }

        return $idsByType;
    }

    /**
     * @param array{type: string, id: int} $descriptor
     */
    private function keyOf(array $descriptor): string
    {
        return $descriptor['type'] . self::KEY_SEPARATOR . $descriptor['id'];
    }

    /**
     * A value denormalized into its elements, and whether that left any of its descriptors out -
     * which is how {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations::load()}
     * decides the same thing: fewer elements than there were rows means the value the read answers
     * with no longer matches what is stored.
     */
    private function denormalize(
        Data $fieldDefinition,
        mixed $normalizedValue,
        ?Concrete $object
    ): ResolvedRelation {
        if (!$fieldDefinition instanceof NormalizerInterface) {
            // nothing wrote a descriptor for such a key, and nothing can read one back
            return new ResolvedRelation(null, false);
        }

        $data = $fieldDefinition->denormalize($normalizedValue, [
            'object' => $object,
            'fieldDefinition' => $fieldDefinition,
        ]);

        return new ResolvedRelation(
            $data,
            $this->countElements($data) < count($this->describe($normalizedValue))
        );
    }

    /**
     * How many elements a denormalized value holds: a to-one relation answers with the element
     * itself rather than with a list of one.
     */
    private function countElements(mixed $data): int
    {
        if (is_array($data)) {
            return count($data);
        }

        return $data === null ? 0 : 1;
    }

    /**
     * @param array<string, true> $remove "<type>:<id>" of every descriptor to drop
     */
    private function filter(mixed $normalizedValue, array $remove): mixed
    {
        if ($remove === [] || !is_array($normalizedValue)) {
            return $normalizedValue;
        }

        $single = $this->describeOne($normalizedValue);
        if ($single !== null) {
            return isset($remove[$this->keyOf($single)]) ? null : $normalizedValue;
        }

        $entries = [];
        foreach ($normalizedValue as $entry) {
            $descriptor = is_array($entry) ? $this->describeOne($entry) : null;

            if ($descriptor === null || !isset($remove[$this->keyOf($descriptor)])) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }
}
