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

namespace Pimcore\Tests\Model\Asset;

use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Embedded meta data of assets (see EmbeddedMetaDataTrait) is bound to the binary data of the asset,
 * so it has to be invalidated whenever the data changes, regardless of the (old or new) asset type.
 *
 * @group model.asset.asset
 */
class EmbeddedMetaDataTest extends ModelTestCase
{
    private function getPdfWithMetaData(): string
    {
        return file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf'));
    }

    private function getPdfWithoutMetaData(): string
    {
        return file_get_contents(TestHelper::resolveFilePath('assets/document/sonnenblume.pdf'));
    }

    public function testDocumentEmbeddedMetaDataIsPersisted(): void
    {
        $document = TestHelper::createDocumentAsset('', $this->getPdfWithMetaData());
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));

        $metaData = $document->getEmbeddedMetaData(true, false);
        $this->assertSame('Pimcore Test Suite', $metaData['CreatorTool']);
        $this->assertStringContainsString('Embedded Meta Data Test', $metaData['title']);
        $document->save();

        $document = Asset::getById($document->getId(), ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $document);
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame($metaData, $document->getEmbeddedMetaData(false));
    }

    public function testEmbeddedMetaDataIsResetWhenDataChanges(): void
    {
        $document = TestHelper::createDocumentAsset('', $this->getPdfWithMetaData());
        $document->getEmbeddedMetaData(true, false);
        $document->save();

        $document = Asset::getById($document->getId(), ['force' => true]);
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame('Pimcore Test Suite', $document->getEmbeddedMetaData(false)['CreatorTool']);

        $document->setData($this->getPdfWithoutMetaData());
        $document->save();

        $document = Asset::getById($document->getId(), ['force' => true]);
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertNull($document->getCustomSetting('embeddedMetaData'));

        // the meta data is extracted from the new data
        $this->assertSame([], $document->getEmbeddedMetaData(true, false));
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
    }

    public function testEmbeddedMetaDataIsResetWhenTypeChanges(): void
    {
        $image = TestHelper::createImageAsset();
        $image->getEmbeddedMetaData(true, false);
        $image->save();

        $image = Asset::getById($image->getId(), ['force' => true]);
        $this->assertInstanceOf(Asset\Image::class, $image);
        $this->assertTrue($image->getCustomSetting('embeddedMetaDataExtracted'));

        // replace the image with a PDF, which turns the asset into a document
        $image->setData($this->getPdfWithMetaData());
        $image->setFilename(pathinfo($image->getFilename(), PATHINFO_FILENAME) . '.pdf');
        $image->save();

        $document = Asset::getById($image->getId(), ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $document);
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertNull($document->getCustomSetting('embeddedMetaData'));

        // the meta data is extracted from the new data
        $this->assertSame('Pimcore Test Suite', $document->getEmbeddedMetaData(true, false)['CreatorTool']);
    }
}
