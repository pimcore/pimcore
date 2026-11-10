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

use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\MultiSelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Helper\OptionsProviderResolver;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;

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
    /**
     * @var array<int, array<int, KeyGroupRelation>> groupId => keyId => relation
     */
    private array $relations = [];

    /**
     * @var array<int, array<int, Data|null>> groupId => keyId => shared field definition
     */
    private array $definitions = [];

    /**
     * The list a provider fills, narrowed to the subset of the assignment, memoized so that
     * resolving the provider costs the same whether a definition is built once or - as the validity
     * walk and the layout enrichment do - once per key per call. A null entry is an assignment
     * nothing narrows, {@see self::narrowProvidedOptions()}.
     *
     * @var array<int, array<int, array<int, array<string, mixed>>|null>> groupId => keyId => options
     */
    private array $providedOptions = [];

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

        $fieldDefinition = Service::getFieldDefinitionFromJson($relation->getEffectiveDefinitionData(), $relation->getType());

        if ($fieldDefinition !== null && $relation->narrowsProvidedOptions()) {
            $this->applyProvidedOptionSubset($fieldDefinition, $relation);
        }

        return $fieldDefinition;
    }

    /**
     * The definition that answers for the value: the inner type of a `multiValue` or an
     * `encryptedField`, which is where an override is merged and what validates the value.
     */
    public static function getAnsweringDefinition(?Data $definition): ?Data
    {
        $seen = [];
        while ($definition !== null) {
            $definition = Service::getValueDefinition($definition);
            if (!$definition instanceof Data\EncryptedField || isset($seen[spl_object_id($definition)])) {
                break;
            }
            $seen[spl_object_id($definition)] = true;
            $delegate = $definition->getDelegate();
            if (!$delegate instanceof Data) {
                break;
            }
            $definition = $delegate;
        }

        return $definition;
    }

    /**
     * Narrows the list of a provider that reports static options to the subset of the assignment, so
     * that what the editor offers is also what {@see Select::checkValidity()} accepts - it resolves
     * the provider itself for a definition whose options are unset, and the unfiltered list would be
     * what a save validates against. A provider whose list depends on the object is left to the
     * caller that has one.
     *
     * A subset that matches nothing is set as the empty list it is, the same as the layout path sets
     * it ({@see \Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore::enrichLayoutDefinition()}),
     * rather than being left unset for the provider's full list to be validated against. That the
     * two `checkValidity()` implementations read an empty list as nothing to validate rather than as
     * nothing to accept is answered where the values are,
     * {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore::refusesEveryValue()}.
     */
    private function applyProvidedOptionSubset(Data $definition, KeyGroupRelation $relation): void
    {
        $definition = self::getAnsweringDefinition($definition);
        if (!$definition instanceof Select && !$definition instanceof Multiselect) {
            return;
        }

        $groupId = $relation->getGroupId();
        $keyId = $relation->getKeyId();

        if (!array_key_exists($keyId, $this->providedOptions[$groupId] ?? [])) {
            $this->providedOptions[$groupId][$keyId] = $this->narrowProvidedOptions($definition, $relation);
        }

        $options = $this->providedOptions[$groupId][$keyId];
        if ($options !== null) {
            $definition->setOptions($options);
            $definition->setDefaultValue(KeyGroupRelation::reconcileDefaultValue($definition->getDefaultValue(), $options));
        }
    }

    /**
     * The list of a provider reporting static options, narrowed to the subset of the assignment -
     * null where nothing narrows it: the key configures its options itself, no provider of the
     * key's own mode resolves, the list depends on the object, or the provider cannot answer.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function narrowProvidedOptions(Select|Multiselect $definition, KeyGroupRelation $relation): ?array
    {
        // each type resolves its provider the way its own enrichment does
        // ({@see \Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectionProviderTrait::doEnrichDefinitionDefinition()}),
        // so the list this narrows is the list the editor is offered: the multiselect mode accepts a
        // provider implementing only the deprecated MultiSelectOptionsProviderInterface, and the
        // select mode does not
        $provider = OptionsProviderResolver::resolveProvider(
            $definition->getOptionsProviderClass(),
            $definition instanceof Select
                ? OptionsProviderResolver::MODE_SELECT
                : OptionsProviderResolver::MODE_MULTISELECT
        );

        if ($definition->useConfiguredOptions() || !$provider instanceof MultiSelectOptionsProviderInterface) {
            return null;
        }

        // the context the editor's enrichment hands the provider, as far as a lookup without an
        // object can: only a provider reporting static options is asked, so the list does not vary
        // with the object, and the purpose is the one the layout is enriched for
        $context = [
            'fieldname' => $definition->getName(),
            'purpose' => 'layout',
            'ownerType' => 'classificationstore',
            'keyId' => $relation->getKeyId(),
            'groupId' => $relation->getGroupId(),
            'keyDefinition' => $definition,
        ];

        try {
            if (!$provider->hasStaticOptions($context, $definition)) {
                return null;
            }

            return $relation->applyOptionSubset($provider->getOptions($context, $definition));
        } catch (Throwable $throwable) {
            // a provider that cannot answer narrows nothing. A definition is built on the load, the
            // save, the dependency walk and the search index walk, none of which has an editor in
            // front of it, and the configuration a provider reads can be gone - the core providers
            // throw on that. Tolerated and logged, the way a key config that no longer resolves
            // already is, {@see Engine\EavEngine::load()}
            Logger::error(
                'Could not narrow the options of key ' . $relation->getKeyId()
                . ' in group ' . $relation->getGroupId() . ' to the subset of its assignment: '
                . $throwable->getMessage()
            );

            return null;
        }
    }

    /**
     * Drops the whole group, because a group, key or relation change can also add a key to it or
     * remove one from it, which the held set would otherwise keep answering.
     */
    public function clearGroup(int $groupId): void
    {
        unset($this->relations[$groupId], $this->definitions[$groupId], $this->providedOptions[$groupId]);
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
        $this->providedOptions = [];
    }
}
