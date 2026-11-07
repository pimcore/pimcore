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

namespace Pimcore\Tests\Model\Inheritance;

use Pimcore;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Data\MultiValue;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class ClassificationstoreTest
 *
 * @package Pimcore\Tests\Model\Inheritance
 *
 * @group model.inheritance.classificationstore
 */
class ClassificationstoreTest extends ModelTestCase
{
    private const DOCUMENT_ENGINE = 'document';

    private const MULTI_VALUE_GROUP = 'groupMultiValue';

    private const MULTI_VALUE_KEY = 'mvHeight';

    private const METRIC = 'metric';

    private const IMPERIAL = 'imperial';

    private bool $storeWasPutOnTheDocumentEngine = false;

    private ?string $storeEngineBeforeTheTest = null;

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        Pimcore::setAdminMode();
    }

    public function tearDown(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');

        if ($store) {
            Classificationstore\KeyConfig::getByName(self::MULTI_VALUE_KEY, $store->getId())?->delete();
            Classificationstore\GroupConfig::getByName(self::MULTI_VALUE_GROUP, $store->getId())?->delete();
        }

        if ($this->storeWasPutOnTheDocumentEngine && $store) {
            $store->setEngine($this->storeEngineBeforeTheTest);
            $store->save();
            $this->storeWasPutOnTheDocumentEngine = false;
        }

        Classificationstore\DefinitionCache::clear(null);
        Classificationstore\Service::clearDefinitionsCache();
        Pimcore::getContainer()->get(Classificationstore\KeyGroupRelationCache::class)->reset();

        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        $class = ClassDefinition::getByName('inheritance');

        if ($class) {
            $store = Classificationstore\StoreConfig::getByName('teststore');
            if (!$store) {
                $store = new Classificationstore\StoreConfig();
                $store->setName('teststore');
                $store->save();
            }

            $this->configureStore($store);
        }
    }

    protected function configureStore(Classificationstore\StoreConfig $store): void
    {
        $group = Classificationstore\GroupConfig::getByName('group1', $store->getId());
        if (!$group) {
            // create group
            $group = new Classificationstore\GroupConfig();
            $group->setStoreId($store->getId());
            $group->setName('group1');
            $group->save();
        }

        $key1 = Classificationstore\KeyConfig::getByName('field1', $store->getId());
        if (!$key1) {
            //create field1
            $key1 = new Classificationstore\KeyConfig();
            $key1->setDefinition(json_encode(new ClassDefinition\Data\Input()));
            $key1->setStoreId($store->getId());
            $key1->setName('field1');
            $key1->setDescription('Input Field 1');
            $key1->setEnabled(true);
            $key1->setType('input');
            $key1->save();
        }

        $key2 = Classificationstore\KeyConfig::getByName('field2', $store->getId());
        if (!$key2) {
            //create field2
            $key2 = new Classificationstore\KeyConfig();
            $key2->setDefinition(json_encode(new ClassDefinition\Data\Input()));
            $key2->setStoreId($store->getId());
            $key2->setName('field2');
            $key2->setDescription('Input Field 2');
            $key2->setEnabled(true);
            $key2->setType('input');
            $key2->save();
        }

        $keygroup1 = Classificationstore\KeyGroupRelation::getByGroupAndKeyId($group->getId(), $key1->getId());
        if (!$keygroup1) {
            //create key group relation
            $keygroup1 = new Classificationstore\KeyGroupRelation();
            $keygroup1->setKeyId($key1->getId());
            $keygroup1->setGroupId($group->getId());
            $keygroup1->setSorter(1);
            $keygroup1->save();
        }

        $keygroup2 = Classificationstore\KeyGroupRelation::getByGroupAndKeyId($group->getId(), $key2->getId());
        if (!$keygroup2) {
            $keygroup2 = new Classificationstore\KeyGroupRelation();
            $keygroup2->setKeyId($key2->getId());
            $keygroup2->setGroupId($group->getId());
            $keygroup2->setSorter(2);
            $keygroup2->save();
        }

        $group2 = Classificationstore\GroupConfig::getByName('group2', $store->getId());
        if (!$group2) {
            $group2 = new Classificationstore\GroupConfig();
            $group2->setStoreId($store->getId());
            $group2->setName('group2');
            $group2->save();
        }

        $key3 = Classificationstore\KeyConfig::getByName('field3', $store->getId());
        if (!$key3) {
            $key3 = new Classificationstore\KeyConfig();
            $key3->setDefinition(json_encode(new ClassDefinition\Data\Input()));
            $key3->setStoreId($store->getId());
            $key3->setName('field3');
            $key3->setDescription('Input Field 3');
            $key3->setEnabled(true);
            $key3->setType('input');
            $key3->save();
        }

        $keygroup3 = Classificationstore\KeyGroupRelation::getByGroupAndKeyId($group2->getId(), $key3->getId());
        if (!$keygroup3) {
            $keygroup3 = new Classificationstore\KeyGroupRelation();
            $keygroup3->setKeyId($key3->getId());
            $keygroup3->setGroupId($group2->getId());
            $keygroup3->setSorter(1);
            $keygroup3->save();
        }

        $collection = Classificationstore\CollectionConfig::getByName('collection1', $store->getId());
        if (!$collection) {
            $collection = new Classificationstore\CollectionConfig();
            $collection->setStoreId($store->getId());
            $collection->setName('collection1');
            $collection->save();
        }

        $collectionRelation = new Classificationstore\CollectionGroupRelation();
        $collectionRelation->setColId($collection->getId());
        $collectionRelation->setGroupId($group->getId());
        $collectionRelation->save();
    }

    /**
     * Tests the following scenario:
     *
     * root
     *    |-one
     *        |-two
     *           |-three
     *
     * add classification store to one(parent) and change value of 2 fields in the store,
     * add store to two(child) and change value of 1 field in the store,
     * create three(child) with empty store to inherit values from two & one
     * asserts inherited and non-inherited values on child & parent.
     *
     */
    public function testInheritance(): void
    {
        DataObject\Service::useInheritedValues(true, function () {
            $group = Classificationstore\GroupConfig::getByName('group1');
            $key1 = Classificationstore\KeyConfig::getByName('field1');
            $key2 = Classificationstore\KeyConfig::getByName('field2');

            $one = new Inheritance();
            $one->setKey('one');
            $one->setParentId(1);
            $one->setPublished(true);

            /** @var Classificationstore $oneStore */
            $oneStore = $one->getTeststore();
            $oneStore->setLocalizedKeyValue($group->getId(), $key1->getId(), 'oneinput1');
            $oneStore->setLocalizedKeyValue($group->getId(), $key2->getId(), 'oneinput2');
            $one->save();

            $two = new Inheritance();
            $two->setKey('two');
            $two->setParentId($one->getId());
            $two->setPublished(true);
            $two->save();

            /** @var Classificationstore $twoStore */
            $twoStore = $two->getTeststore();
            $twoStore->setLocalizedKeyValue($group->getId(), $key1->getId(), 'twoinput1');
            $twoStore->save();

            //check inherited & overriden value from child
            $this->assertEquals('twoinput1', $twoStore->getLocalizedKeyValue($group->getId(), $key1->getId()));
            $this->assertEquals('oneinput2', $twoStore->getLocalizedKeyValue($group->getId(), $key2->getId()));

            //check inherited & overriden value from parent
            $this->assertEquals('oneinput1', $oneStore->getLocalizedKeyValue($group->getId(), $key1->getId()));
            $this->assertEquals('oneinput2', $oneStore->getLocalizedKeyValue($group->getId(), $key2->getId()));
        });
    }

    /**
     * An empty group added via a collection must keep its group-collection mapping after a
     * save/reload round-trip, also when the object has an inheritable parent without an own mapping.
     *
     * @see https://pimcore.atlassian.net/browse/PEES-1225
     */
    public function testGroupCollectionMappingPersistsForEmptyGroups(): void
    {
        $group = Classificationstore\GroupConfig::getByName('group1');
        $collection = Classificationstore\CollectionConfig::getByName('collection1');

        $parent = new Inheritance();
        $parent->setKey('mapping-parent');
        $parent->setParentId(1);
        $parent->setPublished(true);
        $parent->save();

        $child = new Inheritance();
        $child->setKey('mapping-child');
        $child->setParentId($parent->getId());
        $child->setPublished(true);

        /** @var Classificationstore $childStore */
        $childStore = $child->getTeststore();
        $childStore->setActiveGroups([$group->getId() => true]);
        $childStore->setGroupCollectionMapping($group->getId(), $collection->getId());
        $child->save();

        /** @var Inheritance $reloadedChild */
        $reloadedChild = Inheritance::getById($child->getId(), ['force' => true]);
        /** @var Classificationstore $reloadedStore */
        $reloadedStore = $reloadedChild->getTeststore();

        DataObject\Service::useInheritedValues(false, function () use ($reloadedStore, $group, $collection) {
            $this->assertEquals([$group->getId() => true], $reloadedStore->getActiveGroups());
            $this->assertEquals(
                [$group->getId() => $collection->getId()],
                $reloadedStore->getGroupCollectionMappings(),
                'group-collection mapping of an empty group must survive a save/reload round-trip'
            );
        });
    }

    /**
     * A mapping that matches the inheritable parent's mapping is not persisted on the child,
     * but stays available through inheritance.
     */
    public function testInheritedGroupCollectionMappingIsNotPersistedOnChild(): void
    {
        $group = Classificationstore\GroupConfig::getByName('group1');
        $collection = Classificationstore\CollectionConfig::getByName('collection1');

        $parent = new Inheritance();
        $parent->setKey('mapping-parent');
        $parent->setParentId(1);
        $parent->setPublished(true);

        /** @var Classificationstore $parentStore */
        $parentStore = $parent->getTeststore();
        $parentStore->setActiveGroups([$group->getId() => true]);
        $parentStore->setGroupCollectionMapping($group->getId(), $collection->getId());
        $parent->save();

        $child = new Inheritance();
        $child->setKey('mapping-child');
        $child->setParentId($parent->getId());
        $child->setPublished(true);

        /** @var Classificationstore $childStore */
        $childStore = $child->getTeststore();
        $childStore->setActiveGroups([$group->getId() => true]);
        $childStore->setGroupCollectionMapping($group->getId(), $collection->getId());
        $child->save();

        /** @var Inheritance $reloadedChild */
        $reloadedChild = Inheritance::getById($child->getId(), ['force' => true]);
        /** @var Classificationstore $reloadedStore */
        $reloadedStore = $reloadedChild->getTeststore();

        DataObject\Service::useInheritedValues(false, function () use ($reloadedStore) {
            $this->assertEquals(
                [],
                $reloadedStore->getGroupCollectionMappings(),
                'mapping covered by the parent must not be persisted on the child'
            );
        });

        DataObject\Service::useInheritedValues(true, function () use ($reloadedStore, $group, $collection) {
            $this->assertEquals(
                [$group->getId() => $collection->getId()],
                $reloadedStore->getGroupCollectionMappings(),
                'mapping covered by the parent must be available through inheritance'
            );
        });
    }

    /**
     * When the parent maps one group to a collection and the child maps a *different* group to
     * that same collection, the child's mapping must survive a save/reload round-trip.
     *
     * This is the key regression for the array_diff_assoc() fix: the old array_diff() compared
     * only values, so a child mapping whose collectionId happened to equal any parent collectionId
     * was silently discarded even when the groupId was different.
     */
    public function testChildMappingForDifferentGroupToSameCollectionIsNotStripped(): void
    {
        $group1 = Classificationstore\GroupConfig::getByName('group1');
        $group2 = Classificationstore\GroupConfig::getByName('group2');
        $collection = Classificationstore\CollectionConfig::getByName('collection1');

        $parent = new Inheritance();
        $parent->setKey('mapping-parent');
        $parent->setParentId(1);
        $parent->setPublished(true);

        /** @var Classificationstore $parentStore */
        $parentStore = $parent->getTeststore();
        $parentStore->setActiveGroups([$group1->getId() => true]);
        $parentStore->setGroupCollectionMapping($group1->getId(), $collection->getId());
        $parent->save();

        $child = new Inheritance();
        $child->setKey('mapping-child');
        $child->setParentId($parent->getId());
        $child->setPublished(true);

        /** @var Classificationstore $childStore */
        $childStore = $child->getTeststore();
        $childStore->setActiveGroups([$group2->getId() => true]);
        $childStore->setGroupCollectionMapping($group2->getId(), $collection->getId());
        $child->save();

        /** @var Inheritance $reloadedChild */
        $reloadedChild = Inheritance::getById($child->getId(), ['force' => true]);
        /** @var Classificationstore $reloadedStore */
        $reloadedStore = $reloadedChild->getTeststore();

        DataObject\Service::useInheritedValues(false, function () use ($reloadedStore, $group2, $collection) {
            $this->assertEquals(
                [$group2->getId() => $collection->getId()],
                $reloadedStore->getGroupCollectionMappings(),
                'child mapping for a different group to the same collection must not be stripped'
            );
        });
    }

    /**
     * Inheritance resolves per (language, variant): a child that holds no value for one variant
     * takes the parent's value of that variant and never of another.
     */
    public function testAVariantIsInheritedOnItsOwn(): void
    {
        $this->putTheStoreOnTheDocumentEngine();
        [$groupId, $keyId] = $this->createMultiValueKey([self::METRIC, self::IMPERIAL], false);

        DataObject\Service::useInheritedValues(true, function () use ($groupId, $keyId) {
            $parent = $this->createObject('variant-parent', 1);
            $parent->getTeststore()->setKeyValues($groupId, $keyId, [
                new MultiValue('1.80 m', null, self::METRIC),
                new MultiValue('5 ft 11 in', null, self::IMPERIAL),
            ]);
            $parent->save();

            $child = $this->createObject('variant-child', (int) $parent->getId());
            $child->getTeststore()->setKeyValues($groupId, $keyId, [
                new MultiValue('1.90 m', null, self::METRIC),
            ]);
            $child->save();

            $childStore = Inheritance::getById($child->getId(), ['force' => true])->getTeststore();

            $this->assertSame(
                ['1.90 m'],
                $childStore->getKeyValues($groupId, $keyId, null, self::METRIC),
                'the child answers its own metric value'
            );
            $this->assertSame(
                ['5 ft 11 in'],
                $childStore->getKeyValues($groupId, $keyId, null, self::IMPERIAL),
                'and inherits the imperial one it holds nothing for'
            );
        });
    }

    /**
     * Within one (language, variant) slot a list is inherited whole: a child that holds its own
     * list answers exactly that list, and the parent's positions are never merged into it.
     */
    public function testAListIsInheritedWholeRatherThanMergedPerPosition(): void
    {
        $this->putTheStoreOnTheDocumentEngine();
        [$groupId, $keyId] = $this->createMultiValueKey([], true);

        DataObject\Service::useInheritedValues(true, function () use ($groupId, $keyId) {
            $parent = $this->createObject('list-parent', 1);
            $parent->getTeststore()->setKeyValues($groupId, $keyId, [
                new MultiValue('a', null, null, 0),
                new MultiValue('b', null, null, 1),
                new MultiValue('c', null, null, 2),
            ]);
            $parent->save();

            $child = $this->createObject('list-child', (int) $parent->getId());
            $child->getTeststore()->setKeyValues($groupId, $keyId, [new MultiValue('x', null, null, 0)]);
            $child->save();

            $childStore = Inheritance::getById($child->getId(), ['force' => true])->getTeststore();

            $this->assertSame(['x'], $childStore->getKeyValues($groupId, $keyId));

            $parentStore = Inheritance::getById($parent->getId(), ['force' => true])->getTeststore();
            $this->assertSame(['a', 'b', 'c'], $parentStore->getKeyValues($groupId, $keyId));
        });
    }

    /**
     * A (language, variant) slot the child owns is the child's whole answer, even where the list it
     * holds starts above position 0: the value the documented items answer at position 0 is the
     * parent's there, and writing it into the child's list would give the child a value it never
     * wrote.
     */
    public function testAChildsListIsNotMergedWithTheParentsFirstValue(): void
    {
        $this->putTheStoreOnTheDocumentEngine();
        [$groupId, $keyId] = $this->createMultiValueKey([], true);

        DataObject\Service::useInheritedValues(true, function () use ($groupId, $keyId) {
            $parent = $this->createObject('offset-parent', 1);
            $parent->getTeststore()->setKeyValues($groupId, $keyId, [
                new MultiValue('a', null, null, 0),
                new MultiValue('b', null, null, 1),
            ]);
            $parent->save();

            $child = $this->createObject('offset-child', (int) $parent->getId());
            $child->getTeststore()->setKeyValues($groupId, $keyId, [new MultiValue('x', null, null, 1)]);
            $child->save();

            $childStore = Inheritance::getById($child->getId(), ['force' => true])->getTeststore();

            $this->assertSame(
                [1 => 'x'],
                $childStore->getKeyValues($groupId, $keyId),
                'the slot the child owns answers the child alone'
            );
            $this->assertNull(
                $childStore->getKeyValue($groupId, $keyId),
                'and the position the child holds nothing at is not the parent\'s to fill'
            );
        });
    }

    private function createObject(string $key, int $parentId): Inheritance
    {
        $object = new Inheritance();
        $object->setKey($key);
        $object->setParentId($parentId);
        $object->setPublished(true);
        $object->save();

        return $object;
    }

    /**
     * The qualifiers are a capability of the document engine, so the shared test store is put on it
     * for the duration of the test and put back in tearDown - the other test classes of the suite
     * assert against the engine their own fixture asked for.
     */
    private function putTheStoreOnTheDocumentEngine(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $this->storeEngineBeforeTheTest = $store->getEngine();
        $this->storeWasPutOnTheDocumentEngine = true;

        $store->setEngine(self::DOCUMENT_ENGINE);
        $store->save();

        Pimcore::getContainer()->get(Classificationstore\Engine\EngineResolver::class)
            ->resolveForStore($store->getId())
            ->createUpdateTables(ClassDefinition::getByName('inheritance'), $store->getId());
    }

    /**
     * @param string[] $variants
     *
     * @return array{int, int} the group id and the key id
     */
    private function createMultiValueKey(array $variants, bool $list): array
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName(self::MULTI_VALUE_GROUP);
        $group->save();

        $inner = new ClassDefinition\Data\Input();
        $inner->setName(self::MULTI_VALUE_KEY);

        $definition = new ClassDefinition\Data\MultiValue();
        $definition->setName(self::MULTI_VALUE_KEY);
        $definition->setVariants(array_map(static fn (string $name): array => ['name' => $name], $variants));
        $definition->setMultiValue($list);
        $definition->setDelegateDatatype($inner->getFieldType());
        $definition->setDelegate($inner);

        $keyConfig = new Classificationstore\KeyConfig();
        $keyConfig->setStoreId($store->getId());
        $keyConfig->setName(self::MULTI_VALUE_KEY);
        $keyConfig->setEnabled(true);
        $keyConfig->setType($definition->getFieldType());
        $keyConfig->setDefinition((string) json_encode($definition));
        $keyConfig->save();

        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setKeyId($keyConfig->getId());
        $relation->setGroupId($group->getId());
        $relation->setSorter(1);
        $relation->save();

        Classificationstore\DefinitionCache::clear(null);
        Classificationstore\Service::clearDefinitionsCache();
        Pimcore::getContainer()->get(Classificationstore\KeyGroupRelationCache::class)->reset();

        return [(int) $group->getId(), (int) $keyConfig->getId()];
    }
}
