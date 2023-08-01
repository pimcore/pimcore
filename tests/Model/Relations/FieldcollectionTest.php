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

namespace Pimcore\Tests\Model\Relations;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class FieldcollectionTest
 *
 * @package Pimcore\Tests\Model\Relations
 *
 * @group model.relations.fieldcollection
 */
class FieldcollectionTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_RelationTest();
        $this->tester->setupFieldcollection_Unittestfieldcollection();
    }

    public function testRelationFieldInsideFieldCollection(): void
    {
        $target1 = new RelationTest();
        $target1->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target1->setKey('mytarget1');
        $target1->setPublished(true);
        $target1->save();

        $target2 = new RelationTest();
        $target2->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target2->setKey('mytarget2');
        $target2->setPublished(true);
        $target2->save();

        $target3 = new RelationTest();
        $target3->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target3->setKey('mytarget3');
        $target3->setPublished(true);
        $target3->save();

        $object = TestHelper::createEmptyObject();

        //save data for language "en"
        $items = new Fieldcollection();

        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $item1->setFieldRelation([$target1]);

        $item2 = new FieldCollection\Data\Unittestfieldcollection();
        $item2->setFieldRelation([$target2]);

        $items->add($item1);
        $items->add($item2);

        $object->setFieldcollection($items);
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        /** @var Fieldcollection $fc */
        $fc = $object->getFieldcollection();
        $items = $fc->getItems();
        $loadedFieldcollectionItem = $items[1];
        $loadedFieldcollectionItem->setFieldRelation([$target3]);           // target3 instead of target2
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $rel = $loadedFieldcollectionItem->getFieldRelation();
        $this->assertEquals($target1->getId(), $rel[0]->getId());

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $rel = $loadedFieldcollectionItem->getFieldRelation();
        $this->assertEquals($target3->getId(), $rel[0]->getId());

        //Flush relations
        $loadedFieldcollectionItem->setFieldRelation(null);
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $rel = $loadedFieldcollectionItem->getFieldRelation();
        $this->assertEquals([], $rel);
    }

    public function testAdvancedRelationFieldInsideFieldCollection(): void
    {
        $object = TestHelper::createEmptyObject();
        $items = new Fieldcollection();

        $target1 = new Asset();
        $target1->setParent(Asset::getByPath('/'));
        $target1->setKey('mytarget1');
        $target1->save();

        $target2 = new Asset();
        $target2->setParent(Asset::getByPath('/'));
        $target2->setKey('mytarget2');
        $target2->save();

        $target3 = new Asset();
        $target3->setParent(Asset::getByPath('/'));
        $target3->setKey('mytarget3');
        $target3->save();

        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $item1->setAdvancedFieldRelation([new ElementMetadata('metadataUpper', [], $target1)]);

        $item2 = new FieldCollection\Data\Unittestfieldcollection();
        $item2->setAdvancedFieldRelation([new ElementMetadata('metadataUpper', [], $target2)]);

        $items->add($item1);
        $items->add($item2);

        $object->setFieldcollection($items);
        $object->save();

        // Test by deleting the target2 element
        $target2->delete();

        $object = DataObject::getById($object->getId(), ['force' => true]);
        $object->save();

        $object = DataObject::getById($object->getId(), ['force' => true]);
        //check if target1 is still there
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $rel = $loadedFieldcollectionItem->getAdvancedFieldRelation();
        $this->assertEquals($target1->getId(), isset($rel[0]) ? $rel[0]->getElementId() : false);

        //check if target2 is removed
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $rel = $loadedFieldcollectionItem->getAdvancedFieldRelation();
        $this->assertEquals(false, isset($rel[0]));
    }

    public function testLocalizedFieldInsideFieldCollection(): void
    {
        $target1 = new RelationTest();
        $target1->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target1->setKey('mytarget1');
        $target1->setPublished(true);
        $target1->save();

        $target2 = new RelationTest();
        $target2->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target2->setKey('mytarget2');
        $target2->setPublished(true);
        $target2->save();

        $target3 = new RelationTest();
        $target3->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target3->setKey('mytarget3');
        $target3->setPublished(true);
        $target3->save();

        $object = TestHelper::createEmptyObject();

        //save data for language "en"
        $items = new Fieldcollection();

        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $item1->setLinput('textEN', 'en');
        $item1->setLRelation($target1, 'en');

        $item2 = new FieldCollection\Data\Unittestfieldcollection();
        $item2->setLinput('textEN', 'en');
        $item2->setLRelation($target2, 'en');

        $items->add($item1);
        $items->add($item2);

        $object->setFieldcollection($items);
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        /** @var Fieldcollection $fc */
        $fc = $object->getFieldcollection();
        $items = $fc->getItems();
        $loadedFieldcollectionItem = $items[1];
        $loadedFieldcollectionItem->setLRelation($target3, 'en');
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $rel = $loadedFieldcollectionItem->getLRelation('en');
        $this->assertEquals($target1->getId(), $rel->getId());

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $rel = $loadedFieldcollectionItem->getLRelation('en');
        $this->assertEquals($target3->getId(), $rel->getId());

        //Flush relations
        $loadedFieldcollectionItem->setLRelation(null, 'en');
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $lrel = $loadedFieldcollectionItem->getLRelation('en');
        $this->assertEquals(null, $lrel);
    }
}
