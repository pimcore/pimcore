<?php

namespace Pimcore\Tests\Model\Relations;

use Pimcore\Cache;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\MultipleAssignments;
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

    public function testLocalizedFieldInsideFieldCollection()
    {
        $target1 = new RelationTest();
        $target1->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target1->setKey("mytarget1");
        $target1->setPublished(true);
        $target1->save();

        $target2 = new RelationTest();
        $target2->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target2->setKey("mytarget2");
        $target2->setPublished(true);
        $target2->save();

        $target3 = new RelationTest();
        $target3->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target3->setKey("mytarget3");
        $target3->setPublished(true);
        $target3->save();

        $object = TestHelper::createEmptyObject();

        //save data for language "en"
        $items = new Fieldcollection();


        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $item1->setLinput('textEN', 'en');
        $item1->setLRelation($target1, "en");

        $item2 = new FieldCollection\Data\Unittestfieldcollection();
        $item2->setLinput('textEN', 'en');
        $item2->setLRelation($target2, "en");

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
        $loadedFieldcollectionItem->setLRelation($target3, "en");
        $fc->setItems($items);
        $object->save();

        //Reload object from db
        $object = AbstractObject::getById($object->getId(), true);
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $rel = $loadedFieldcollectionItem->getLRelation("en");
        $this->assertEquals($target1->getId(), $rel->getId());


        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $rel = $loadedFieldcollectionItem->getLRelation("en");
        $this->assertEquals($target3->getId(), $rel->getId());

    }
}
