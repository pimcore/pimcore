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

class ManyToOneRelationTest extends AbstractLazyLoadingTest
{
    public function testClassAttributes(): void
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
            Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, ['force' => true]);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //load relation and check if relation loads correctly
            $loadedRelation = $object->getRelation();
            $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObject, $messagePrefix) {
                //load relation and check if relation loads correctly
                $loadedRelation = $objectCache->getRelation();
                $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');
            });
        }
    }

    public function testLocalizedClassAttributes(): void
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
            Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, ['force' => true]);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //load relation and check if relation loads correctly
            $loadedRelation = $object->getLrelation();
            $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObject, $messagePrefix) {
                $loadedRelation = $objectCache->getLrelation();
                $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');
            });
        }
    }

    public function testBlockClassAttributes(): void
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
            Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, ['force' => true]);

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

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObject, $messagePrefix) {
                $blockItems = $objectCache->getTestBlock();
                $loadedRelation = $blockItems[0]['blockrelation']->getData();
                $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');
            });
        }
    }

    public function testLazyBlockClassAttributes(): void
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
            Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, ['force' => true]);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //load relation and check if relation loads correctly
            $blockItems = $object->getTestBlockLazyloaded();
            $loadedRelation = $blockItems[0]['blockrelationLazyLoaded']->getData();
            $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObject, $messagePrefix) {
                //load relation and check if relation loads correctly
                $blockItems = $objectCache->getTestBlockLazyloaded();
                $loadedRelation = $blockItems[0]['blockrelationLazyLoaded']->getData();
                $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');
            });
        }
    }

    public function testFieldCollectionAttributes(): void
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
            Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, ['force' => true]);

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

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($objectType, $relationObject, $messagePrefix) {
                $collection = $objectCache->getFieldcollection();
                if ($objectType == 'parent') {
                    $item = $collection->get(0);
                    $loadedRelation = $item->getRelation();
                    $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');
                }
            });
        }
    }

    public function testFieldCollectionLocalizedAttributes(): void
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
        $loadedUntouchedRelation = $firstItem->getLRelation();

        $this->assertEquals($relationObject->getId(), $loadedUntouchedRelation->getId(), 'relations not loaded properly');

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
                $loadedRelation = $item->getLrelation();
                $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');
            }

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($objectType, $relationObject, $messagePrefix) {
                $collection = $objectCache->getFieldcollection();
                if ($objectType == 'parent') {
                    $item = $collection->get(0);
                    $loadedRelation = $item->getLrelation();
                    $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');
                }
            });
        }
    }

    public function testBrickAttributes(): void
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
            Pimcore::collectGarbage();

            //reload data object from database
            $object = LazyLoading::getById($id, ['force' => true]);

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //load relation and check if relation loads correctly
            $brick = $object->getBricks()->getLazyLoadingTest();
            $loadedRelation = $brick->getRelation();
            $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObject, $messagePrefix) {
                //load relation and check if relation loads correctly
                $brick = $objectCache->getBricks()->getLazyLoadingTest();
                $loadedRelation = $brick->getRelation();
                $this->assertEquals($relationObject->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');
            });
        }
    }

    public function testLocalizedBrickAttributes(): void
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

        $object = Concrete::getById($object->getId(), ['force' => true]);
        $this->assertNotNull($object->getBricks()->getLazyLoadingLocalizedTest()->getLRelation('en'));
        $this->assertNotNull($object->getBricks()->getLazyLoadingLocalizedTest()->getLRelation('de'));

        $object = Concrete::getById($object->getId(), ['force' => true]);
        $newRelation = $relations[1];

        $brick = $object->getBricks()->getLazyLoadingLocalizedTest();
        $lFields = $brick->getLocalizedfields();

        // change one language and make sure that it does not affect the other one
        $lFields->setLocalizedValue('lrelations', $newRelation, 'de');
        $object->save();

        $object = Concrete::getById($object->getId(), ['force' => true]);
        $this->assertNotNull($object->getBricks()->getLazyLoadingLocalizedTest()->getLRelation('en'));
        $this->assertNotNull($object->getBricks()->getLazyLoadingLocalizedTest()->getLRelation('de'));

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
            $loadedRelation = $brick->getLocalizedFields()->getLocalizedValue('lrelation');
            $this->assertEquals($relation->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relation, $messagePrefix) {
                //load relation and check if relation loads correctly
                $brick = $objectCache->getBricks()->getLazyLoadingLocalizedTest();
                $loadedRelation = $brick->getLocalizedFields()->getLocalizedValue('lrelation');
                $this->assertEquals($relation->getId(), $loadedRelation->getId(), $messagePrefix . 'relations not loaded properly');
            });
        }
    }
}
