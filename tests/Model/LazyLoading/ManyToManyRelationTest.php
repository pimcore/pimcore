<?php

namespace Pimcore\Tests\Model\LazyLoading;

use Pimcore\Cache;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingTest;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;

class ManyToManyRelationTest extends AbstractLazyLoadingTest
{
    public function testUnpublished()
    {
        $preservedState = Concrete::getHideUnpublished();
        $folder = Service::createFolderByPath('/rel-test');

        $unpub = new RelationTest();
        $unpub->setParent($folder);
        $unpub->setPublished(false);
        $unpub->setKey('unpub');
        $unpub->save();

        $source = new Lazyloading();
        $source->setParentId(1);
        $source->setKey('source');
        $source->setPublished(true);
        $source->setRelations([$unpub]);
        $source->save();

        $source = LazyLoading::getById($source->getId(), true);

        $this->assertEquals(0, count($source->getRelations()), 'expected 0 items');

        Concrete::setHideUnpublished(false);
        $this->assertEquals(1, count($source->getRelations()), 'expected 1 items');

        Concrete::setHideUnpublished(true);
        $source->setRelations([]);
        $source->save();
        $source = LazyLoading::getById($source->getId(), true);

        Concrete::setHideUnpublished(false);
        $this->assertEquals(0, count($source->getRelations()), 'expected 0 items');

        Concrete::setHideUnpublished($preservedState);
    }

    public function testClassAttributes()
    {
        //prepare data object

        $object = $this->createDataObject();
        $object->setRelations($this->loadRelations()->load());
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            \Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, true);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //load relation and check if relation loads correctly
            $relationObjects = $object->getRelations();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);
        }
    }

    public function testLocalizedClassAttributes()
    {
        //prepare data object
        $object = $this->createDataObject();
        $object->setLrelations($this->loadRelations()->load());
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            \Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, true);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //load relation and check if relation loads correctly
            $relationObjects = $object->getLrelations();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);
        }
    }

    public function testBlockClassAttributes()
    {
        //prepare data object
        $object = $this->createDataObject();
        $data = [
            'blockrelations' => new BlockElement('blockrelations', 'manyToManyRelation', $this->loadRelations()->load()),
        ];
        $object->setTestBlock([$data]);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
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
            $relationObjects = $blockItems[0]['blockrelations']->getData();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, $contentShouldBeIncluded);
        }
    }

    public function testLazyBlockClassAttributes()
    {
        //prepare data object
        $object = $this->createDataObject();
        $data = [
            'blockrelationsLazyLoaded' => new BlockElement('blockrelationsLazyLoaded', 'manyToManyRelation', $this->loadRelations()->load()),
        ];
        $object->setTestBlockLazyloaded([$data]);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
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
            $relationObjects = $blockItems[0]['blockrelationsLazyLoaded']->getData();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);
        }
    }

    public function testFieldCollectionAttributes()
    {
        //prepare data object
        $object = $this->createDataObject();

        $items = new Fieldcollection();
        $item = new Fieldcollection\Data\LazyLoadingTest();
        $item->setRelations($this->loadRelations()->load());
        $items->add($item);
        $object->setFieldcollection($items);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
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
            if ($objectType == 'parent') {
                $item = $collection->get(0);
                $relationObjects = $item->getRelations();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');
            }

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);
        }
    }

    public function testFieldCollectionLocalizedAttributes()
    {
        //prepare data object
        /** @var LazyLoading $object */
        $object = $this->createDataObject();

        $items = new Fieldcollection();
        $item = new Fieldcollection\Data\LazyLoadingLocalizedTest();
        $item->setLrelations($this->loadRelations()->load());
        $items->add($item);
        $object->setFieldcollection($items);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        $lRelations1 = $item->getLRelations();

        $object = LazyLoading::getById($object->getId(), true);
        $collection = $object->getFieldcollection();
        /** @var Fieldcollection\Data\LazyLoadingLocalizedTest $firstItem */
        $firstItem = $collection->get(0);
        $firstItem->setLInput(uniqid());        // make it dirty but do not touch the relation
        $object->save();

        $this->assertTrue(count($lRelations1) > 0);

        $object = LazyLoading::getById($object->getId(), true);

        //load relation and check if relation loads correctly
        $collection = $object->getFieldcollection();
        /** @var Fieldcollection\Data\LazyLoadingLocalizedTest $firstItem */
        $firstItem = $collection->get(0);
        $loadedRelations = $firstItem->getLRelations();

        $this->assertEquals(self::RELATION_COUNT, count($loadedRelations), 'expected that original relations count is the same as the new one');

        //save only non localized field and check if relation loads correctly
        $firstItem->setNormalInput(uniqid());
        $collection->setItems([$firstItem]);

        $object->save();

        $object = LazyLoading::getById($object->getId(), true);

        //load relation and check if relation loads correctly
        $collection = $object->getFieldcollection();
        /** @var Fieldcollection\Data\LazyLoadingLocalizedTest $firstItem */
        $firstItem = $collection->get(0);
        $loadedRelations = $firstItem->getLRelations();

        $this->assertEquals(self::RELATION_COUNT, count($loadedRelations), 'expected that original relations count is the same as the new one');

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            \Pimcore::collectGarbage();

            //reload data object from database

            $object = LazyLoading::getById($id, true);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            if ($objectType == 'parent') {
                $item = $collection->get(0);
                $relationObjects = $item->getLrelations();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');
            }

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);
        }
    }

    public function testBrickAttributes()
    {
        //prepare data object
        $object = $this->createDataObject();
        $brick = new LazyLoadingTest($object);
        $brick->setRelations($this->loadRelations()->load());
        $object->getBricks()->setLazyLoadingTest($brick);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
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
            $relationObjects = $brick->getRelations();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);
        }
    }

    public function testLocalizedBrickAttributes()
    {
        //prepare data object
        $object = $this->createDataObject();
        $brick = new LazyLoadingLocalizedTest($object);

        $relations = $this->loadRelations()->load();

        $brick->getLocalizedfields()->setLocalizedValue('lrelations', $relations, 'en');
        $brick->getLocalizedfields()->setLocalizedValue('lrelations', $relations, 'de');

        $object->getBricks()->setLazyLoadingLocalizedTest($brick);
        $object->save();

        $brick->setLInput(uniqid());
        $object->save();
        $object = Concrete::getById($object->getId(), true);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLRelations('en')) > 0);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLRelations('de')) > 0);

        $object = Concrete::getById($object->getId(), true);
        $newRelations = $this->loadRelations()->load();
        array_pop($newRelations);
        $brick = $object->getBricks()->getLazyLoadingLocalizedTest();

        $lFields = $brick->getLocalizedfields();

        // change one language and make sure that it does not affect the other one
        $lFields->setLocalizedValue('lrelations', $newRelations, 'de');
        $object->save();

        $object = Concrete::getById($object->getId(), true);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLRelations('en')) > 0);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLRelations('de')) > 0);

        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
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
            $relationObjects = $brick->getLocalizedFields()->getLocalizedValue('lrelations');
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);
        }
    }
}
