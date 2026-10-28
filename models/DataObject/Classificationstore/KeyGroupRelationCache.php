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

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Process-scoped cache of the key/group relations of a classification store, keyed by
 * (groupId, keyId). The relations of any number of groups are read with a single statement, so
 * loading, validating and rendering a classification store field does not cost one
 * KeyGroupRelation\Listing per group, per language or per row.
 *
 * The entry is keyed by (groupId, keyId) rather than by key id alone because the same key can be
 * assigned to several groups with its own mandatory flag and description in each of them.
 * {@see self::getMutableFieldDefinition()} therefore builds a new field definition on every call:
 * the layout and validity paths set the tooltip and the mandatory flag on the definition they
 * receive, and one group's assignment must not leak into another's.
 *
 * The cache is cleared at the request and messenger-message boundary via the kernel.reset tag
 * (ResetInterface), and on a key, group or relation change by
 * {@see \Pimcore\Bundle\CoreBundle\EventListener\KeyGroupRelationCacheInvalidationListener} and by
 * {@see KeyGroupRelation\Dao::update()} / {@see KeyGroupRelation\Dao::delete()}, which dispatch no
 * event of their own.
 *
 * This is deliberately separate from {@see Service::$definitionsCache}, which keys the definition
 * by key id only and is left untouched.
 *
 * @internal
 */
final class KeyGroupRelationCache implements ResetInterface
{
    /**
     * @var array<int, array<int, KeyGroupRelation>> groupId => keyId => relation
     */
    private array $relations = [];

    /**
     * @var array<int, array<int, Data|null>> groupId => keyId => shared field definition
     */
    private array $definitions = [];

    /**
     * Reads the relations of all groups that are not held yet with a single statement.
     *
     * @param int[] $groupIds
     */
    public function load(array $groupIds): void
    {
        $missing = array_values(array_diff(array_unique($groupIds), array_keys($this->relations)));
        if (!$missing) {
            return;
        }

        $list = new KeyGroupRelation\Listing();
        $list->setCondition('`groupId` in (' . implode(',', array_fill(0, count($missing), '?')) . ')', $missing);
        $list->setOrderKey(['sorter', 'id']);
        $list->setOrder(['ASC', 'ASC']);

        // a group without any relation is held as an empty set, so it is not looked up again
        $loaded = array_fill_keys($missing, []);
        $keyIds = [];

        foreach ($list->load() as $relation) {
            $loaded[$relation->getGroupId()][$relation->getKeyId()] = $relation;
            $keyIds[] = $relation->getKeyId();
        }

        $this->relations += $loaded;

        // the key configs of the same keys are read in one go as well: the store model resolves
        // one per key and language while it is filled
        DefinitionCache::load($keyIds);
    }

    /**
     * The relations of one group, ordered by (sorter, id), disabled ones included.
     *
     * @return array<int, KeyGroupRelation> keyId => relation
     */
    public function getRelations(int $groupId): array
    {
        $this->load([$groupId]);

        return $this->relations[$groupId];
    }

    public function getRelation(int $groupId, int $keyId): ?KeyGroupRelation
    {
        return $this->getRelations($groupId)[$keyId] ?? null;
    }

    /**
     * The shared field definition of a key within a group, for callers that only read it.
     */
    public function getFieldDefinition(int $groupId, int $keyId): ?Data
    {
        if (array_key_exists($keyId, $this->definitions[$groupId] ?? [])) {
            return $this->definitions[$groupId][$keyId];
        }

        $definition = $this->getMutableFieldDefinition($groupId, $keyId);
        $this->definitions[$groupId][$keyId] = $definition;

        return $definition;
    }

    /**
     * A field definition of its own, for callers that mutate the one they receive.
     */
    public function getMutableFieldDefinition(int $groupId, int $keyId): ?Data
    {
        $relation = $this->getRelation($groupId, $keyId);
        if (!$relation) {
            return null;
        }

        return Service::getFieldDefinitionFromJson(
            json_decode($relation->getDefinition(), true),
            $relation->getType()
        );
    }

    /**
     * Drops the whole group, because a group, key or relation change can also add a key to it or
     * remove one from it, which the held set would otherwise keep answering.
     */
    public function clearGroup(int $groupId): void
    {
        unset($this->relations[$groupId], $this->definitions[$groupId]);
    }

    public function clearKey(int $keyId): void
    {
        foreach ($this->relations as $groupId => $relations) {
            if (isset($relations[$keyId])) {
                $this->clearGroup($groupId);
            }
        }
    }

    public function reset(): void
    {
        $this->relations = [];
        $this->definitions = [];
    }
}
