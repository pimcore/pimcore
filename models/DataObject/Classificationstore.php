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

namespace Pimcore\Model\DataObject;

use Exception;
use Pimcore;
use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data\PreGetDataInterface;
use Pimcore\Model\DataObject\Classificationstore\RelationDescriptor;
use Pimcore\Model\DataObject\Classificationstore\RelationEditModeResolver;
use Pimcore\Model\DataObject\Classificationstore\RelationRequest;
use Pimcore\Model\DataObject\Classificationstore\RelationResolver;
use Pimcore\Model\DataObject\Classificationstore\RenderedRelation;
use Pimcore\Model\Element\DirtyIndicatorInterface;
use Pimcore\Tool;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\Dao createUpdateTable()
 * @method \Pimcore\Model\DataObject\Classificationstore\Dao getDao()
 * @method void delete()
 * @method Classificationstore load()
 * @method void save()
 */
class Classificationstore extends Model\AbstractModel implements DirtyIndicatorInterface
{
    use Model\Element\Traits\DirtyIndicatorTrait;

    /**
     * How many levels of the items are a map: a value is addressed by group, key and language, and
     * what sits under the third of them is the value itself. {@see self::mergeArrays()}
     *
     * @internal
     */
    private const ITEM_LEVELS = 3;

    /**
     * @internal
     *
     */
    protected array $items = [];

    /**
     * @internal
     *
     */
    protected Concrete|Model\Element\ElementDescriptor|null $object = null;

    /**
     * @internal
     */
    protected ?ClassDefinition $class = null;

    /**
     * @internal
     *
     */
    protected string $fieldname;

    /**
     * @internal
     *
     * @var array<int, bool>
     */
    protected array $activeGroups = [];

    /**
     * @internal
     *
     * @var array<int, int>
     */
    protected array $groupCollectionMapping = [];

    /**
     * Counts the changes made to the items, so that an answer read for them is not reused once they
     * are no longer the items it was read for.
     */
    private int $itemsRevision = 0;

    /**
     * The targets of the stored descriptors this store holds that still exist, keyed
     * "<type>:<id>", and the items revision they were read for. Null outside a walk that may hold
     * them, {@see self::getExistingTargets()}.
     *
     * @var array<string, true>|null
     */
    private ?array $existingTargets = null;

    private int $existingTargetsRevision = -1;

    /**
     * The target-existence walk they were read in, which they may not outlive.
     *
     * {@see Classificationstore\RelationResolver::inTargetExistenceWalk()}
     */
    private ?int $existingTargetsWalk = null;

    public function __construct(?array $items = null)
    {
        if ($items) {
            $this->setItems($items);
            $this->markFieldDirty('_self');
        }
    }

    public function addItem(array $item): void
    {
        $this->items[] = $item;
        $this->itemsRevision++;
        $this->bindAssignedItems();
        $this->markFieldDirty('_self');
    }

    public function setItems(array $items): static
    {
        $this->items = $items;
        $this->itemsRevision++;
        $this->bindAssignedItems();
        $this->markFieldDirty('_self');

        return $this;
    }

    /**
     * Binds the advanced relation items of an assigned items tree to this store, so that an
     * in-place column edit reaches its dirty map whichever way the value got here.
     *
     * The tree a store is handed may carry items bound to something else entirely: the one
     * {@see ClassDefinition\Data\Classificationstore::denormalize()} builds has them bound to the
     * Concrete object the denormalizer was given, and the one
     * {@see Model\DataObject\Service::restoreClassificationstoreLanguages()} moves over has them
     * bound to the store they came from. Both would discard a column edit with no error,
     * {@see Classificationstore\RelationResolver::bindItemsToOwner()}.
     */
    private function bindAssignedItems(): void
    {
        $this->getRelationResolver()->bindItemsToOwner($this->items, $this);
    }

    public function getItems(): array
    {
        $doGetInheritedValues = Model\DataObject::doGetInheritedValues();
        if (!$doGetInheritedValues) {
            return $this->items;
        }

        return $this->getAllDataFromField(
            fn ($classificationStore, $fieldsArray) => $this->mergeArrays(
                $fieldsArray,
                $classificationStore->items,
                self::ITEM_LEVELS
            )
        );
    }

