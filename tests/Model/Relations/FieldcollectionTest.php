<?php

namespace Pimcore\Tests\Model\Relations;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class FieldcollectionTest
 *
 * @package Pimcore\Tests\Model\Relations
 * @group model.relations.fieldcollection
 */
class FieldcollectionTest extends ModelTestCase
{
    public function setUp()
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function tearDown()
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses()
    {
        $this->tester->setupPimcoreClass_RelationTest();
        $this->tester->setupFieldcollection_Unittestfieldcollection();
    }

    public function testRelationFieldInsideFieldCollection()
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
        $object = AbstractObject::getById($object->getId(), true);

        /** @var Fieldcollection $fc */
        $fc = $object->getFieldcollection();
        $items = $fc->getItems();
        $loadedFieldcollectionItem = $items[1];
        $loadedFieldcollectionItem->setFieldRelation([$target3]);           // target3 instead of target2
        $object->save();

        //Reload object from db
        $object = AbstractObject::getById($object->getId(), true);
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
        $object = AbstractObject::getById($object->getId(), true);

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $rel = $loadedFieldcollectionItem->getFieldRelation();
        $this->assertEquals([], $rel);
    }

    public function testLocalizedFieldInsideFieldCollection()
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
        $object = AbstractObject::getById($object->getId(), true);

        /** @var Fieldcollection $fc */
        $fc = $object->getFieldcollection();
        $items = $fc->getItems();
        $loadedFieldcollectionItem = $items[1];
        $loadedFieldcollectionItem->setLRelation($target3, 'en');
        $object->save();

        //Reload object from db
        $object = AbstractObject::getById($object->getId(), true);
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
        $object = AbstractObject::getById($object->getId(), true);

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $lrel = $loadedFieldcollectionItem->getLRelation('en');
        $this->assertEquals(null, $lrel);
    }
}
