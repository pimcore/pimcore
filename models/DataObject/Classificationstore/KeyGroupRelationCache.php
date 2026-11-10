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
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Helper\OptionsProviderResolver;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Process-scoped cache of the key/group relations of a classification store, keyed by
 * (groupId, keyId). The relations of any number of groups are read with a single statement, so
 * loading, validating and rendering a classification store field does not cost one
 * KeyGroupRelation\Listing per group, per language or per row.
 *
 * The entry is keyed by (groupId, keyId) rather than by key id alone because the same key can be
 * assigned to several groups with its own mandatory flag and description in each of them.
 * {@see self::getMutableFieldDefinition()} therefore builds a new field definition on every call,
 * from the assignment's {@see KeyGroupRelation::getEffectiveDefinition()}, so that the group's option
 * subset and units apply, and the list of a provider reporting static options is narrowed to that
 * subset as well ({@see self::applyProvidedOptionSubset()}). The layout and validity paths set the
 * tooltip and the mandatory flag on the definition they receive, and one group's assignment must not
 * leak into another's.
 *
 * The cache is cleared at the request and messenger-message boundary via the kernel.reset tag
 * (ResetInterface), and on a key, group or relation change by
 * {@see \Pimcore\Bundle\CoreBundle\EventListener\KeyGroupRelationCacheInvalidationListener} and by
 * {@see KeyGroupRelation\Dao::update()} / {@see KeyGroupRelation\Dao::delete()}, which dispatch no
 * event of their own.
 *
 * This is deliberately separate from {@see Service::$definitionsCache}, which memoizes one shared
 * definition instance per entry and is only dropped at the messenger-message boundary, while this
 * cache hands out a per-call mutable definition and is cleared on a key, group or relation change
 * as well.
 *
 * @internal
 */
final class KeyGroupRelationCache implements ResetInterface
{
    private const OPTIONS = 'options';

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

        // the key configs are read in one go too: the store resolves one per key and language when filled
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
     * The distinct sub groups of a group, each positioned by its first-occurring key under the
     * (sorter, id) order the relations are held in. The relations of disabled keys are left out, so
     * a sub group every key of which is disabled does not appear at all, and one that keeps a single
     * visible key is positioned by that key: enrichLayoutDefinition() skips the disabled keys too,
     * and a sub group exists in a group exactly as long as a key the user can see carries it.
     *
     * @return string[]
     */
    public function getSubGroupOrder(int $groupId): array
    {
        $order = [];

        foreach ($this->getRelations($groupId) as $relation) {
            $subGroup = $relation->getSubGroup();
            if ($subGroup === null || !$relation->isEnabled()) {
                continue;
            }

            // valued as well as keyed: array_keys() would hand back an all digit identifier as an int
            $order[$subGroup] = $subGroup;
        }

        return array_values($order);
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

        $definition = json_decode($relation->getEffectiveDefinition(), true);
        $narrowsProvidedOptions = $this->narrowsProvidedOptions($relation);

        // a key without a list of its own takes them from its provider, and [] would switch validation off
        if ($narrowsProvidedOptions) {
            unset($definition[self::OPTIONS]);
        }

        $fieldDefinition = Service::getFieldDefinitionFromJson($definition, $relation->getType());

        if ($narrowsProvidedOptions && $fieldDefinition !== null) {
            $this->applyProvidedOptionSubset($fieldDefinition, $relation);
        }

        return $fieldDefinition;
    }

    /**
     * Whether the subset of an assignment speaks for a list a provider fills: the key carries no
     * option list of its own, so the list the group narrows is the one the provider hands over.
     */
    private function narrowsProvidedOptions(KeyGroupRelation $relation): bool
    {
        if (!array_key_exists(self::OPTIONS, $relation->getDefinitionOverride() ?? [])) {
            return false;
        }

        return (json_decode($relation->getDefinition(), true)[self::OPTIONS] ?? []) === [];
    }

    /**
     * Narrows the list of a provider that reports static options to the subset of the assignment, so
     * that what the editor offers is also what {@see Select::checkValidity()} accepts - it resolves
     * the provider itself for a definition whose options are unset, and the unfiltered list would be
     * what a save validates against. A provider whose list depends on the object is left to the
     * caller that has one, and a subset that matches nothing is left unset as well, because an empty
     * option list switches option validation off rather than refusing every value.
     */
    private function applyProvidedOptionSubset(Data $definition, KeyGroupRelation $relation): void
    {
        if (!$definition instanceof Select && !$definition instanceof Multiselect) {
            return;
        }

        // the multiselect mode covers a select provider too, the interface of which extends it
        $provider = OptionsProviderResolver::resolveProvider(
            $definition->getOptionsProviderClass(),
            OptionsProviderResolver::MODE_MULTISELECT
        );

        if ($definition->useConfiguredOptions() || !$provider instanceof SelectOptionsProviderInterface) {
            return;
        }

        $context = ['fieldname' => $definition->getName()];
        if (!$provider->hasStaticOptions($context, $definition)) {
            return;
        }

        $options = $relation->applyOptionSubset($provider->getOptions($context, $definition));
        if ($options !== []) {
            $definition->setOptions($options);
        }
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
