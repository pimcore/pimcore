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
use Pimcore\Model\DataObject\ClassDefinition\Data\DerivedValueInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Exception\DerivedValueNotStorableException;
use Pimcore\Model\DataObject\Exception\UnsupportedEngineFeatureException;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * Turns the stored descriptors of a relation-typed classification store key into its elements.
 *
 * It follows {@see \Pimcore\Model\DataObject\ClassDefinition\Data\LightweightRelationLoadingInterface}
 * in shape rather than implementing it: that interface sources its rows from `object_relations_*`
 * through `loadRelationRows()`, which a document-stored relation has none of, and its parameter
 * union does not admit a classification store. What is taken from it is the part that matters here -
 * one chunked `IN` query per element type decides which descriptors still resolve, so that the cost
 * of answering a key does not grow with the number of targets it holds.
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
     * The element tables a descriptor can address, and the id column of each. In the order
     * {@see \Pimcore\Model\Element\Service::getElementType()} names them.
     */
    private const TABLES = [
        'object' => 'objects',
        'asset' => 'assets',
        'document' => 'documents',
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
     * @throws Exception
     */
    public function resolve(Data $fieldDefinition, mixed $normalizedValue, ?Concrete $object): ResolvedRelation
    {
        $existing = $this->findExisting($this->describe($normalizedValue));

        return $this->denormalize($fieldDefinition, $normalizedValue, $object, $existing);
    }

    /**
     * The elements of several stored values, resolved against one set of lookups: a caller holding a
     * whole store pays for the element types its descriptors name, not for every key.
     *
     * @param list<RelationRequest> $requests
     *
     * @return list<ResolvedRelation> in the order the requests came in
     *
     * @throws Exception
     */
    public function resolveAll(array $requests, ?Concrete $object): array
    {
        $descriptors = [];
        foreach ($requests as $request) {
            foreach ($this->describe($request->getNormalizedValue()) as $descriptor) {
                $descriptors[] = $descriptor;
            }
        }

        $existing = $this->findExisting($descriptors);

        $resolved = [];
        foreach ($requests as $request) {
            $resolved[] = $this->denormalize(
                $request->getFieldDefinition(),
                $request->getNormalizedValue(),
                $object,
                $existing
            );
        }

        return $resolved;
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
        $existing = $this->findExisting($descriptors);

        return $this->dangling($descriptors, $existing);
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
     * @return array{type: string, id: int}|null
     */
    private function describeOne(array $entry): ?array
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
    private function findExisting(array $descriptors): array
    {
        $idsByType = [];
        foreach ($descriptors as $descriptor) {
            if (isset(self::TABLES[$descriptor['type']])) {
                $idsByType[$descriptor['type']][] = $descriptor['id'];
            }
        }

        $existing = [];

        foreach ($idsByType as $type => $ids) {
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
     * @param array{type: string, id: int} $descriptor
     */
    private function keyOf(array $descriptor): string
    {
        return $descriptor['type'] . self::KEY_SEPARATOR . $descriptor['id'];
    }

    /**
     * @param array<string, true> $existing
     */
    private function denormalize(
        Data $fieldDefinition,
        mixed $normalizedValue,
        ?Concrete $object,
        array $existing
    ): ResolvedRelation {
        if (!$fieldDefinition instanceof NormalizerInterface) {
            // nothing wrote a descriptor for such a key, and nothing can read one back
            return new ResolvedRelation(null, false);
        }

        $dangling = $this->dangling($this->describe($normalizedValue), $existing);

        $data = $fieldDefinition->denormalize($this->without($normalizedValue, $dangling), [
            'object' => $object,
            'fieldDefinition' => $fieldDefinition,
        ]);

        return new ResolvedRelation($data, $dangling !== []);
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
