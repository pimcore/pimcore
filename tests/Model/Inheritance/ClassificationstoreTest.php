<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
}
