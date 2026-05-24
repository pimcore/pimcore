<?php
declare(strict_types=1);

namespace Pimcore\Tests\Model\DataType;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tests\Test\ModelTestCase;

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
}
