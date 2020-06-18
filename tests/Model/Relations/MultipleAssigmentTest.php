<?php

namespace Pimcore\Tests\Model\Relations;

use Pimcore\Cache;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\MultipleAssignments;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class MultipleAssigmentTest
 *
 * @package Pimcore\Tests\Model\Relations
 * @group model.relations.multipleassignment
 */
class MultipleAssigmentTest extends ModelTestCase
{
    public function setUp()
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->createRelationObjects();
    }

    protected function createRelationObjects()
    {
        for ($i = 0; $i < 20; $i++) {
            $object = new RelationTest();
            $object->setParent(Service::createFolderByPath('__test/relationobjects'));
            $object->setKey("relation-$i");
            $object->setPublished(true);
            $object->setSomeAttribute("Some content $i");
            $object->save();
        }
    }

    public function tearDown()
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses()
    {
        $this->tester->setupPimcoreClass_RelationTest();
        $this->tester->setupPimcoreClass_MultipleAssignments();
    }

    public function testMultipleAssignmentsOnSingleManyToMany()
    {
        $listing = new RelationTest\Listing();
        $listing->setLimit(5);

        $object = new MultipleAssignments();
        $object->setParent(Service::createFolderByPath('/assignments'));
        $object->setKey('test1');
        $object->setPublished(true);

        $metaDataList = [];

        foreach ($listing as $i => $item) {
            $objectMetadata = new ElementMetadata('onlyOneManyToMany', ['meta'], $item);
            $objectMetadata->setMeta("single-some-metadata $i");
            $metaDataList[] = $objectMetadata;

            $objectMetadata = new ElementMetadata('onlyOneManyToMany', ['meta'], $item);
            $objectMetadata->setMeta("single-some-more-metadata $i");
            $metaDataList[] = $objectMetadata;
        }

        $object->setOnlyOneManyToMany($metaDataList);

        $object->save();

        $metaDataList = $object->getOnlyOneManyToMany();
        $this->checkMultipleAssignmentsOnSingleManyToMany($metaDataList, 'after saving');

        $id = $object->getId();

        //clear cache and collect garbage
        Cache::clearAll();
        \Pimcore::collectGarbage();

        //reload data object from database
        $object = MultipleAssignments::getById($id, true);

        $metaDataList = $object->getOnlyOneManyToMany();
        $this->checkMultipleAssignmentsOnSingleManyToMany($metaDataList, 'after loading');

        $serializedData = serialize($object);
        $deserializedObject = unserialize($serializedData);
        $metaDataList = $deserializedObject->getOnlyOneManyToMany();
        $this->checkMultipleAssignmentsOnSingleManyToMany($metaDataList, 'after serialize/unserialize');
    }

    protected function checkMultipleAssignmentsOnSingleManyToMany(array $metaDataList, $positionMessage = '')
    {
        $this->assertEquals(5, count($metaDataList), "Relation count $positionMessage.");
        foreach ($metaDataList as $i => $metadata) {
            $this->assertEquals("single-some-metadata $i", $metadata->getMeta(), "Metadata $positionMessage.");
        }
    }

    public function testMultipleAssignmentsOnSingleManyToManyObject()
    {
        $listing = new RelationTest\Listing();
        $listing->setLimit(5);

        $object = new MultipleAssignments();
        $object->setParent(Service::createFolderByPath('/assignments'));
        $object->setKey('test1');
        $object->setPublished(true);

        $metaDataList = [];

        foreach ($listing as $i => $item) {
            $objectMetadata = new ObjectMetadata('onlyOneManyToManyObject', ['meta'], $item);
            $objectMetadata->setMeta("single-some-metadata $i");
            $metaDataList[] = $objectMetadata;

            $objectMetadata = new ObjectMetadata('onlyOneManyToManyObject', ['meta'], $item);
            $objectMetadata->setMeta("single-some-more-metadata $i");
            $metaDataList[] = $objectMetadata;
        }

        $object->setOnlyOneManyToManyObject($metaDataList);

        $object->save();

        $metaDataList = $object->getOnlyOneManyToManyObject();
        $this->checkMultipleAssignmentsOnSingleManyToMany($metaDataList, 'after saving');

        $id = $object->getId();

        //clear cache and collect garbage
        Cache::clearAll();
        \Pimcore::collectGarbage();

        //reload data object from database
        $object = MultipleAssignments::getById($id, true);

        $metaDataList = $object->getOnlyOneManyToManyObject();
        $this->checkMultipleAssignmentsOnSingleManyToMany($metaDataList, 'after loading');

        $serializedData = serialize($object);
        $deserializedObject = unserialize($serializedData);
        $metaDataList = $deserializedObject->getOnlyOneManyToManyObject();
        $this->checkMultipleAssignmentsOnSingleManyToMany($metaDataList, 'after serialize/unserialize');
    }

    protected function checkMultipleAssignmentsOnMultipleManyToMany(array $metaDataList, $positionMessage = '')
    {
        $this->assertEquals(10, count($metaDataList), "Relation count $positionMessage.");
        $number = 0;
        foreach ($metaDataList as $i => $metadata) {
            if ($i % 2) {
                $this->assertEquals("multiple-some-more-metadata $number", $metadata->getMeta(), "Metadata $positionMessage.");
                $number++;
            } else {
                $this->assertEquals("multiple-some-metadata $number", $metadata->getMeta(), "Metadata $positionMessage.");
            }
        }
    }

    public function testMultipleAssignmentsMultipleManyToMany()
    {
        $listing = new RelationTest\Listing();
        $listing->setLimit(5);

        $object = new MultipleAssignments();
        $object->setParent(Service::createFolderByPath('/assignments'));
        $object->setKey('test1');
        $object->setPublished(true);

        $metaDataList = [];

        foreach ($listing as $i => $item) {
            $objectMetadata = new ElementMetadata('multipleManyToMany', ['meta'], $item);
            $objectMetadata->setMeta("multiple-some-metadata $i");
            $metaDataList[] = $objectMetadata;

            $objectMetadata = new ElementMetadata('multipleManyToMany', ['meta'], $item);
            $objectMetadata->setMeta("multiple-some-more-metadata $i");
            $metaDataList[] = $objectMetadata;
        }

        $object->setMultipleManyToMany($metaDataList);

        $object->save();

        $metaDataList = $object->getMultipleManyToMany();
        $this->checkMultipleAssignmentsOnMultipleManyToMany($metaDataList, 'after saving');

        $id = $object->getId();

        //clear cache and collect garbage
        Cache::clearAll();
        \Pimcore::collectGarbage();

        //reload data object from database
        $object = MultipleAssignments::getById($id, true);

        $metaDataList = $object->getMultipleManyToMany();
        $this->checkMultipleAssignmentsOnMultipleManyToMany($metaDataList, 'after loading');

        $serializedData = serialize($object);
        $deserializedObject = unserialize($serializedData);
        $metaDataList = $deserializedObject->getMultipleManyToMany();
        $this->checkMultipleAssignmentsOnMultipleManyToMany($metaDataList, 'after serialize/unserialize');
    }

    public function testMultipleAssignmentsMultipleManyToManyObject()
    {
        $listing = new RelationTest\Listing();
        $listing->setLimit(5);

        $object = new MultipleAssignments();
        $object->setParent(Service::createFolderByPath('/assignments'));
        $object->setKey('test1');
        $object->setPublished(true);

        $metaDataList = [];

        foreach ($listing as $i => $item) {
            $objectMetadata = new ObjectMetadata('multipleManyToManyObject', ['meta'], $item);
            $objectMetadata->setMeta("multiple-some-metadata $i");
            $metaDataList[] = $objectMetadata;

            $objectMetadata = new ObjectMetadata('multipleManyToManyObject', ['meta'], $item);
            $objectMetadata->setMeta("multiple-some-more-metadata $i");
            $metaDataList[] = $objectMetadata;
        }

        $object->setMultipleManyToManyObject($metaDataList);

        $object->save();

        $metaDataList = $object->getMultipleManyToManyObject();
        $this->checkMultipleAssignmentsOnMultipleManyToMany($metaDataList, 'after saving');

        $id = $object->getId();

        //clear cache and collect garbage
        Cache::clearAll();
        \Pimcore::collectGarbage();

        //reload data object from database
        $object = MultipleAssignments::getById($id, true);

        $metaDataList = $object->getMultipleManyToManyObject();
        $this->checkMultipleAssignmentsOnMultipleManyToMany($metaDataList, 'after loading');

        $serializedData = serialize($object);
        $deserializedObject = unserialize($serializedData);
        $metaDataList = $deserializedObject->getMultipleManyToManyObject();
        $this->checkMultipleAssignmentsOnMultipleManyToMany($metaDataList, 'after serialize/unserialize');
    }
}
