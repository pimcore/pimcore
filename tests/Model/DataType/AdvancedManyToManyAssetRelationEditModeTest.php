<?php
declare(strict_types=1);

namespace Pimcore\Tests\Model\DataType;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tests\Test\ModelTestCase;

class AdvancedManyToManyAssetRelationEditModeTest extends ModelTestCase
{
    public function testGetDataForEditmodeWithColumns(): void
    {
        $asset = TestHelper::createImageAsset();

        $fd = new DataObject\ClassDefinition\Data\AdvancedManyToManyAssetRelation();
        $fd->setColumns([['position' => 1, 'key' => 'meta1', 'type' => 'text', 'label' => 'Meta 1']]);

        $metaData = new DataObject\Data\ElementMetadata('testField', ['meta1'], $asset);
        $metaData->setMeta1('test-value');

        $result = $fd->getDataForEditmode([$metaData]);

        $this->assertCount(1, $result);
        $this->assertSame($asset->getId(), $result[0]['id']);
        $this->assertArrayHasKey('fullpath', $result[0]);
        $this->assertSame('test-value', $result[0]['meta1']);
        $this->assertArrayHasKey('rowId', $result[0]);
    }

    public function testGetDataForEditmodeWithVisibleFields(): void
    {
        $asset = TestHelper::createImageAsset();
        $asset->addMetadata('altText', 'input', 'my alt text');
        $asset->save();

        $fd = new DataObject\ClassDefinition\Data\AdvancedManyToManyAssetRelation();
        $fd->setVisibleFields('altText');
        $fd->setColumns([['position' => 1, 'key' => 'note', 'type' => 'text', 'label' => 'Note']]);

        $metaData = new DataObject\Data\ElementMetadata('testField', ['note'], $asset);
        $metaData->setNote('a note');

        $result = $fd->getDataForEditmode([$metaData]);

        $this->assertCount(1, $result);
        $this->assertSame('my alt text', $result[0]['altText']);
        $this->assertSame('a note', $result[0]['note']);
    }

    public function testGetDataFromEditmodeReturnsElementMetadata(): void
    {
        $asset = TestHelper::createImageAsset();

        $fd = new DataObject\ClassDefinition\Data\AdvancedManyToManyAssetRelation();
        $fd->setColumns([['position' => 1, 'key' => 'meta1', 'type' => 'text', 'label' => 'Meta 1']]);

        $result = $fd->getDataFromEditmode([
            ['id' => $asset->getId(), 'meta1' => 'from-edit'],
        ]);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(DataObject\Data\ElementMetadata::class, $result[0]);
        $this->assertSame($asset->getId(), $result[0]->getElement()->getId());
        $this->assertSame('from-edit', $result[0]->getMeta1());
    }

    public function testNormalizeDenormalizeRoundtrip(): void
    {
        $asset = TestHelper::createImageAsset();

        $fd = new DataObject\ClassDefinition\Data\AdvancedManyToManyAssetRelation();
        $fd->setColumns([['position' => 1, 'key' => 'meta1', 'type' => 'text', 'label' => 'Meta 1']]);

        $metaData = new DataObject\Data\ElementMetadata('testField', ['meta1'], $asset);
        $metaData->setMeta1('round-trip');

        $normalized = $fd->normalize([$metaData]);
        $this->assertSame('asset', $normalized[0]['element']['type']);
        $this->assertSame($asset->getId(), $normalized[0]['element']['id']);
        $this->assertSame('round-trip', $normalized[0]['data']['meta1']);

        $denormalized = $fd->denormalize($normalized);
        $this->assertCount(1, $denormalized);
        $this->assertSame($asset->getId(), $denormalized[0]->getElement()->getId());
    }
}