    public function setObject(Concrete $object): static
    {
        if ($this->object) {
            if ($this->object->getId() != $object->getId()) {
                $this->markFieldDirty('_self');
            }
        }
        $this->object = $object;

        return $this;
    }

    public function getObject(): ?Concrete
    {
        return $this->object;
    }

    public function setClass(?ClassDefinition $class): static
    {
        $this->class = $class;

        return $this;
    }

    public function getClass(): ?ClassDefinition
    {
        if (!$this->class && $this->getObject()) {
            $this->class = $this->getObject()->getClass();
        }

        return $this->class;
    }

    public function getLanguage(?string $language = null): string
    {
        if ($language) {
            return $language;
        }

        return 'default';
    }

    /**
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setLocalizedKeyValue(int $groupId, int $keyId, mixed $value, ?string $language = null): static
    {
        if (!$groupId) {
            throw new Exception('groupId not valid');
        }

        if (!$keyId) {
            throw new Exception('keyId not valid');
        }

        $language = $this->getLanguage($language);

        // treat value "0" nonempty
        $nonEmpty = is_string($value) ? strlen($value) > 0 : isset($value);

        // Workaround for booleanSelect
        // @TODO Find a better solution for using isEmpty() in all ClassDefintion DataTypes

        $keyConfig = Model\DataObject\Classificationstore\DefinitionCache::get($keyId);
        $dataDefinition = Model\DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

        // set the given group to active groups
        $this->setActiveGroups($this->activeGroups + [$groupId => true]);

        $isRelation = $this->getRelationResolver()->isRelation($dataDefinition);

        if ($isRelation) {
            // the items of an advanced relation are bound to the store that holds them, which is
            // what makes an in-place column edit reach the next save. A value assigned here has to
            // be bound the way a hydrated one is, or editing a column of an item that was assigned
            // rather than read would be discarded with no error,
            // {@see Classificationstore\RelationResolver::bindToOwner()}
            $this->getRelationResolver()->bindToOwner($value, $this);
        }

        // no ResourcePersistenceAwareInterface, so the comparison below cannot judge it
        if ($isRelation && !$this->isFieldDirty('_self')) {
            $this->markFieldDirty('_self');
        }

        if (!$this->isFieldDirty('_self') && $dataDefinition instanceof Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface) {
            if ($this->object) {
                $oldData = $this->items[$groupId][$keyId][$language] ?? null;
                $oldData = $dataDefinition->getDataForResource($oldData, $this->object, ['owner' => $this]);
                if (!$dataDefinition instanceof Model\DataObject\ClassDefinition\Data\Password) {
                    $oldData = serialize($oldData);
                }

                $newData = $dataDefinition->getDataForResource($value, $this->object, ['owner' => $this]);
                if ($dataDefinition instanceof Model\DataObject\ClassDefinition\Data\Password) {
                    $value = $newData;
                } else {
                    $newData = serialize($newData);
                }

                if ($newData != $oldData) {
                    $this->markFieldDirty('_self');
                }
            } else {
                $this->markFieldDirty('_self');
            }
        }

        if ($dataDefinition instanceof Model\DataObject\ClassDefinition\Data\BooleanSelect) {
            $nonEmpty = true;
        }

        if ($dataDefinition instanceof Model\DataObject\ClassDefinition\Data\Multiselect && is_array($value) && empty($value)) {
            $nonEmpty = true;
        }

        $this->itemsRevision++;

        if ($nonEmpty || $value) {
            $this->items[$groupId][$keyId][$language] = $value;
        } elseif (isset($this->items[$groupId][$keyId][$language])) {
            unset($this->items[$groupId][$keyId][$language]);
            if (empty($this->items[$groupId][$keyId])) {
                unset($this->items[$groupId][$keyId]);
                if (empty($this->items[$groupId])) {
                    unset($this->items[$groupId]);
                }
            }
        }

        return $this;
    }

    /**
     * Removes the group with the given id
     *
     */
    public function removeGroupData(int $groupId): void
    {
        unset($this->items[$groupId]);
        $this->itemsRevision++;
    }

    /** Returns an array of
     */
    public function getGroupIdsWithData(): array
    {
        return array_keys($this->items);
    }

    public function getFieldname(): string
    {
        return $this->fieldname;
    }

    public function setFieldname(string $fieldname): void
    {
        $this->fieldname = $fieldname;
    }

