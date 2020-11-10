<?php

namespace Pimcore\Tests\Model\LazyLoading;

use Pimcore\Cache;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingTest;

class ManyToOneRelationTest extends AbstractLazyLoadingTest
{
    public function testClassAttributes()
    {
        //prepare data object
        $relationObject = $this->loadSingleRelation();

        $object = $this->createDataObject();
        $object->setRelation($relationObject);
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
            $loadedRelation = $object->getRelation();
            $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);
        }
    }

    public function testLocalizedClassAttributes()
    {
        //prepare data object
        $relationObject = $this->loadSingleRelation();

        $object = $this->createDataObject();
        $object->setLrelation($relationObject);
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
            $loadedRelation = $object->getLrelation();
            $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);
        }
    }

    public function testBlockClassAttributes()
    {
        //prepare data object
        $relationObject = $this->loadSingleRelation();

        $object = $this->createDataObject();
        $data = [
            'blockrelation' => new BlockElement('blockrelation', 'manyToOneRelation', $relationObject),
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
            $loadedRelation = $blockItems[0]['blockrelation']->getData();
            $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, $contentShouldBeIncluded);
        }
    }

    public function testLazyBlockClassAttributes()
    {
        //prepare data object
        $relationObject = $this->loadSingleRelation();

        $object = $this->createDataObject();
        $data = [
            'blockrelationLazyLoaded' => new BlockElement('blockrelationLazyLoaded', 'manyToOneRelation', $relationObject),
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
            $loadedRelation = $blockItems[0]['blockrelationLazyLoaded']->getData();
            $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);
        }
    }

    public function testFieldCollectionAttributes()
    {
        //prepare data object
        $relationObject = $this->loadSingleRelation();

        $object = $this->createDataObject();

        $items = new Fieldcollection();
        $item = new Fieldcollection\Data\LazyLoadingTest();
        $item->setRelation($relationObject);
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
                $loadedRelation = $item->getRelation();
                $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');
            }

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);
        }
    }

    public function testFieldCollectionLocalizedAttributes()
    {
        //prepare data object
        $relationObject = $this->loadSingleRelation();

        $object = $this->createDataObject();

        $items = new Fieldcollection();
        $item = new Fieldcollection\Data\LazyLoadingLocalizedTest();
        $item->setLrelation($relationObject);
        $items->add($item);
        $object->setFieldcollection($items);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        //save only non localized field and check if relation loads correctly
        $object = LazyLoading::getById($object->getId(), true);
        $collection = $object->getFieldcollection();
        /** @var Fieldcollection\Data\LazyLoadingLocalizedTest $firstItem */
        $firstItem = $collection->get(0);
        $firstItem->setNormalInput(uniqid());
        $collection->setItems([$firstItem]);

        $object->save();

        $object = LazyLoading::getById($object->getId(), true);

        //load relation and check if relation loads correctly
        $collection = $object->getFieldcollection();
        /** @var Fieldcollection\Data\LazyLoadingLocalizedTest $firstItem */
        $firstItem = $collection->get(0);
        $loadedUntouchedRelation = $firstItem->getLRelation();

        $this->assertEquals($relationObject->getId(), $loadedUntouchedRelation->getId(), 'relations not loaded properly');

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
                $loadedRelation = $item->getLrelation();
                $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');
            }

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);
        }
    }

    public function testBrickAttributes()
    {
        //prepare data object
        $relationObject = $this->loadSingleRelation();

        $object = $this->createDataObject();
        $brick = new LazyLoadingTest($object);
        $brick->setRelation($relationObject);
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
            $loadedRelation = $brick->getRelation();
            $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');

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
        $relation = $relations[0];

        $brick->getLocalizedfields()->setLocalizedValue('lrelation', $relation, 'en');
        $brick->getLocalizedfields()->setLocalizedValue('lrelation', $relation, 'de');

        $object->getBricks()->setLazyLoadingLocalizedTest($brick);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        $object = Concrete::getById($object->getId(), true);
        $this->assertNotNull($object->getBricks()->getLazyLoadingLocalizedTest()->getLRelation('en'));
        $this->assertNotNull($object->getBricks()->getLazyLoadingLocalizedTest()->getLRelation('de'));

        $object = Concrete::getById($object->getId(), true);
        $newRelation = $relations[1];

        $brick = $object->getBricks()->getLazyLoadingLocalizedTest();
        $lFields = $brick->getLocalizedfields();

        // change one language and make sure that it does not affect the other one
        $lFields->setLocalizedValue('lrelations', $newRelation, 'de');
        $object->save();

        $object = Concrete::getById($object->getId(), true);
        $this->assertNotNull($object->getBricks()->getLazyLoadingLocalizedTest()->getLRelation('en'));
        $this->assertNotNull($object->getBricks()->getLazyLoadingLocalizedTest()->getLRelation('de'));

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
            $loadedRelation = $brick->getLocalizedFields()->getLocalizedValue('lrelation');
            $this->assertEquals($relation->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);
        }
    }
}
