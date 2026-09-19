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
use Pimcore\Model\Asset;
use Pimcore\Model\Version\Adapter\VersionStorageAdapterInterface;
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
        // assertEquals() instead of assertSame(): MySQL JSON columns do not preserve the key order of the
        // custom settings, so the arrays are compared without considering the order of the keys
        $this->assertEquals($metaData, $document->getEmbeddedMetaData(false));
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

        $queueSize = TestHelper::getAssetUpdateTaskQueueSize();
        $document->save();
        // replaced data is processed again (in contrast to restored data)
        $this->assertSame($queueSize + 1, TestHelper::getAssetUpdateTaskQueueSize());

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
        $this->assertEquals($metaData, $document->getEmbeddedMetaData(false));
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
        $this->assertEquals($metaData, $versionDocument->getCustomSetting('embeddedMetaData'));

        // the same applies when the version is restored
        $versionDocument->save();

        $document = Asset::getById($document->getId(), ['force' => true]);
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertEquals($metaData, $document->getEmbeddedMetaData(false));
    }

    /**
     * Restoring a version assigns the binary data of the version, which must not invalidate the custom settings
     * derived from this data (page count, dimensions, ...), as the subtypes do when the data is replaced
     */
    public function testVersionRestoreKeepsDerivedCustomSettings(): void
    {
        $assets = [
            [TestHelper::createDocumentAsset(), [
                'document_page_count' => 3,
                Asset\Document::CUSTOM_SETTING_PDF_SCAN_STATUS => Asset\Enum\PdfScanStatus::SAFE->value,
            ]],
            [TestHelper::createImageAsset(), [
                'imageWidth' => 1024,
                'imageHeight' => 768,
                'imageDimensionsCalculated' => true,
            ]],
            [TestHelper::createVideoAsset(), [
                'duration' => 12.5,
                'videoWidth' => 640,
                'videoHeight' => 480,
            ]],
        ];

        foreach ($assets as [$asset, $derivedSettings]) {
            foreach ($derivedSettings as $key => $value) {
                $asset->setCustomSetting($key, $value);
            }
            $asset->save();

            $version = $asset->getLatestVersion(null, true);
            $this->assertNotNull($version);

            $queueSize = TestHelper::getAssetUpdateTaskQueueSize();
            $version->loadData()->save();
            // the restored data must not be processed again, as this could discard the restored derived settings
            $this->assertSame($queueSize, TestHelper::getAssetUpdateTaskQueueSize(), get_class($asset) . ': update task queued');

            $restoredAsset = Asset::getById($asset->getId(), ['force' => true]);
            foreach ($derivedSettings as $key => $value) {
                $this->assertEquals($value, $restoredAsset->getCustomSetting($key), get_class($asset) . ': ' . $key);
            }
        }
    }

    /**
     * Versions created before the custom settings were loaded explicitly before dumping, of an asset that was
     * hydrated from the cache without its custom settings (too large for the cache), don't contain the custom
     * settings at all. Restoring such a version can't restore the derived settings, so they have to be generated
     * again from the restored data.
     */
    public function testLegacyVersionWithoutCustomSettingsRegeneratesDerivedSettings(): void
    {
        $document = TestHelper::createDocumentAsset('', $this->getPdfWithMetaData());
        $document->getEmbeddedMetaData(true, false);
        $document->setCustomSetting('document_page_count', 3);
        $document->save();

        // replace the data of the version by a dump in the legacy format
        $version = $document->getLatestVersion(null, true);
        $this->assertNotNull($version);
        Pimcore::getContainer()->get(VersionStorageAdapterInterface::class)->save(
            $version,
            TestHelper::getLegacyDumpDataWithoutCustomSettings($document),
            $document->getStream()
        );

        // a custom setting added afterwards belongs to the current state of the asset, not to the version
        $document->setCustomSetting('customSettingsTest', 'test');
        $document->save();

        $queueSize = TestHelper::getAssetUpdateTaskQueueSize();
        $restoredDocument = $version->loadData();
        $this->assertInstanceOf(Asset\Document::class, $restoredDocument);
        $this->assertNull($restoredDocument->getCustomSetting('customSettingsTest'));
        $restoredDocument->save();
        // the derived settings are unknown, so the restored data is processed again ...
        $this->assertSame($queueSize + 1, TestHelper::getAssetUpdateTaskQueueSize());

        $document = Asset::getById($document->getId(), ['force' => true]);
        $this->assertNull($document->getPageCount());
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));
        // the current custom settings must not leak into the restored state
        $this->assertNull($document->getCustomSetting('customSettingsTest'));

        // ... which generates the derived settings again (with exiftool if available, so only a key
        // available with and without exiftool is checked)
        TestHelper::runAssetUpdateTasks($document->getId());

        $document = Asset::getById($document->getId(), ['force' => true]);
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame('Pimcore Test Suite', $document->getEmbeddedMetaData(false)['CreatorTool'] ?? null);
    }

    /**
     * An asset remembers that its custom settings were too large for the cache, even if they are cleared
     * afterwards. A version of such an asset with cleared custom settings must not be mistaken for a legacy
     * version without custom settings (see testLegacyVersionWithoutCustomSettingsRegeneratesDerivedSettings())
     */
    public function testVersionRestoreKeepsClearedOversizedCustomSettings(): void
    {
        $document = TestHelper::createDocumentAsset();
        $document = Asset::getById($document->getId(), ['force' => true]);
        TestHelper::simulateCustomSettingsTooLargeForCache($document);
        $document->setCustomSettings([]);
        $document->save();

        $clearedVersion = $document->getLatestVersion(null, true);
        $this->assertNotNull($clearedVersion);

        // the current state of the asset has custom settings again
        $document->setCustomSetting('customSettingsTest', 'test');
        $document->save();

        $queueSize = TestHelper::getAssetUpdateTaskQueueSize();
        $restoredDocument = $clearedVersion->loadData();
        $this->assertInstanceOf(Asset\Document::class, $restoredDocument);
        $this->assertNull($restoredDocument->getCustomSetting('customSettingsTest'));
        $restoredDocument->save();
        // the empty custom settings of the version are restored as they are, without processing the data again
        $this->assertSame($queueSize, TestHelper::getAssetUpdateTaskQueueSize());

        $document = Asset::getById($document->getId(), ['force' => true]);
        $this->assertNull($document->getCustomSetting('customSettingsTest'));
    }

    /**
     * Custom settings which are too large for the cache are only loaded on access, which must not lead to
     * a version without custom settings when it is created from an asset that was hydrated from the cache
     */
    public function testVersionKeepsCustomSettingsOfCacheHydratedAsset(): void
    {
        $document = TestHelper::createDocumentAsset('', $this->getPdfWithMetaData());
        $metaData = $document->getEmbeddedMetaData(true, false);
        $document->setCustomSetting('customSettingsTest', 'test');
        $document->save();

        $document = TestHelper::getCacheHydratedAsset(Asset::getById($document->getId(), ['force' => true]));
        $document->saveVersion();

        $version = $document->getLatestVersion(null, true);
        $this->assertNotNull($version);

        $versionDocument = $version->loadData();
        $this->assertInstanceOf(Asset\Document::class, $versionDocument);
        $this->assertTrue($versionDocument->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertEquals($metaData, $versionDocument->getCustomSetting('embeddedMetaData'));
        $this->assertSame('test', $versionDocument->getCustomSetting('customSettingsTest'));
    }

    public function testCopyKeepsCustomSettingsOfCacheHydratedAsset(): void
    {
        $document = TestHelper::createDocumentAsset('', $this->getPdfWithMetaData());
        $metaData = $document->getEmbeddedMetaData(true, false);
        $document->setCustomSetting('customSettingsTest', 'test');
        $document->save();

        $source = TestHelper::getCacheHydratedAsset(Asset::getById($document->getId(), ['force' => true]));
        $folder = Asset\Service::createFolderByPath('/' . uniqid('embedded-meta-data-copy-'));
        $service = new Asset\Service();

        foreach (['copyAsChild', 'copyRecursive'] as $copyMethod) {
            $copy = $service->$copyMethod($folder, $source);
            $copy = Asset::getById($copy->getId(), ['force' => true]);
            $this->assertInstanceOf(Asset\Document::class, $copy);
            $this->assertTrue($copy->getCustomSetting('embeddedMetaDataExtracted'), $copyMethod);
            $this->assertEquals($metaData, $copy->getEmbeddedMetaData(false), $copyMethod);
            $this->assertSame('test', $copy->getCustomSetting('customSettingsTest'), $copyMethod);
        }
    }

    public function testCopyKeepsEmbeddedMetaData(): void
    {
        $document = TestHelper::createDocumentAsset('', $this->getPdfWithMetaData());
        $metaData = $document->getEmbeddedMetaData(true, false);
        $this->assertSame('Pimcore Test Suite', $metaData['CreatorTool']);
        $document->save();

        $folder = Asset\Service::createFolderByPath('/' . uniqid('embedded-meta-data-copy-'));
        $service = new Asset\Service();

        foreach (['copyAsChild', 'copyRecursive'] as $copyMethod) {
            $copy = $service->$copyMethod($folder, $document);
            $this->assertNotSame($document->getId(), $copy->getId());

            $copy = Asset::getById($copy->getId(), ['force' => true]);
            $this->assertInstanceOf(Asset\Document::class, $copy);
            $this->assertTrue($copy->getCustomSetting('embeddedMetaDataExtracted'), $copyMethod);
            $this->assertEquals($metaData, $copy->getEmbeddedMetaData(false), $copyMethod);
        }
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

        TestHelper::runAssetUpdateTasks($document->getId());

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
