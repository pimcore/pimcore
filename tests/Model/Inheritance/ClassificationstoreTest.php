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
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        Pimcore::setAdminMode();
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

        $this->assertEquals([$group->getId() => true], $reloadedStore->getActiveGroups());
        $this->assertEquals(
            [$group->getId() => $collection->getId()],
            $reloadedStore->getGroupCollectionMappings(),
            'group-collection mapping of an empty group must survive a save/reload round-trip'
        );
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

        $this->assertEquals(
            [],
            $reloadedStore->getGroupCollectionMappings(),
            'mapping covered by the parent must not be persisted on the child'
        );

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

        $this->assertEquals(
            [$group2->getId() => $collection->getId()],
            $reloadedStore->getGroupCollectionMappings(),
            'child mapping for a different group to the same collection must not be stripped'
        );
    }
}
