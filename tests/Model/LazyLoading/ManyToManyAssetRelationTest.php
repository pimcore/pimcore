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
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Tests\Support\Util\TestHelper;

class ManyToManyAssetRelationTest extends AbstractLazyLoadingTest
{
    /**
     * @return Asset[]
     */
    private function createAssets(): array
    {
        $assets = [];
        for ($i = 0; $i < self::RELATION_COUNT; $i++) {
            $assets[] = TestHelper::createImageAsset('lazy-asset-');
        }

        return $assets;
    }

    public function testClassAttributes(): void
    {
        $object = $this->createDataObject();
        $object->setAssetRelations($this->createAssets());
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            Cache::clearAll();
            Pimcore::collectGarbage();

            $object = LazyLoading::getById($id, ['force' => true]);

            $this->checkSerialization($object, $messagePrefix);

            $relationAssets = $object->getAssetRelations();
            $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');

            $this->checkSerialization($object, $messagePrefix);

            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($messagePrefix) {
                $relationAssets = $objectCache->getAssetRelations();
                $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
            });
        }
    }

    public function testLocalizedClassAttributes(): void
    {
        $object = $this->createDataObject();
        $object->setLassetRelations($this->createAssets());
        $object->save();
        $parentId = $object->getId();
        $childId = $this->createChildDataObject($object)->getId();

        foreach (['parent' => $parentId, 'inherited' => $childId] as $objectType => $id) {
            $messagePrefix = "Testing object-type $objectType: ";

            Cache::clearAll();
            Pimcore::collectGarbage();

            $object = LazyLoading::getById($id, ['force' => true]);

            $this->checkSerialization($object, $messagePrefix);

            $relationAssets = $object->getLassetRelations();
            $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');

            $this->checkSerialization($object, $messagePrefix);

            $this->forceSavingAndLoadingFromCache($object, function ($objectCache) use ($messagePrefix) {
                $relationAssets = $objectCache->getLassetRelations();
                $this->assertEquals(self::RELATION_COUNT, count($relationAssets), $messagePrefix . 'asset relations not loaded properly');
            });
        }
    }
}
