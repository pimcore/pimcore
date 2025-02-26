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

namespace Pimcore\Tests\Model\LazyLoading;

use Pimcore;
use Pimcore\Cache;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingTest;

class ManyToManyObjectRelationTest extends AbstractLazyLoadingTest
{
    public function testClassAttributes(): void
    {
        //prepare data object

        $object = $this->createDataObject();
        $object->setObjects($this->loadRelations()->load());
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, ['force' => true]);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //load relation and check if relation loads correctly
            $relationObjects = $object->getObjects();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObjects, $messagePrefix) {
                $relationObjects = $objectCache->getObjects();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');
            });
        }
    }

    public function testLocalizedClassAttributes(): void
    {
        //prepare data object
        $object = $this->createDataObject();
        $object->setLobjects($this->loadRelations()->load());
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, ['force' => true]);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //load relation and check if relation loads correctly
            $relationObjects = $object->getLobjects();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObjects, $messagePrefix) {
                $relationObjects = $objectCache->getLobjects();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');
            });
        }
    }

    public function testBlockClassAttributes(): void
    {
        //prepare data object
        $object = $this->createDataObject();
        $data = [
            'blockobjects' => new BlockElement('blockobjects', 'manyToManyObjectRelation', $this->loadRelations()->load()),
        ];
        $object->setTestBlock([$data]);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, ['force' => true]);

            // inherited data isn't assigned to a property, it's only returned by the getter and therefore doesn't get serialized
            $contentShouldBeIncluded = ($objectType === 'inherited') ? false : true;

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, $contentShouldBeIncluded);

            //load relation and check if relation loads correctly
            $blockItems = $object->getTestBlock();
            $relationObjects = $blockItems[0]['blockobjects']->getData();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, $contentShouldBeIncluded);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObjects, $messagePrefix) {
                $blockItems = $objectCache->getTestBlock();
                $relationObjects = $blockItems[0]['blockobjects']->getData();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');
            });
        }
    }

    public function testLazyBlockClassAttributes(): void
    {
        //prepare data object
        $object = $this->createDataObject();
        $data = [
            'blockobjectsLazyLoaded' => new BlockElement('blockobjectsLazyLoaded', 'manyToManyObjectRelation', $this->loadRelations()->load()),
        ];
        $object->setTestBlockLazyloaded([$data]);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, ['force' => true]);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //load relation and check if relation loads correctly
            $blockItems = $object->getTestBlockLazyloaded();
            $relationObjects = $blockItems[0]['blockobjectsLazyLoaded']->getData();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObjects, $messagePrefix) {
                $blockItems = $objectCache->getTestBlockLazyloaded();
                $relationObjects = $blockItems[0]['blockobjectsLazyLoaded']->getData();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');
            });
        }
    }

    public function testFieldCollectionAttributes(): void
    {
        //prepare data object
        $object = $this->createDataObject();

        $items = new Fieldcollection();
        $item = new Fieldcollection\Data\LazyLoadingTest();
        $item->setObjects($this->loadRelations()->load());
        $items->add($item);
        $object->setFieldcollection($items);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, ['force' => true]);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //load relation and check if relation loads correctly
            $collection = $object->getFieldcollection();
            if ($objectType == 'parent') {
                $item = $collection->get(0);
                $relationObjects = $item->getObjects();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');
            }

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($objectType, $relationObjects, $messagePrefix) {
                $collection = $objectCache->getFieldcollection();
                if ($objectType == 'parent') {
                    $item = $collection->get(0);
                    $relationObjects = $item->getObjects();
                    $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');
                }
            });
        }
    }

    public function testFieldCollectionLocalizedAttributes(): void
    {
        //prepare data object
        $object = $this->createDataObject();

        $items = new Fieldcollection();
        $item = new Fieldcollection\Data\LazyLoadingLocalizedTest();
        $item->setLObjects($this->loadRelations()->load());
        $items->add($item);
        $object->setFieldcollection($items);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        //save only non localized field and check if relation loads correctly
        $object = LazyLoading::getById($object->getId(), ['force' => true]);
        $collection = $object->getFieldcollection();
        /** @var Fieldcollection\Data\LazyLoadingLocalizedTest $firstItem */
        $firstItem = $collection->get(0);
        $firstItem->setNormalInput(uniqid());
        $collection->setItems([$firstItem]);

        $object->save();

        $object = LazyLoading::getById($object->getId(), ['force' => true]);

        //load relation and check if relation loads correctly
        $collection = $object->getFieldcollection();
        /** @var Fieldcollection\Data\LazyLoadingLocalizedTest $firstItem */
        $firstItem = $collection->get(0);
        $loadedRelations = $firstItem->getLObjects();

        $this->assertEquals(self::RELATION_COUNT, count($loadedRelations), 'expected that original relations count is the same as the new one');

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, ['force' => true]);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //load relation and check if relation loads correctly
            $collection = $object->getFieldcollection();
            if ($objectType == 'parent') {
                $item = $collection->get(0);
                $relationObjects = $item->getLObjects();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');
            }

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($objectType, $relationObjects, $messagePrefix) {
                $collection = $objectCache->getFieldcollection();
                if ($objectType == 'parent') {
                    $item = $collection->get(0);
                    $relationObjects = $item->getLObjects();
                    $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');
                }
            });
        }
    }

    public function testBrickAttributes(): void
    {
        //prepare data object
        $object = $this->createDataObject();
        $brick = new LazyLoadingTest($object);
        $brick->setObjects($this->loadRelations()->load());
        $object->getBricks()->setLazyLoadingTest($brick);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, ['force' => true]);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //load relation and check if relation loads correctly
            $brick = $object->getBricks()->getLazyLoadingTest();
            $relationObjects = $brick->getObjects();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObjects, $messagePrefix) {
                $brick = $objectCache->getBricks()->getLazyLoadingTest();
                $relationObjects = $brick->getObjects();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');
            });
        }
    }

    public function testLocalizedBrickAttributes(): void
    {
        //prepare data object
        $object = $this->createDataObject();
        $brick = new LazyLoadingLocalizedTest($object);

        $relations = $this->loadRelations()->load();

        $brick->getLocalizedfields()->setLocalizedValue('lobjects', $relations, 'en');
        $brick->getLocalizedfields()->setLocalizedValue('lobjects', $relations, 'de');

        $object->getBricks()->setLazyLoadingLocalizedTest($brick);
        $object->save();

        $object = Concrete::getById($object->getId(), ['force' => true]);

        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLObjects('en')) > 0);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLObjects('de')) > 0);

        $object = Concrete::getById($object->getId(), ['force' => true]);
        array_pop($relations);

        $brick = $object->getBricks()->getLazyLoadingLocalizedTest();
        $lFields = $brick->getLocalizedfields();
        // change one language and make sure that it does not affect the other one
        $lFields->setLocalizedValue('lobjects', $relations, 'de');
        $object->save();

        $object = Concrete::getById($object->getId(), ['force' => true]);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLObjects('en')) > 0);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLObjects('de')) > 0);

        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            //clear cache and collect garbage
            Cache::clearAll();
            Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, ['force' => true]);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //load relation and check if relation loads correctly
            $brick = $object->getBricks()->getLazyLoadingLocalizedTest();
            $relationObjects = $brick->getLocalizedFields()->getLocalizedValue('lobjects');
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObjects, $messagePrefix) {
                $brick = $objectCache->getBricks()->getLazyLoadingLocalizedTest();
                $relationObjects = $brick->getLocalizedFields()->getLocalizedValue('lobjects');
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');
            });
        }
    }
}
