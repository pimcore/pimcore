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
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingTest;

class AdvancedManyToManyObjectRelationTest extends AbstractLazyLoadingTest
{
    protected function loadMetadataRelations(string $fieldname, string $metaKey = 'metadata'): array
    {
        $relations = $this->loadRelations();

        $metaDataList = [];
        foreach ($relations as $relation) {
            $objectMetadata = new ObjectMetadata($fieldname, [$metaKey], $relation);
            $setter = 'set' . ucfirst($metaKey);
            $objectMetadata->$setter('some-metadata');
            $metaDataList[] = $objectMetadata;
        }

        return $metaDataList;
    }

    protected function checkSerialization(LazyLoading $object, string $messagePrefix, bool $contentShouldBeIncluded = false): void
    {
        parent::checkSerialization($object, $messagePrefix, false);
        $serializedString = serialize($object);
        $this->checkSerializedStringForNeedle($serializedString, 'some-metadata', $contentShouldBeIncluded, $messagePrefix);
    }

    public function testClassAttributes(): void
    {
        //prepare data object

        $object = $this->createDataObject();
        $object->setAdvancedObjects($this->loadMetadataRelations('advancedObjects', 'metadataUpper'));
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
            $relationObjects = $object->getAdvancedObjects();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //check if relation meta data is there
            $this->assertEquals('some-metadata', $relationObjects[2]->getMetaDATAUpper(), $messagePrefix . 'relations metadata not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObjects, $messagePrefix) {
                //load relation and check if relation loads correctly
                $relationObjects = $objectCache->getAdvancedObjects();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

                //check if relation meta data is there
                $this->assertEquals('some-metadata', $relationObjects[2]->getMetaDATAUpper(), $messagePrefix . 'relations metadata not loaded properly');
            });
        }
    }

    public function testDirtyFlag(): void
    {
        $object = $this->createDataObject();

        $relatedObjects = $this->loadMetadataRelations('advancedObjects', 'metadataUpper');

        $object->setAdvancedObjects($relatedObjects);
        $object->save();
        $this->assertFalse($object->isFieldDirty('advancedObjects'), 'Advanced relation must not be dirty after saving');

        Cache\RuntimeCache::clear();
        $object = LazyLoading::getByPath('/lazy1');
        $this->assertFalse($object->isFieldDirty('advancedObjects'), 'Advanced relation must not be dirty directly after loading');

        $object->getAdvancedObjects()[0]->setMetadataUpper('some-other-metadata');
        $this->assertTrue($object->isFieldDirty('advancedObjects'), 'Advanced relation must be dirty after changing a metadata field');
    }

    public function testLocalizedClassAttributes(): void
    {
        //prepare data object
        $object = $this->createDataObject();
        $object->setLadvancedObjects($this->loadMetadataRelations('ladvancedObjects'));
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
            $relationObjects = $object->getLadvancedObjects();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //check if relation meta data is there
            $this->assertEquals('some-metadata', $relationObjects[2]->getMetaData(), $messagePrefix . 'relations metadata not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObjects, $messagePrefix) {
                //load relation and check if relation loads correctly
                $relationObjects = $objectCache->getLadvancedObjects();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

                //check if relation meta data is there
                $this->assertEquals('some-metadata', $relationObjects[2]->getMetaData(), $messagePrefix . 'relations metadata not loaded properly');
            });
        }
    }

    public function testBlockClassAttributes(): void
    {
        //prepare data object
        $object = $this->createDataObject();
        $data = [
            'blockadvancedObjects' => new BlockElement('blockadvancedObjects', 'advancedManyToManyObjectRelation', $this->loadMetadataRelations('blockadvancedObjects')),
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

            $contentShouldBeIncluded = ($objectType === 'inherited') ? false : true;

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, $contentShouldBeIncluded);

            //load relation and check if relation loads correctly
            $blockItems = $object->getTestBlock();
            $relationObjects = $blockItems[0]['blockadvancedObjects']->getData();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //check if relation meta data is there
            $this->assertEquals('some-metadata', $relationObjects[2]->getMetaData(), $messagePrefix . 'relations metadata not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, $contentShouldBeIncluded);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObjects, $messagePrefix) {
                //load relation and check if relation loads correctly
                $blockItems = $objectCache->getTestBlock();
                $relationObjects = $blockItems[0]['blockadvancedObjects']->getData();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

                //check if relation meta data is there
                $this->assertEquals('some-metadata', $relationObjects[2]->getMetaData(), $messagePrefix . 'relations metadata not loaded properly');
            });
        }
    }

    public function testLazyBlockClassAttributes(): void
    {
        //prepare data object
        $object = $this->createDataObject();
        $data = [
            'blockadvancedObjectsLazyLoaded' => new BlockElement('blockadvancedObjectsLazyLoaded', 'advancedManyToManyObjectRelation', $this->loadMetadataRelations('blockadvancedObjectsLazyLoaded')),
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
            $relationObjects = $blockItems[0]['blockadvancedObjectsLazyLoaded']->getData();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //check if relation meta data is there
            $this->assertEquals('some-metadata', $relationObjects[2]->getMetaData(), $messagePrefix . 'relations metadata not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObjects, $messagePrefix) {
                //load relation and check if relation loads correctly
                $blockItems = $objectCache->getTestBlockLazyloaded();
                $relationObjects = $blockItems[0]['blockadvancedObjectsLazyLoaded']->getData();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

                //check if relation meta data is there
                $this->assertEquals('some-metadata', $relationObjects[2]->getMetaData(), $messagePrefix . 'relations metadata not loaded properly');
            });
        }
    }

    public function testFieldCollectionAttributes(): void
    {
        //prepare data object
        $object = $this->createDataObject();

        $items = new Fieldcollection();
        $item = new Fieldcollection\Data\LazyLoadingTest();
        $item->setAdvancedObjects($this->loadMetadataRelations('advancedObjects', 'metadataUpper'));
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
                $relationObjects = $item->getAdvancedObjects();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

                //check if relation meta data is there
                $this->assertEquals('some-metadata', $relationObjects[2]->getMetaDATAUpper(), $messagePrefix . 'relations metadata not loaded properly');
            }

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($objectType, $relationObjects, $messagePrefix) {
                //load relation and check if relation loads correctly
                $collection = $objectCache->getFieldcollection();
                if ($objectType == 'parent') {
                    $item = $collection->get(0);
                    $relationObjects = $item->getAdvancedObjects();
                    $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

                    //check if relation meta data is there
                    $this->assertEquals('some-metadata', $relationObjects[2]->getMetaDATAUpper(), $messagePrefix . 'relations metadata not loaded properly');
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
        $item->setLadvancedObjects($this->loadMetadataRelations('ladvancedObjects'));
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
                $relationObjects = $item->getLAdvancedObjects();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

                //check if relation meta data is there
                $this->assertEquals('some-metadata', $relationObjects[2]->getMetaData(), $messagePrefix . 'relations metadata not loaded properly');
            }

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($objectType, $relationObjects, $messagePrefix) {
                //load relation and check if relation loads correctly
                $collection = $objectCache->getFieldcollection();
                if ($objectType == 'parent') {
                    $item = $collection->get(0);
                    $relationObjects = $item->getLAdvancedObjects();
                    $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

                    //check if relation meta data is there
                    $this->assertEquals('some-metadata', $relationObjects[2]->getMetaData(), $messagePrefix . 'relations metadata not loaded properly');
                }
            });
        }
    }

    public function testBrickAttributes(): void
    {
        //prepare data object
        $object = $this->createDataObject();
        $brick = new LazyLoadingTest($object);
        $brick->setAdvancedObjects($this->loadMetadataRelations('advancedObjects', 'metadataUpper'));
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
            $relationObjects = $brick->getAdvancedObjects();
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //check if relation meta data is there
            $this->assertEquals('some-metadata', $relationObjects[2]->getMetaDATAUpper(), $messagePrefix . 'relations metadata not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObjects, $messagePrefix) {
                //load relation and check if relation loads correctly
                $brick = $objectCache->getBricks()->getLazyLoadingTest();
                $relationObjects = $brick->getAdvancedObjects();
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

                //check if relation meta data is there
                $this->assertEquals('some-metadata', $relationObjects[2]->getMetaDATAUpper(), $messagePrefix . 'relations metadata not loaded properly');
            });
        }
    }

    public function testLocalizedBrickAttributes(): void
    {
        //prepare data object
        $object = $this->createDataObject();
        $relations = $this->loadMetadataRelations('ladvancedObjects');
        $brick = new LazyLoadingLocalizedTest($object);

        $brick->getLocalizedfields()->setLocalizedValue('ladvancedObjects', $relations, 'en');
        $brick->getLocalizedfields()->setLocalizedValue('ladvancedObjects', $relations, 'de');

        $object->getBricks()->setLazyLoadingLocalizedTest($brick);
        $object->save();

        $object = Concrete::getById($object->getId(), ['force' => true]);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLadvancedObjects('en')) > 0);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLadvancedObjects('de')) > 0);

        $object = Concrete::getById($object->getId(), ['force' => true]);
        array_pop($relations);

        $brick = $object->getBricks()->getLazyLoadingLocalizedTest();
        $lFields = $brick->getLocalizedfields();
        // change one language and make sure that it does not affect the other one
        $lFields->setLocalizedValue('ladvancedObjects', $relations, 'de');
        $object->save();

        $object = Concrete::getById($object->getId(), ['force' => true]);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLadvancedObjects('en')) > 0);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLadvancedObjects('de')) > 0);

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
            $relationObjects = $brick->getLocalizedFields()->getLocalizedValue('ladvancedObjects');
            $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

            //check if relation meta data is there
            $this->assertEquals('some-metadata', $relationObjects[2]->getMetaData(), $messagePrefix . 'relations metadata not loaded properly');

            //serialize data object and check for (not) wanted content in serialized string
            $this->checkSerialization($object, $messagePrefix, false);

            //check if data also loaded correctly when loaded from cache
            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($relationObjects, $messagePrefix) {
                //load relation and check if relation loads correctly
                $brick = $objectCache->getBricks()->getLazyLoadingLocalizedTest();
                $relationObjects = $brick->getLocalizedFields()->getLocalizedValue('ladvancedObjects');
                $this->assertEquals(self::RELATION_COUNT, count($relationObjects), $messagePrefix . 'relations not loaded properly');

                //check if relation meta data is there
                $this->assertEquals('some-metadata', $relationObjects[2]->getMetaData(), $messagePrefix . 'relations metadata not loaded properly');
            });
        }
    }
}
