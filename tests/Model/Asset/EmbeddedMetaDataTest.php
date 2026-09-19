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

use Pimcore;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Messenger\AssetUpdateTasksMessage;
use Pimcore\Messenger\Handler\AssetUpdateTasksHandler;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Psr\Log\NullLogger;
use Symfony\Component\Lock\LockFactory;

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

        // the settings are removed as soon as the data is assigned, so that the meta data
        // can already be extracted from the new data before the asset is saved
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertNull($document->getCustomSetting('embeddedMetaData'));

        $document->save();

        $document = Asset::getById($document->getId(), ['force' => true]);
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertNull($document->getCustomSetting('embeddedMetaData'));

        // the meta data is extracted from the new data
        $this->assertSame([], $document->getEmbeddedMetaData(true, false));
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
    }

    public function testEmbeddedMetaDataExtractedBeforeSaveIsPersisted(): void
    {
        // extracted from a new, not yet saved asset
        $document = TestHelper::createDocumentAsset('', $this->getPdfWithoutMetaData(), false);
        $this->assertSame([], $document->getEmbeddedMetaData(true, false));
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $document->save();

        $document = Asset::getById($document->getId(), ['force' => true]);
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame([], $document->getEmbeddedMetaData(false));

        // extracted from new data assigned to an existing asset, before the asset is saved
        $document->setData($this->getPdfWithMetaData());
        $metaData = $document->getEmbeddedMetaData(true, false);
        $this->assertSame('Pimcore Test Suite', $metaData['CreatorTool']);
        $document->save();

        $document = Asset::getById($document->getId(), ['force' => true]);
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame($metaData, $document->getEmbeddedMetaData(false));
    }

    public function testVersionKeepsEmbeddedMetaData(): void
    {
        $document = TestHelper::createDocumentAsset('', $this->getPdfWithMetaData());
        $metaData = $document->getEmbeddedMetaData(true, false);
        $this->assertSame('Pimcore Test Suite', $metaData['CreatorTool']);
        $document->save();

        $version = $document->getLatestVersion(null, true);
        $this->assertNotNull($version);

        // loading the version restores its binary data, which must not invalidate the meta data stored with it
        $versionDocument = $version->loadData();
        $this->assertInstanceOf(Asset\Document::class, $versionDocument);
        $this->assertTrue($versionDocument->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame($metaData, $versionDocument->getCustomSetting('embeddedMetaData'));

        // the same applies when the version is restored
        $versionDocument->save();

        $document = Asset::getById($document->getId(), ['force' => true]);
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame($metaData, $document->getEmbeddedMetaData(false));
    }

    public function testUpdateTasksHandlerExtractsAndPersistsEmbeddedMetaData(): void
    {
        $document = TestHelper::createDocumentAsset('', $this->getPdfWithMetaData());

        // mark the other processing steps of the handler (PDF scan, page count) as already done,
        // so that the embedded meta data extraction is the only reason for the handler to save the asset
        $document->setCustomSetting(Asset\Document::CUSTOM_SETTING_PDF_SCAN_STATUS, Asset\Enum\PdfScanStatus::SAFE->value);
        $document->setCustomSetting('document_page_count', 1);
        $document->save();

        $document = Asset::getById($document->getId(), ['force' => true]);
        $this->assertSame(1, $document->getPageCount());
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertNull($document->getCustomSetting('embeddedMetaData'));

        $container = Pimcore::getContainer();
        $handler = new AssetUpdateTasksHandler(
            new NullLogger(),
            $container->get(LongRunningHelper::class),
            $container->get(LockFactory::class)
        );
        $handler(new AssetUpdateTasksMessage($document->getId()));

        $document = Asset::getById($document->getId(), ['force' => true]);
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame('Pimcore Test Suite', $document->getCustomSetting('embeddedMetaData')['CreatorTool']);
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
