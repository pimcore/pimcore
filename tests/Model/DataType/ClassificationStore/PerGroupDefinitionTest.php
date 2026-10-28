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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The enriched layout: what the batched relation lookup produces, and the field definition each
 * group gets for a key it shares with another group.
 *
 * @group model.datatype.classificationstore
 */
class PerGroupDefinitionTest extends AbstractBatchedRelationTest
{
    private const GROUP_COUNT = 10;

    private const CHANGED_TITLE = 'changed title';

    private const STORED_SORTER = 7;

    private const CHANGED_SORTER = 42;

    public function testEnrichedLayoutKeepsGroupsKeyOrderAndSkipsDisabledKeys(): void
    {
        $expectedGroups = [];
        $activeGroups = [];

        for ($groupIndex = 0; $groupIndex < self::GROUP_COUNT; $groupIndex++) {
            $group = $this->createGroup('ordered' . $groupIndex);
            $activeGroups[$group->getId()] = true;

            // assigned with a descending sorter, so that (sorter, id) is not the creation order
            $keys = [];
            foreach ([2, 1, 0] as $position => $sorter) {
                $keyConfig = $this->createKey('ordered' . $groupIndex . '_' . $position);
                $this->assignKey($group, $keyConfig, $sorter);
                $keys[$sorter] = $keyConfig;
            }

            // a relation whose key is disabled is not part of the layout
            $disabledKey = $this->createKey('disabled' . $groupIndex, false);
            $this->assignKey($group, $disabledKey, 3);

            ksort($keys);
            $expectedGroups[$group->getId()] = [
                'name' => $group->getName(),
                'id' => $group->getId(),
                'description' => $group->getDescription(),
                'keys' => array_map(
                    static fn (Classificationstore\KeyConfig $keyConfig) => [
                        'id' => $keyConfig->getId(),
                        'name' => $keyConfig->getName(),
                        'description' => $keyConfig->getDescription(),
                    ],
                    array_values($keys)
                ),
            ];
        }

        $definitions = $this->enrich($this->createObject('ordered', $activeGroups));

        $this->assertSame(array_keys($expectedGroups), array_keys($definitions));

        foreach ($expectedGroups as $groupId => $expectedGroup) {
            $this->assertSame($expectedGroup['name'], $definitions[$groupId]['name']);
            $this->assertSame($expectedGroup['id'], $definitions[$groupId]['id']);
            $this->assertSame($expectedGroup['description'], $definitions[$groupId]['description']);

            $actualKeys = array_map(
                static fn (array $key) => [
                    'id' => $key['id'],
                    'name' => $key['name'],
                    'description' => $key['description'],
                ],
                $definitions[$groupId]['keys']
            );

            $this->assertSame($expectedGroup['keys'], $actualKeys, 'keys stay ordered by (sorter, id)');
        }
    }

    public function testAKeyInTwoGroupsGetsItsOwnDefinitionPerGroup(): void
    {
        $groupA = $this->createGroup('mandatoryA');
        $groupB = $this->createGroup('mandatoryB');
        $keyConfig = $this->createKey('shared');

        $this->assignKey($groupA, $keyConfig, 0, true);
        $this->assignKey($groupB, $keyConfig, 0, false);

        // the mandatory relation of group A is satisfied, so that the object itself still saves
        $definitions = $this->enrich($this->createObject('shared', [
            $groupA->getId() => true,
            $groupB->getId() => true,
        ], [[$groupA->getId(), $keyConfig->getId(), 'a value']]));

        $definitionA = $definitions[$groupA->getId()]['keys'][0]['definition'];
        $definitionB = $definitions[$groupB->getId()]['keys'][0]['definition'];

        $this->assertNotSame($definitionA, $definitionB, 'each group gets a field definition of its own');
        $this->assertTrue($definitionA->getMandatory(), 'the key is mandatory in the group that says so');
        $this->assertFalse($definitionB->getMandatory(), 'and not in the group that does not');

        // the description a tooltip falls back to is a property of the key, not of the assignment
        $expectedTooltip = $keyConfig->getName() . ' - ' . $keyConfig->getDescription();
        $this->assertSame($expectedTooltip, $definitionA->getTooltip());
        $this->assertSame($expectedTooltip, $definitionB->getTooltip());
    }

