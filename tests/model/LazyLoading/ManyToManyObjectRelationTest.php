<?php

namespace Pimcore\Tests\Model\LazyLoading;


use Pimcore\Cache;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Definition;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingTest;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

class ManyToManyObjectRelationTest extends ModelTestCase
{

    public function setUp()
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->createRelationObjects();
    }

    public function tearDown()
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses()
    {
        $name = 'RelationTest';
        $file = 'lazyloading/class_RelationTest_export.json';
        $class = ClassDefinition::getByName($name);

        if (!$class) {
            /** @var ClassDefinition $class */
            $class = $this->tester->setupClass($name, $file);
        }

        $name = 'LazyLoadingTest';
        $file = 'lazyloading/fieldcollection_LazyLoadingTest_export.json';
        $fieldCollection = $this->tester->setupFieldCollection($name, $file);

        $name = 'LazyLoadingLocalizedTest';
        $file = 'lazyloading/fieldcollection_LazyLoadingLocalizedTest_export.json';
        $fieldCollection = $this->tester->setupFieldCollection($name, $file);


        $name = 'LazyLoading';
        $file = 'lazyloading/class_LazyLoading_export.json';
        $class = ClassDefinition::getByName($name);

        if (!$class) {
            /** @var ClassDefinition $class */
            $class = $this->tester->setupClass($name, $file);
        }

        $name = 'LazyLoadingTest';
        $file = 'lazyloading/objectbrick_LazyLoadingTest_export.json';
        $brick = $this->tester->setupObjectBrick($name, $file);


        $name = 'LazyLoadingLocalizedTest';
        $file = 'lazyloading/objectbrick_LazyLoadingLocalizedTest_export.json';
        $brick = $this->tester->setupObjectBrick($name, $file);

    }

    protected function createRelationObjects() {
        for($i = 0; $i < 20; $i++) {
            $object = new RelationTest();
            $object->setParent(Service::createFolderByPath("__test/relationobjects"));
            $object->setKey("relation-$i");
            $object->setPublished(true);
            $object->setSomeAttribute("Some content $i");
            $object->save();
        }
    }

    /**
     * @return LazyLoading
     */
    protected function createDataObject(): LazyLoading {
        $object = new LazyLoading();
        $object->setParentId(1);
        $object->setKey('lazy1');
        $object->setPublished(true);
        return $object;
    }

    /**
     * @param $parent
     * @return LazyLoading
     * @throws \Exception
     */
    protected function createChildDataObject($parent): LazyLoading {
        $object = new LazyLoading();
        $object->setParent($parent);
        $object->setKey('sub-lazy');
        $object->setPublished(true);
        $object->save();
        return $object;
    }

    public function testClassAttributes() {
        //prepare data object
        $relationCount = 5;
        $listing = new RelationTest\Listing();
        $listing->setLimit($relationCount);

        $object = $this->createDataObject();
        $object->setObjects($listing->load());
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();



        foreach(['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {

            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            \Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, true);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //load relation and check if relation loads correctly
            $relationObjects = $object->getObjects();
            $this->assertEquals($relationCount, count($relationObjects), $messagePrefix . "relations not loaded properly");

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

        }

    }

    public function testLocalizedClassAttributes() {
        //prepare data object
        $relationCount = 5;
        $listing = new RelationTest\Listing();
        $listing->setLimit($relationCount);

        $object = $this->createDataObject();
        $object->setLobjects($listing->load());
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach(['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {

            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            \Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, true);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //load relation and check if relation loads correctly
            $relationObjects = $object->getLobjects();
            $this->assertEquals($relationCount, count($relationObjects), $messagePrefix . "relations not loaded properly");

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);
        }
    }


    public function testBlockClassAttributes() {
        //prepare data object
        $relationCount = 5;
        $listing = new RelationTest\Listing();
        $listing->setLimit($relationCount);

        $object = $this->createDataObject();
        $data = [
            "blockobjects" => new BlockElement('blockobjects', 'manyToManyObjectRelation', $listing->load()),
        ];
        $object->setTestBlock([$data]);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach(['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {

            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            \Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, true);

            // inherited data isn't assigned to a property, it's only returned by the getter and therefore doesn't get serialized
            $contentShouldBeIncluded = ($objectType === 'inherited') ? false : true;

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, $contentShouldBeIncluded);

            //load relation and check if relation loads correctly
            $blockItems = $object->getTestBlock();
            $relationObjects = $blockItems[0]["blockobjects"]->getData();
            $this->assertEquals($relationCount, count($relationObjects), $messagePrefix . "relations not loaded properly");

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, $contentShouldBeIncluded);
        }
    }

    public function testLazyBlockClassAttributes() {
        //prepare data object
        $relationCount = 5;
        $listing = new RelationTest\Listing();
        $listing->setLimit($relationCount);

        $object = $this->createDataObject();
        $data = [
            "blockobjectsLazyloaded" => new BlockElement('blockobjectsLazyloaded', 'manyToManyObjects', $listing->load()),
        ];
        $object->setTestBlockLazyloaded([$data]);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach(['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {

            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            \Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, true);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //load relation and check if relation loads correctly
            $blockItems = $object->getTestBlockLazyloaded();
            $relationObjects = $blockItems[0]["blockobjectsLazyloaded"]->getData();
            $this->assertEquals($relationCount, count($relationObjects), $messagePrefix . "relations not loaded properly");

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);
        }
    }


    public function testFieldCollectionAttributes() {
        //prepare data object
        $relationCount = 5;
        $listing = new RelationTest\Listing();
        $listing->setLimit($relationCount);

        $object = $this->createDataObject();

        $items = new Fieldcollection();
        $item = new Fieldcollection\Data\LazyLoadingTest();
        $item->setObjects($listing->load());
        $items->add($item);
        $object->setFieldcollection($items);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach(['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {

            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            \Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, true);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //load relation and check if relation loads correctly
            $collection = $object->getFieldcollection();
            if($objectType == 'parent') {
                $item = $collection->get(0);
                $relationObjects = $item->getObjects();
                $this->assertEquals($relationCount, count($relationObjects), $messagePrefix . "relations not loaded properly");
            }

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);
        }
    }


    public function testFieldCollectionLocalizedAttributes() {
        //prepare data object
        $relationCount = 5;
        $listing = new RelationTest\Listing();
        $listing->setLimit($relationCount);

        $object = $this->createDataObject();

        $items = new Fieldcollection();
        $item = new Fieldcollection\Data\LazyLoadingLocalizedTest();
        $item->setLObjects($listing->load());
        $items->add($item);
        $object->setFieldcollection($items);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach(['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {

            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            \Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, true);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //load relation and check if relation loads correctly
            $collection = $object->getFieldcollection();
            if($objectType == 'parent') {
                $item = $collection->get(0);
                $relationObjects = $item->getLObjects();
                $this->assertEquals($relationCount, count($relationObjects), $messagePrefix . "relations not loaded properly");
            }

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);
        }
    }

    public function testBrickAttributes() {
        //prepare data object
        $relationCount = 5;
        $listing = new RelationTest\Listing();
        $listing->setLimit($relationCount);

        $object = $this->createDataObject();
        $brick = new LazyLoadingTest($object);
        $brick->setObjects($listing->load());
        $object->getBricks()->setLazyLoadingTest($brick);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach(['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {

            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            \Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, true);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //load relation and check if relation loads correctly
            $brick = $object->getBricks()->getLazyLoadingTest();
            $relationObjects = $brick->getObjects();
            $this->assertEquals($relationCount, count($relationObjects), $messagePrefix . "relations not loaded properly");

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);
        }
    }

    public function testLocalizedBrickAttributes() {
        //prepare data object
        $relationCount = 5;
        $listing = new RelationTest\Listing();
        $listing->setLimit($relationCount);

        $object = $this->createDataObject();
        $brick = new LazyLoadingLocalizedTest($object);
        $brick->getLocalizedfields()->setLocalizedValue('lobjects', $listing->load());
        $object->getBricks()->setLazyLoadingLocalizedTest($brick);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach(['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {

            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            \Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, true);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //load relation and check if relation loads correctly
            $brick = $object->getBricks()->getLazyLoadingLocalizedTest();
            $relationObjects = $brick->getLocalizedFields()->getLocalizedValue('lobjects');
            $this->assertEquals($relationCount, count($relationObjects), $messagePrefix . "relations not loaded properly");

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);
        }
    }



    protected function checkSerialization(LazyLoading $object, string $messagePrefix, bool $contentShouldBeIncluded = false) {
        $serializedString = serialize($object);
        $this->checkSerializedStringForNeedle($serializedString, ['lazyLoadedFields', 'lazyKeys', 'loadedLazyKeys'], false, $messagePrefix);
        $this->checkSerializedStringForNeedle($serializedString, 'someAttribute";s:14:"Some content', $contentShouldBeIncluded, $messagePrefix);
    }

    protected function checkSerializedStringForNeedle(string $string, $needle, bool $expected, string $messagePrefix = null) {

        if(!is_array($needle)) {
            $needle = [$needle];
        }

        foreach ($needle as $item) {
            $this->assertEquals($expected, strpos($string, $item) !== false, $messagePrefix . "Check if '$item' is occuring in serialized data.");
        }

    }

}
