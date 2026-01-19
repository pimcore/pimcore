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
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\LazyLoading;
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
}
