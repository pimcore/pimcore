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

namespace Pimcore\Tests\Model\DataType;

use InvalidArgumentException;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tests\Support\Test\ModelTestCase;

class ManyToManyAssetRelationEditModeTest extends ModelTestCase
{
    public function testGetDataForEditmodeWithoutVisibleFields(): void
    {
        $asset1 = TestHelper::createImageAsset();
        $asset2 = TestHelper::createImageAsset();

        $fd = new DataObject\ClassDefinition\Data\ManyToManyAssetRelation();

        $result = $fd->getDataForEditmode([$asset1, $asset2]);

        $this->assertCount(2, $result);
        $this->assertSame($asset1->getId(), $result[0]['id']);
        $this->assertSame($asset2->getId(), $result[1]['id']);
        $this->assertArrayHasKey('fullpath', $result[0]);
        $this->assertArrayHasKey('type', $result[0]);
    }

    public function testGetDataForEditmodeWithVisibleFields(): void
    {
        $asset = TestHelper::createImageAsset();
        $asset->addMetadata('altText', 'input', 'test alt text');
        $asset->addMetadata('copyright', 'input', 'test copyright');
        $asset->save();

        $fd = new DataObject\ClassDefinition\Data\ManyToManyAssetRelation();
        $fd->setVisibleFields('altText,copyright');

        $result = $fd->getDataForEditmode([$asset]);

        $this->assertCount(1, $result);
        $this->assertSame($asset->getId(), $result[0]['id']);
        $this->assertSame('test alt text', $result[0]['altText']);
        $this->assertSame('test copyright', $result[0]['copyright']);
    }

    public function testGetDataForEditmodeDoesNotOverrideSystemColumns(): void
    {
        $asset = TestHelper::createImageAsset();
        $asset->addMetadata('id', 'input', 'should-not-override');
        $asset->save();

        $fd = new DataObject\ClassDefinition\Data\ManyToManyAssetRelation();
        $fd->setVisibleFields('id');

        $result = $fd->getDataForEditmode([$asset]);

        $this->assertCount(1, $result);
        $this->assertSame($asset->getId(), $result[0]['id']);
    }

    public function testGetDataForEditmodeReadsMetadataCollidingWithAssetGetter(): void
    {
        // Asset\Image::getThumbnail() exists; a visible field named "thumbnail" must still resolve to the
        // metadata value and never invoke the getter (which on Asset\Video would even throw for lack of an argument)
        $asset = TestHelper::createImageAsset();
        $asset->addMetadata('thumbnail', 'input', 'metadata thumbnail value');
        $asset->save();

        $fd = new DataObject\ClassDefinition\Data\ManyToManyAssetRelation();
        $fd->setVisibleFields('thumbnail');

        $result = $fd->getDataForEditmode([$asset]);

        $this->assertCount(1, $result);
        $this->assertSame('metadata thumbnail value', $result[0]['thumbnail']);
    }

    public function testGetDataFromEditmodeReturnsAssets(): void
    {
        $asset = TestHelper::createImageAsset();

        $fd = new DataObject\ClassDefinition\Data\ManyToManyAssetRelation();
        $result = $fd->getDataFromEditmode([['id' => $asset->getId()]]);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Asset::class, $result[0]);
        $this->assertSame($asset->getId(), $result[0]->getId());
    }

    public function testGetDataFromEditmodeReturnsNullForNullInput(): void
    {
        $fd = new DataObject\ClassDefinition\Data\ManyToManyAssetRelation();
        $this->assertNull($fd->getDataFromEditmode(null));
    }

    public function testGetDataFromEditmodeSkipsMissingAssets(): void
    {
        $fd = new DataObject\ClassDefinition\Data\ManyToManyAssetRelation();
        $result = $fd->getDataFromEditmode([['id' => 999999999]]);

        $this->assertCount(0, $result);
    }

    public function testAddListingFilterAcceptsAssetAssetIdAndArray(): void
    {
        $asset = TestHelper::createImageAsset();

        $fd = new DataObject\ClassDefinition\Data\ManyToManyAssetRelation();
        $fd->setName('assetRelations');

        foreach ([$asset, $asset->getId(), ['id' => $asset->getId()], ['id' => $asset->getId(), 'type' => 'asset']] as $data) {
            $listing = new DataObject\Listing();
            $fd->addListingFilter($listing, $data);

            $conditionParams = $listing->getConditionParams();
            $this->assertArrayHasKey('(`assetRelations` LIKE ?)', $conditionParams);
            $this->assertSame('%,' . $asset->getId() . ',%', $conditionParams['(`assetRelations` LIKE ?)']['value']);
        }
    }

    public function testAddListingFilterRejectsNonAssetElement(): void
    {
        $object = TestHelper::createEmptyObject();

        $fd = new DataObject\ClassDefinition\Data\ManyToManyAssetRelation();
        $fd->setName('assetRelations');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does only support assets, object given');

        $fd->addListingFilter(new DataObject\Listing(), $object);
    }

    public function testAddListingFilterRejectsNonAssetTypeInArray(): void
    {
        $fd = new DataObject\ClassDefinition\Data\ManyToManyAssetRelation();
        $fd->setName('assetRelations');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does only support assets, type "object" given');

        $fd->addListingFilter(new DataObject\Listing(), ['id' => 1, 'type' => 'object']);
    }
}