    /**
     * @return array<int, bool>
     */
    public function getActiveGroups(): array
    {
        $doGetInheritedValues = Model\DataObject::doGetInheritedValues();
        if (!$doGetInheritedValues) {
            return $this->activeGroups;
        }

        return $this->getAllDataFromField(fn ($classificationStore, $fieldsArray) => $classificationStore->activeGroups + $fieldsArray);
    }

    private function sanitizeActiveGroups(array $activeGroups): array
    {
        $newList = [];

        if ($activeGroups) {
            foreach ($activeGroups as $key => $value) {
                if ($value) {
                    $newList[$key] = true;
                }
            }
        }

        return $newList;
    }

    /**
     * @param array<int, bool> $activeGroups
     */
    public function setActiveGroups(array $activeGroups): void
    {
        $activeGroups = $this->sanitizeActiveGroups($activeGroups);
        $diff1 = array_diff(array_keys($activeGroups), array_keys($this->activeGroups));
        $diff2 = array_diff(array_keys($this->activeGroups), array_keys($activeGroups));
        if ($diff1 || $diff2) {
            $this->markFieldDirty('_self');
        }
        $this->activeGroups = $activeGroups;
    }

    private function getFallbackValue(
        int $groupId,
        int $keyId,
        string $language,
        ClassDefinition\Data $fielddefinition,
        bool $resolveRelations = true
    ): mixed {
        $fallbackLanguages = Tool::getFallbackLanguagesFor($language);
        $data = null;

        foreach ($fallbackLanguages as $l) {
            if (
                array_key_exists($groupId, $this->items)
                && array_key_exists($keyId, $this->items[$groupId])
                && array_key_exists($l, $this->items[$groupId][$keyId])
            ) {
                $data = $this->readItem($groupId, $keyId, $l, $fielddefinition, $resolveRelations);
                if (!$this->isEmptyValue($fielddefinition, $data)) {
                    return $data;
                }
            }
        }

        foreach ($fallbackLanguages as $l) {
            $data = $this->getFallbackValue($groupId, $keyId, $l, $fielddefinition, $resolveRelations);
        }

        return $data;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function getLocalizedKeyValue(
        int $groupId,
        int $keyId,
        ?string $language = 'default',
        bool $ignoreFallbackLanguage = false,
        bool $ignoreDefaultLanguage = false
    ): mixed {
        return $this->selectKeyValue(
            $groupId,
            $keyId,
            $language,
            $ignoreFallbackLanguage,
            $ignoreDefaultLanguage,
            true
        );
    }

    /**
     * The same value {@see self::getLocalizedKeyValue()} selects, with a relation-typed one left as
     * the {@see RelationDescriptor} it is stored as instead of being resolved into its elements.
     *
     * For the callers that have something to do with the stored value rather than with the elements
     * it names - validation, {@see ClassDefinition\Data\Classificationstore::checkValidity()}. They
     * need the value the fallback language, the default language and the inheritance chain select,
     * which is why this goes through the same walk rather than reading
     * {@see self::getItems()}: a coordinate one store holds an empty value at and an ancestor holds
     * a value at is answered here the way a read answers it, and a merge of the items is not.
     *
     * No target is instantiated on the way, so a save of an object whose relation keys were never
     * read costs no element load. An unresolved descriptor counts as present exactly when it names
     * a target, {@see self::isEmptyValue()}, so that an empty one falls through to the fallback
     * language and the inheritance chain as a resolved empty value does.
     *
     * @internal
     *
     * @throws Exception
     */
    public function getStoredKeyValue(
        int $groupId,
        int $keyId,
        ?string $language = 'default',
        bool $ignoreFallbackLanguage = false,
        bool $ignoreDefaultLanguage = false
    ): mixed {
        return $this->selectKeyValue(
            $groupId,
            $keyId,
            $language,
            $ignoreFallbackLanguage,
            $ignoreDefaultLanguage,
            false
        );
    }

    /**
     * @param bool $resolveRelations whether a relation-typed value is resolved into its elements,
     *                               which is what the two callers above differ in
     *
     * @throws Exception
     */
    private function selectKeyValue(
        int $groupId,
        int $keyId,
        ?string $language,
        bool $ignoreFallbackLanguage,
        bool $ignoreDefaultLanguage,
        bool $resolveRelations
    ): mixed {
        $language = $this->getLanguage($language);

        $keyConfig = Model\DataObject\Classificationstore\DefinitionCache::get($keyId);
        if ($keyConfig->getType() == 'calculatedValue') {
            $data = new Model\DataObject\Data\CalculatedValue($this->getFieldname());
            $childDef = Model\DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
            $data->setContextualData('classificationstore', $this->getFieldname(), null, $language, $groupId, $keyId, $childDef);
            $data = Model\DataObject\Service::getCalculatedFieldValueForEditMode($this->getObject(), [], $data);

            return $data;
        }

        $fieldDefinition = Model\DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
        $data = null;

        if (array_key_exists($groupId, $this->items) && array_key_exists($keyId, $this->items[$groupId])
                && array_key_exists($language, $this->items[$groupId][$keyId])
        ) {
            $data = $this->readItem($groupId, $keyId, $language, $fieldDefinition, $resolveRelations);
        }

        // check for fallback value
        if ($this->isEmptyValue($fieldDefinition, $data) && !$ignoreFallbackLanguage && self::doGetFallbackValues()) {
            $data = $this->getFallbackValue($groupId, $keyId, $language, $fieldDefinition, $resolveRelations);
        }

        if ($this->isEmptyValue($fieldDefinition, $data) && !$ignoreDefaultLanguage && $language != 'default') {
            $data = $this->readItem($groupId, $keyId, 'default', $fieldDefinition, $resolveRelations);
        }

        // check for inherited value
        $doGetInheritedValues = Model\DataObject::doGetInheritedValues();
        if ($this->isEmptyValue($fieldDefinition, $data) && $doGetInheritedValues) {
            $object = $this->getObject();
            $class = $object->getClass();
            $allowInherit = $class->getAllowInherit();

            if ($allowInherit) {
                if ($object->getParent() instanceof AbstractObject) {
                    $parent = $object->getParent();
                    while ($parent && $parent->getType() == AbstractObject::OBJECT_TYPE_FOLDER) {
                        $parent = $parent->getParent();
                    }

                    if ($parent && ($parent->getType() == AbstractObject::OBJECT_TYPE_OBJECT || $parent->getType() == AbstractObject::OBJECT_TYPE_VARIANT)) {
                        /** @var Concrete $parent */
                        if ($parent->getClassId() == $object->getClassId()) {
                            $getter = 'get' . ucfirst($this->fieldname);
                            $classificationStore = $parent->$getter();
                            if ($classificationStore instanceof Classificationstore) {
                                if ($classificationStore->object->getId() != $this->object->getId()) {
                                    // the ancestor is asked the same way it was asked itself, so
                                    // that a store overriding either entry point keeps answering
                                    // for the values it holds
                                    $data = $resolveRelations
                                        ? $classificationStore->getLocalizedKeyValue($groupId, $keyId, $language, false)
                                        : $classificationStore->getStoredKeyValue($groupId, $keyId, $language, false);
                                }
                            }
                        }
                    }
                }
            }
        }

        // an unresolved descriptor is not a value this hook can be given: for a store container it
        // hands the value straight back after filtering the unpublished elements out of it,
        // {@see ClassDefinition\Data\ManyToOneRelation::preGetData()}, and a descriptor holds no
        // element to filter. The value the caller gets is the stored one, which is what the
        // non-inheriting branch of checkValidity() has always read.
        if ($fieldDefinition instanceof PreGetDataInterface && !$data instanceof RelationDescriptor) {
            $data = $fieldDefinition->preGetData($this, [
                'data' => $data,
                'language' => $language,
                'name' => $groupId . '-' . $keyId,
            ]);
        }

        return $data;
    }

    /**
     * Whether this value is the key's empty one, which decides whether the fallback language, the
     * default language and the inheritance chain are consulted for it.
     *
     * An unresolved relation value is asked of its surviving descriptors rather than of the key:
     * every relation type reads emptiness off the value itself - `empty($data)`,
     * {@see ClassDefinition\Data::isEmpty()} - and a {@see RelationDescriptor} is an object, so a
     * key storing no relation at all would otherwise count as holding one.
     *
     * A descriptor whose targets have all been deleted is the key holding nothing, the same answer
     * the elements would have given: reading the key drops a dead target,
     * {@see self::readItem()}, so counting one as present here would make the value this selects -
     * and with it what a save refuses - depend on whether the key had been read. Deciding it costs
     * no element load and one round of lookups for the whole store,
     * {@see self::getExistingTargets()}.
     */
    private function isEmptyValue(ClassDefinition\Data $fieldDefinition, mixed $data): bool
    {
        if ($data instanceof RelationDescriptor) {
            return !$this->getRelationResolver()->hasSurvivingTarget(
                $data->getValue(),
                $this->getExistingTargets()
            );
        }

        return $fieldDefinition->isEmpty($data);
    }

    /**
     * The targets of every stored descriptor this store holds that still exist, keyed "<type>:<id>".
     *
     * Read for the whole store at once, in one chunked statement per element type: the paths that
     * ask whether a stored relation is empty ask for key after key and language after language -
     * the validity check of a store with fifty relation keys asks once per key and language - and a
     * lookup per question would be a statement per key where the store means to cost one round.
     * Nothing is instantiated for it either.
     *
     * The answer is held for the walk that asked for it and no longer,
     * {@see Classificationstore\RelationResolver::inTargetExistenceWalk()}. Deleting a target
     * changes nothing about this store, so neither the items nor their revision can tell that a
     * held answer has gone stale: a second validation of the same instance would still be told a
     * dead descriptor names a target, select it over the live value the default language or an
     * ancestor holds, and be refused by the summaries of that same validation, which are read
     * fresh. Outside a walk nothing is held at all - a caller asking one question at a time is
     * answered about the elements as they stand now.
     *
     * @return array<string, true>
     *
     * @throws Exception
     */
    private function getExistingTargets(): array
    {
        $resolver = $this->getRelationResolver();
        $walk = $resolver->currentTargetExistenceWalk();

        if (
            $walk !== null
            && $this->existingTargets !== null
            && $this->existingTargetsWalk === $walk
            && $this->existingTargetsRevision === $this->itemsRevision
        ) {
            return $this->existingTargets;
        }

        $descriptors = [];

        foreach ($this->items as $keys) {
            foreach ($keys as $languages) {
                foreach ($languages as $value) {
                    if ($value instanceof RelationDescriptor) {
                        foreach ($resolver->describe($value->getValue()) as $descriptor) {
                            $descriptors[] = $descriptor;
                        }
                    }
                }
            }
        }

        // a store holding no descriptor pays for no statement: the lookup is per element type of
        // the descriptors handed over, and there are none
        $existingTargets = $resolver->findExistingTargets($descriptors);

        $this->existingTargets = $walk === null ? null : $existingTargets;
        $this->existingTargetsRevision = $this->itemsRevision;
        $this->existingTargetsWalk = $walk;

        return $existingTargets;
    }

    public static function doGetFallbackValues(): bool
    {
        return true;
    }

    /**
     * One stored value, with a relation-typed one resolved into its elements on the way out.
     *
     * The resolved elements take the descriptor's place in the items, so that reading the same key
     * twice resolves once; the store is not marked dirty for that, as nothing about what is stored
     * changed. A descriptor whose target is gone does mark it dirty - the value the read answers
     * with no longer matches the stored document, and the next ordinary save is what rewrites it.
     *
     * A key that is not a relation any more keeps its stored descriptor: the definition in force
     * cannot read one - it is not a normalizer, so the resolution answers null,
     * {@see Classificationstore\RelationResolver::resolve()} - and memoizing that null would put it
     * where the stored value was and have the next ordinary save write it over the document. The
     * descriptor is handed back as it stands instead, which is what the callers that can be given
     * one expect: the validity check leaves such a key alone,
     * {@see ClassDefinition\Data\Classificationstore::checkValidity()}, and the hook of a
     * definition that cannot read it is not asked either, {@see self::selectKeyValue()}.
     *
     * @param bool $resolveRelations false for a caller that wants the stored descriptor itself,
     *                               {@see self::getStoredKeyValue()}; nothing is resolved and
     *                               nothing is memoized then
     *
     * @throws Exception
     */
    private function readItem(
        int $groupId,
        int $keyId,
        string $language,
        ?ClassDefinition\Data $fieldDefinition,
        bool $resolveRelations = true
    ): mixed {
        $data = $this->items[$groupId][$keyId][$language] ?? null;

        if ($fieldDefinition === null || !$resolveRelations || !$data instanceof RelationDescriptor) {
            return $data;
        }

        if (!$this->getRelationResolver()->isRelation($fieldDefinition)) {
            return $data;
        }

        $resolved = $this->getRelationResolver()->resolve(
            $fieldDefinition,
            $data->getValue(),
            $this->getObject(),
            $this
        );
        $this->items[$groupId][$keyId][$language] = $resolved->getData();

        if ($resolved->hasDroppedTargets()) {
            $this->markFieldDirty('_self');
        }

        return $resolved->getData();
    }

    /**
     * The edit mode value of every relation-typed value the store holds, against one set of lookups
     * and without instantiating a target.
     *
     * For the reader that takes the items as they are instead of going through
     * {@see self::getLocalizedKeyValue()} - the edit mode payload. It needs the columns of a target
     * rather than the target, {@see RelationEditModeResolver}, so what comes back is the rendered
     * value rather than the elements, and the cost of the whole store is one chunked statement per
     * element type instead of one element load per target.
     *
     * What that reader sees is merged across the inheritance chain, {@see self::getItems()}, so
     * every store on that chain renders its own descriptors - a value an ancestor contributes would
     * otherwise reach the reader as the descriptor the ancestor still holds. A coordinate a store
     * holds an already resolved value at contributes `null`, so that the ancestor's rendering does
     * not take the place of the value the nearer store actually holds.
     *
     * The whole chain is rendered here, against one round of lookups, and the answer carries one
     * merged map per store on it - the one that store's own reader needs, with the stores nearer to
     * the object left out of it. The reader walks the chain itself, one level per ancestor whose
     * values it is still missing, and asking each level for its own rendering would render the
     * ancestors of level two again at level two, those of level three again at level three: a chain
     * of H levels would cost H(H+1)/2 rounds of lookups to show one editor tab. So the reader asks
     * once, {@see ClassDefinition\Data\Classificationstore::getDataForEditmode()}, and takes the map
     * of the object each of its levels is at.
     *
     * The chain is walked whatever {@see Model\DataObject::doGetInheritedValues()} says, because the
     * reader walks it on the class allowing inheritance alone: with inherited values off, each level
     * shows its own items, and each level still has to have its descriptors rendered.
     *
     * @internal
     *
     * @return array<int, array<int, array<int, array<string, RenderedRelation|null>>>>
     *         objectId => groupId => keyId => language
     *
     * @throws Exception
     */
    public function renderRelationsForEditMode(): array
    {
        $chain = $this->collectInheritanceChain();
        $own = $this->renderChainForEditMode($chain);

        $byObject = [];
        $merged = [];

        // folded from the far end of the chain, so that every store's map holds its own rendering
        // over its ancestors' - the way the items it reads are merged, {@see self::mergeArrays()}
        for ($level = count($chain) - 1; $level >= 0; $level--) {
            $merged = $this->mergeArrays($own[$level], $merged, self::ITEM_LEVELS);
            $byObject[(int) $chain[$level]->getObject()?->getId()] = $merged;
        }

        return $byObject;
    }

    /**
     * The stores an edit mode reader walks from this one, nearest first.
     *
     * @return list<Classificationstore>
     */
    private function collectInheritanceChain(): array
    {
        $chain = [$this];
        $object = $this->getObject();

        while (!is_null($object) && ($parent = Service::hasInheritableParentObject($object)) !== null) {
            $store = $parent->{'get' . ucfirst($this->getFieldname())}();

            if (!$store instanceof self) {
                break;
            }

            $chain[] = $store;
            $object = $parent;
        }

        return $chain;
    }

    /**
     * The rendering of each store on the chain, its own descriptors only, against one round of
     * lookups for all of them together: the requests of every store go into one call, so a chain
     * costs what a single store costs.
     *
     * @param list<Classificationstore> $chain
     *
     * @return array<int, array<int, array<int, array<string, RenderedRelation|null>>>> by level
     *
     * @throws Exception
     */
    private function renderChainForEditMode(array $chain): array
    {
        $renderer = $this->getRelationEditModeResolver();
        $rendered = [];
        $renderable = [];

        foreach ($chain as $level => $store) {
            $rendered[$level] = $store->collectResolvedCoordinates();

            foreach ($store->collectRelationRequests() as $request) {
                if ($renderer->supports($request->getFieldDefinition())) {
                    $renderable[] = [$level, $request];

                    continue;
                }

                // a project relation type keeps the resolution it had: the elements go into the
                // items, and the reader renders them itself
                $store->resolveItem($request);
                $rendered[$level][$request->getGroupId()][$request->getKeyId()][$request->getLanguage()] = null;
            }
        }

        return $this->applyRenderedRelations($chain, $rendered, $renderable, $renderer);
    }

    /**
     * The coordinates of this store whose value is not a descriptor, mapped to nothing to render.
     *
     * They are what keeps the rendering an ancestor contributes for a coordinate from standing in
     * for the value this store holds there - a key read earlier in the request holds its elements
     * rather than its descriptor, and those are the value the payload has to show. Deciding this
     * needs no key config, so a key that holds no descriptor still pays for none.
     *
     * @return array<int, array<int, array<string, null>>>
     */
    private function collectResolvedCoordinates(): array
    {
        $coordinates = [];

        foreach ($this->items as $groupId => $keys) {
            foreach ($keys as $keyId => $languages) {
                foreach ($languages as $language => $value) {
                    if (!$value instanceof RelationDescriptor) {
                        $coordinates[(int) $groupId][(int) $keyId][(string) $language] = null;
                    }
                }
            }
        }

        return $coordinates;
    }

    /**
     * @param list<Classificationstore> $chain
     * @param array<int, array<int, array<int, array<string, RenderedRelation|null>>>> $rendered by level
     * @param list<array{0: int, 1: RelationRequest}> $renderable the level each request belongs to,
     *                                                           and the request
     *
     * @return array<int, array<int, array<int, array<string, RenderedRelation|null>>>> by level
     *
     * @throws Exception
     */
    private function applyRenderedRelations(
        array $chain,
        array $rendered,
        array $renderable,
        RelationEditModeResolver $renderer
    ): array {
        if ($renderable === []) {
            return $rendered;
        }

        $relations = $renderer->renderAll(array_column($renderable, 1));

        foreach ($renderable as $index => [$level, $request]) {
            if ($relations[$index]->hasDroppedTargets()) {
                // a descriptor whose target is gone has to come out of the stored value, and the
                // per-relation column values of an advanced key are addressed by the position of
                // the entry they belong to - so the key is resolved into its elements, which is
                // what the save rebuilds both the document and those rows from. Rewriting the
                // descriptor instead would reindex the document and leave the rows behind it.
                // Only a key that actually lost a target pays for that resolution.
                $chain[$level]->resolveItem($request);
                $rendered[$level][$request->getGroupId()][$request->getKeyId()][$request->getLanguage()] = null;

                continue;
            }

            $rendered[$level][$request->getGroupId()][$request->getKeyId()][$request->getLanguage()]
                = $relations[$index];
        }

        return $rendered;
    }

    /**
     * @throws Exception
     */
    private function resolveItem(RelationRequest $request): void
    {
        $resolved = $this->getRelationResolver()->resolve(
            $request->getFieldDefinition(),
            $request->getNormalizedValue(),
            $this->getObject(),
            $this
        );

        $this->items[$request->getGroupId()][$request->getKeyId()][$request->getLanguage()]
            = $resolved->getData();

        if ($resolved->hasDroppedTargets()) {
            $this->markFieldDirty('_self');
        }
    }

    /**
     * One request per stored descriptor this store holds.
     *
     * @return list<RelationRequest>
     *
     * @throws Exception
     */
    private function collectRelationRequests(): array
    {
        $requests = [];

        foreach ($this->items as $groupId => $keys) {
            foreach ($keys as $keyId => $languages) {
                $descriptors = array_filter(
                    $languages,
                    static fn (mixed $value): bool => $value instanceof RelationDescriptor
                );

                // only a key holding a descriptor pays for its key config here
                $fieldDefinition = $descriptors === [] ? null : $this->resolveKeyDefinition((int) $keyId);

                if ($fieldDefinition === null) {
                    continue;
                }

                foreach ($descriptors as $language => $descriptor) {
                    $requests[] = new RelationRequest(
                        (int) $groupId,
                        (int) $keyId,
                        (string) $language,
                        $fieldDefinition,
                        $descriptor->getValue()
                    );
                }
            }
        }

        return $requests;
    }

    /**
     * @throws Exception
     */
    private function resolveKeyDefinition(int $keyId): ?ClassDefinition\Data
    {
        $keyConfig = Classificationstore\DefinitionCache::get($keyId);

        return $keyConfig === null
            ? null
            : Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
    }

    private function getRelationResolver(): RelationResolver
    {
        return Pimcore::getContainer()->get(RelationResolver::class);
    }

    private function getRelationEditModeResolver(): RelationEditModeResolver
    {
        return Pimcore::getContainer()->get(RelationEditModeResolver::class);
    }

    /**
     * @return array<int, int>
     */
    public function getGroupCollectionMappings(): array
    {
        $doGetInheritedValues = Model\DataObject::doGetInheritedValues();
        if (!$doGetInheritedValues) {
            return $this->groupCollectionMapping;
        }

        return $this->getAllDataFromField(fn ($classificationStore, $fieldsArray) => $fieldsArray + $classificationStore->groupCollectionMapping);
    }

    /**
     * @param array<int, int> $groupCollectionMapping
     */
    public function setGroupCollectionMappings(array $groupCollectionMapping): void
    {
        $this->groupCollectionMapping = $groupCollectionMapping;
    }

    public function setGroupCollectionMapping(?int $groupId = null, ?int $collectionId = null): void
    {
        if ($groupId && $collectionId) {
            $this->groupCollectionMapping[$groupId] = $collectionId;
        }
    }

    public function getGroupCollectionMapping(int $groupId): ?int
    {
        return $this->getGroupCollectionMappings()[$groupId] ?? null;
    }

    /**
     * @return Model\DataObject\Classificationstore\Group[]
     */
    public function getGroups(): array
    {
        return Classificationstore::getActiveGroupsWithConfig($this);
    }

    private function getAllDataFromField(callable $mergeFunction): array
    {
        $fieldsArray = $mergeFunction($this, []);
        $object = $this->getObject();
        while (!is_null($object) && ($parent = Service::hasInheritableParentObject($object)) !== null) {
            $fieldsArray = $mergeFunction($parent->{'get' . ucfirst($this->getFieldname())}(), $fieldsArray);

            $object = $parent;
        }

        return $fieldsArray;
    }

    /**
     * @return Model\DataObject\Classificationstore\Group[]
     */
    private static function getActiveGroupsWithConfig(Classificationstore $classificationStore): array
    {
        $groups = [];
        $activeGroups = $classificationStore->getActiveGroups();
        foreach (array_keys($activeGroups) as $groupId) {
            if ($groupConfig = $classificationStore->getGroupConfigById($groupId)) {
                $groups[] = $classificationStore->createGroup($classificationStore, $groupConfig);
            }
        }

        return $groups;
    }

    public function createGroup(
        Classificationstore $classificationstore,
        Classificationstore\GroupConfig $groupConfig
    ): Model\DataObject\Classificationstore\Group {
        return new Model\DataObject\Classificationstore\Group($classificationstore, $groupConfig);
    }

    private function getGroupConfigById(int $groupId): ?Classificationstore\GroupConfig
    {
        return Classificationstore\GroupConfig::getById($groupId);
    }

    /**
     * Merges the nearer store's map over an ancestor's, down to the given number of levels.
     *
     * A value is a leaf, never a map to merge into: the items are addressed by group, key and
     * language, and what sits under those three belongs to one store as a whole. A relation-typed
     * value makes that unmistakable - one store can hold its elements while an ancestor still holds
     * the descriptor it was loaded from, and merging a list of elements into a
     * {@see RelationDescriptor} would ask for the descriptor as an array. It is no less true of
     * every other value a key holds as an array: a child's multiselect is its own value, and the
     * entries an ancestor happens to have beyond it are not part of it.
     *
     * The level count is what keeps that rule at the value and nowhere above it, and the type of
     * both sides is checked as well, so a map one level shallower than it should be cannot make a
     * merge of two arrays out of a value and a map.
     */
    private function mergeArrays(array $a1, array $a2, int $levels = PHP_INT_MAX): array
    {
        foreach ($a1 as $key => $value) {
            if ($levels > 1 && is_array($value) && is_array($a2[$key] ?? null)) {
                $a2[$key] = $this->mergeArrays($value, $a2[$key], $levels - 1);
            } else {
                $a2[$key] = $value;
            }
        }

        return $a2;
    }
}