    /**
     * Drives the real services_resetter rather than asserting the tag line in services.yaml: with
     * `autoconfigure: true` the ResetInterface implementation is tagged either way, so the explicit
     * tag - which mirrors the one on PermissionCache - is not what makes this pass.
     */
    public function testTheCacheIsClearedAtTheKernelResetBoundary(): void
    {
        $group = $this->createGroup('reset');
        $keyConfig = $this->createKey('reset');
        $this->assignKey($group, $keyConfig, self::STORED_SORTER);

        $container = Pimcore::getContainer();

        $this->assertTrue($container->has(Classificationstore\KeyGroupRelationCache::class));

        $cache = $container->get(Classificationstore\KeyGroupRelationCache::class);
        $this->assertInstanceOf(ResetInterface::class, $cache);

        $cache->reset();
        $this->assertSame(self::STORED_SORTER, $this->readSorter($cache, $group, $keyConfig));

        // changed behind the cache's back, so that none of the invalidation hooks fires
        Db::get()->executeStatement(
            'UPDATE `' . Classificationstore\KeyGroupRelation\Dao::TABLE_NAME_RELATIONS
            . '` SET `sorter` = ? WHERE `groupId` = ? AND `keyId` = ?',
            [self::CHANGED_SORTER, $group->getId(), $keyConfig->getId()]
        );

        $this->assertSame(
            self::STORED_SORTER,
            $this->readSorter($cache, $group, $keyConfig),
            'precondition: the held relations answer without reading again'
        );

        $container->get('services_resetter')->reset();

        $this->assertSame(
            self::CHANGED_SORTER,
            $this->readSorter($cache, $group, $keyConfig),
            'the kernel.reset tag must empty the cache'
        );
    }

    public function testAKeyConfigAndARelationSavedMidProcessAreReflected(): void
    {
        $group = $this->createGroup('invalidated');
        $keyConfig = $this->createKey('invalidated');
        $this->assignKey($group, $keyConfig);

        $object = $this->createObject('invalidated', [$group->getId() => true]);

        $definition = $this->enrich($object)[$group->getId()]['keys'][0]['definition'];
        $this->assertFalse($definition->getMandatory());
        $this->assertNotSame(self::CHANGED_TITLE, $definition->getTitle());

        $changedDefinition = new Input();
        $changedDefinition->setName($keyConfig->getName());
        $changedDefinition->setTitle(self::CHANGED_TITLE);
        $keyConfig->setDefinition(json_encode($changedDefinition));
        $keyConfig->save();

        $relation = Classificationstore\KeyGroupRelation::getByGroupAndKeyId($group->getId(), $keyConfig->getId());
        $relation->setMandatory(true);
        $relation->save();

        $definition = $this->enrich($object)[$group->getId()]['keys'][0]['definition'];
        $this->assertTrue($definition->getMandatory(), 'the re-saved relation is read again');
        $this->assertSame(self::CHANGED_TITLE, $definition->getTitle(), 'the edited key config is read again');
    }

    /**
     * @param array<int, bool> $activeGroups
     * @param array<int, array{int, int, mixed}> $values [groupId, keyId, value] set before the save
     */
    private function createObject(string $key, array $activeGroups, array $values = []): Csstore
    {
        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey($key);
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups($activeGroups);

        foreach ($values as [$groupId, $keyId, $value]) {
            $object->getCsstore()->setLocalizedKeyValue($groupId, $keyId, $value);
        }

        $object->save();

        return $object;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function enrich(Csstore $object): array
    {
        $fieldDefinition = $object->getClass()->getFieldDefinition('csstore');
        $fieldDefinition->enrichLayoutDefinition($object);

        return $fieldDefinition->activeGroupDefinitions;
    }

    private function readSorter(
        Classificationstore\KeyGroupRelationCache $cache,
        Classificationstore\GroupConfig $group,
        Classificationstore\KeyConfig $keyConfig
    ): int {
        return $cache->getRelation($group->getId(), $keyConfig->getId())->getSorter();
    }
}
