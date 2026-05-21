<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Model\LazyLoading;

use Pimcore;
use Pimcore\Cache;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingTest;
use Pimcore\Tests\Support\Util\TestHelper;

class AdvancedManyToManyAssetRelationTest extends AbstractLazyLoadingTest
{
    private array $assets = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assets = $this->createAssets();
    }

    private function createAssets(): array
    {
        $assets = [];
        for ($i = 0; $i < self::RELATION_COUNT; $i++) {
            $assets[] = TestHelper::createImageAsset('lazy-asset-');
        }

        return $assets;
    }

    /**
     * @return ElementMetadata[]
     */
    protected function loadMetadataAssets(string $fieldname, string $metaKey = 'metadata'): array
    {
        $metaDataList = [];
        foreach ($this->assets as $asset) {
            $assetMetadata = new ElementMetadata($fieldname, [$metaKey], $asset);
            $setter = 'set' . ucfirst($metaKey);
            $assetMetadata->$setter('some-metadata');
            $metaDataList[] = $assetMetadata;
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
        $object = $this->createDataObject();
        $object->setAdvancedAssetRelations($this->loadMetadataAssets('advancedAssetRelations'));
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            Cache::clearAll();
            Pimcore::collectGarbage();

            $object = LazyLoading::getById($id, ['force' => true]);

            $this->checkSerialization($object, $messagePrefix);

            $relationAssets = $object->getAdvancedAssetRelations();
            $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
            $this->assertEquals('some-metadata', $relationAssets[2]->getMetadata(), $messagePrefix . 'asset relation metadata not loaded properly');

            $this->checkSerialization($object, $messagePrefix);

            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($messagePrefix) {
                $relationAssets = $objectCache->getAdvancedAssetRelations();
                $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
                $this->assertEquals('some-metadata', $relationAssets[2]->getMetadata(), $messagePrefix . 'asset relation metadata not loaded properly');
            });
        }
    }

    public function testDirtyFlag(): void
    {
        $object = $this->createDataObject();

        $relations = $this->loadMetadataAssets('advancedAssetRelations');

        $object->setAdvancedAssetRelations($relations);
        $object->save();
        $this->assertFalse($object->isFieldDirty('advancedAssetRelations'), 'Advanced asset relation must not be dirty after saving');

        Cache\RuntimeCache::clear();
        $object = LazyLoading::getByPath('/lazy1');
        $this->assertFalse($object->isFieldDirty('advancedAssetRelations'), 'Advanced asset relation must not be dirty directly after loading');

        $object->getAdvancedAssetRelations()[0]->setMetadata('some-other-metadata');
        $this->assertTrue($object->isFieldDirty('advancedAssetRelations'), 'Advanced asset relation must be dirty after changing a metadata field');
    }

    public function testLocalizedClassAttributes(): void
    {
        $object = $this->createDataObject();
        $object->setLadvancedAssetRelations($this->loadMetadataAssets('ladvancedAssetRelations'));
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            Cache::clearAll();
            Pimcore::collectGarbage();

            $object = LazyLoading::getById($id, ['force' => true]);

            $this->checkSerialization($object, $messagePrefix);

            $relationAssets = $object->getLadvancedAssetRelations();
            $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
            $this->assertEquals('some-metadata', $relationAssets[2]->getMetadata(), $messagePrefix . 'asset relation metadata not loaded properly');

            $this->checkSerialization($object, $messagePrefix);

            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($messagePrefix) {
                $relationAssets = $objectCache->getLadvancedAssetRelations();
                $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
                $this->assertEquals('some-metadata', $relationAssets[2]->getMetadata(), $messagePrefix . 'asset relation metadata not loaded properly');
            });
        }
    }

    public function testBlockClassAttributes(): void
    {
        $object = $this->createDataObject();
        $data = [
            'blockadvancedAssetRelations' => new BlockElement('blockadvancedAssetRelations', 'advancedManyToManyAssetRelation', $this->loadMetadataAssets('blockadvancedAssetRelations')),
        ];
        $object->setTestBlock([$data]);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            Cache::clearAll();
            Pimcore::collectGarbage();

            $object = LazyLoading::getById($id, ['force' => true]);

            $contentShouldBeIncluded = $objectType !== 'inherited';

            $this->checkSerialization($object, $messagePrefix, $contentShouldBeIncluded);

            $blockItems = $object->getTestBlock();
            $relationAssets = $blockItems[0]['blockadvancedAssetRelations']->getData();
            $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
            $this->assertEquals('some-metadata', $relationAssets[2]->getMetadata(), $messagePrefix . 'asset relation metadata not loaded properly');

            $this->checkSerialization($object, $messagePrefix, $contentShouldBeIncluded);

            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($messagePrefix) {
                $blockItems = $objectCache->getTestBlock();
                $relationAssets = $blockItems[0]['blockadvancedAssetRelations']->getData();
                $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
                $this->assertEquals('some-metadata', $relationAssets[2]->getMetadata(), $messagePrefix . 'asset relation metadata not loaded properly');
            });
        }
    }

    public function testLazyBlockClassAttributes(): void
    {
        $object = $this->createDataObject();
        $data = [
            'blockadvancedAssetRelationsLazyLoaded' => new BlockElement('blockadvancedAssetRelationsLazyLoaded', 'advancedManyToManyAssetRelation', $this->loadMetadataAssets('blockadvancedAssetRelationsLazyLoaded')),
        ];
        $object->setTestBlockLazyloaded([$data]);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            Cache::clearAll();
            Pimcore::collectGarbage();

            $object = LazyLoading::getById($id, ['force' => true]);

            $this->checkSerialization($object, $messagePrefix);

            $blockItems = $object->getTestBlockLazyloaded();
            $relationAssets = $blockItems[0]['blockadvancedAssetRelationsLazyLoaded']->getData();
            $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
            $this->assertEquals('some-metadata', $relationAssets[2]->getMetadata(), $messagePrefix . 'asset relation metadata not loaded properly');

            $this->checkSerialization($object, $messagePrefix);

            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($messagePrefix) {
                $blockItems = $objectCache->getTestBlockLazyloaded();
                $relationAssets = $blockItems[0]['blockadvancedAssetRelationsLazyLoaded']->getData();
                $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
                $this->assertEquals('some-metadata', $relationAssets[2]->getMetadata(), $messagePrefix . 'asset relation metadata not loaded properly');
            });
        }
    }

    public function testFieldCollectionAttributes(): void
    {
        $object = $this->createDataObject();

        $items = new Fieldcollection();
        $item = new Fieldcollection\Data\LazyLoadingTest();
        $item->setAdvancedAssetRelations($this->loadMetadataAssets('advancedAssetRelations', 'metadataUpper'));
        $items->add($item);
        $object->setFieldcollection($items);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            Cache::clearAll();
            Pimcore::collectGarbage();

            $object = LazyLoading::getById($id, ['force' => true]);

            $this->checkSerialization($object, $messagePrefix, false);

            $collection = $object->getFieldcollection();
            if ($objectType === 'parent') {
                $item = $collection->get(0);
                $relationAssets = $item->getAdvancedAssetRelations();
                $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
                $this->assertEquals('some-metadata', $relationAssets[2]->getMetadataUpper(), $messagePrefix . 'asset relation metadata not loaded properly');
            }

            $this->checkSerialization($object, $messagePrefix, false);

            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($objectType, $messagePrefix) {
                $collection = $objectCache->getFieldcollection();
                if ($objectType === 'parent') {
                    $item = $collection->get(0);
                    $relationAssets = $item->getAdvancedAssetRelations();
                    $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
                    $this->assertEquals('some-metadata', $relationAssets[2]->getMetadataUpper(), $messagePrefix . 'asset relation metadata not loaded properly');
                }
            });
        }
    }

    public function testFieldCollectionLocalizedAttributes(): void
    {
        $object = $this->createDataObject();

        $items = new Fieldcollection();
        $item = new Fieldcollection\Data\LazyLoadingLocalizedTest();
        $item->setLadvancedAssetRelations($this->loadMetadataAssets('ladvancedAssetRelations'));
        $items->add($item);
        $object->setFieldcollection($items);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            Cache::clearAll();
            Pimcore::collectGarbage();

            $object = LazyLoading::getById($id, ['force' => true]);

            $this->checkSerialization($object, $messagePrefix, false);

            $collection = $object->getFieldcollection();
            if ($objectType === 'parent') {
                $item = $collection->get(0);
                $relationAssets = $item->getLadvancedAssetRelations();
                $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
                $this->assertEquals('some-metadata', $relationAssets[2]->getMetadata(), $messagePrefix . 'asset relation metadata not loaded properly');
            }

            $this->checkSerialization($object, $messagePrefix, false);

            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($objectType, $messagePrefix) {
                $collection = $objectCache->getFieldcollection();
                if ($objectType === 'parent') {
                    $item = $collection->get(0);
                    $relationAssets = $item->getLadvancedAssetRelations();
                    $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
                    $this->assertEquals('some-metadata', $relationAssets[2]->getMetadata(), $messagePrefix . 'asset relation metadata not loaded properly');
                }
            });
        }
    }

    public function testBrickAttributes(): void
    {
        $object = $this->createDataObject();
        $brick = new LazyLoadingTest($object);
        $brick->setAdvancedAssetRelations($this->loadMetadataAssets('advancedAssetRelations', 'metadataUpper'));
        $object->getBricks()->setLazyLoadingTest($brick);
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            Cache::clearAll();
            Pimcore::collectGarbage();

            $object = LazyLoading::getById($id, ['force' => true]);

            $this->checkSerialization($object, $messagePrefix, false);

            $brick = $object->getBricks()->getLazyLoadingTest();
            $relationAssets = $brick->getAdvancedAssetRelations();
            $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
            $this->assertEquals('some-metadata', $relationAssets[2]->getMetadataUpper(), $messagePrefix . 'asset relation metadata not loaded properly');

            $this->checkSerialization($object, $messagePrefix, false);

            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($messagePrefix) {
                $brick = $objectCache->getBricks()->getLazyLoadingTest();
                $relationAssets = $brick->getAdvancedAssetRelations();
                $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
                $this->assertEquals('some-metadata', $relationAssets[2]->getMetadataUpper(), $messagePrefix . 'asset relation metadata not loaded properly');
            });
        }
    }

    public function testLocalizedBrickAttributes(): void
    {
        $object = $this->createDataObject();
        $relations = $this->loadMetadataAssets('ladvancedAssetRelations');
        $brick = new LazyLoadingLocalizedTest($object);

        $brick->getLocalizedfields()->setLocalizedValue('ladvancedAssetRelations', $relations, 'en');
        $brick->getLocalizedfields()->setLocalizedValue('ladvancedAssetRelations', $relations, 'de');

        $object->getBricks()->setLazyLoadingLocalizedTest($brick);
        $object->save();

        $object = Concrete::getById($object->getId(), ['force' => true]);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLadvancedAssetRelations('en')) > 0);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLadvancedAssetRelations('de')) > 0);

        $object = Concrete::getById($object->getId(), ['force' => true]);
        array_pop($relations);

        $brick = $object->getBricks()->getLazyLoadingLocalizedTest();
        $lFields = $brick->getLocalizedfields();
        // change one language and make sure that it does not affect the other one
        $lFields->setLocalizedValue('ladvancedAssetRelations', $relations, 'de');
        $object->save();

        $object = Concrete::getById($object->getId(), ['force' => true]);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLadvancedAssetRelations('en')) > 0);
        $this->assertTrue(count($object->getBricks()->getLazyLoadingLocalizedTest()->getLadvancedAssetRelations('de')) > 0);

        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            Cache::clearAll();
            Pimcore::collectGarbage();

            $object = LazyLoading::getById($id, ['force' => true]);

            $this->checkSerialization($object, $messagePrefix, false);

            $brick = $object->getBricks()->getLazyLoadingLocalizedTest();
            $relationAssets = $brick->getLocalizedFields()->getLocalizedValue('ladvancedAssetRelations');
            $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
            $this->assertEquals('some-metadata', $relationAssets[2]->getMetadata(), $messagePrefix . 'asset relation metadata not loaded properly');

            $this->checkSerialization($object, $messagePrefix, false);

            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($messagePrefix) {
                $brick = $objectCache->getBricks()->getLazyLoadingLocalizedTest();
                $relationAssets = $brick->getLocalizedFields()->getLocalizedValue('ladvancedAssetRelations');
                $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
                $this->assertEquals('some-metadata', $relationAssets[2]->getMetadata(), $messagePrefix . 'asset relation metadata not loaded properly');
            });
        }
    }
}
