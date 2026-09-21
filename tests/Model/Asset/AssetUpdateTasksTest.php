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

use Pimcore\Document;
use Pimcore\Messenger\AssetUpdateTasksMessage;
use Pimcore\Model\Asset;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Storage;
use Pimcore\Video;

/**
 * Tests the tasks which the asset update tasks queue processes replaced data with: they are bound to the data they
 * were created for, so that they don't process (and overwrite) a state the asset got in the meantime
 *
 * @group model.asset.asset
 */
class AssetUpdateTasksTest extends ModelTestCase
{
    /**
     * A task created for replaced data can still be waiting in the queue when a processed state is restored (e.g.
     * from a version). Handling it must not process the restored state, as this could overwrite its derived settings.
     * A task created on demand processes the asset in any case.
     */
    public function testTaskOfReplacedDataIsSkippedAfterRestoringProcessedState(): void
    {
        $image = TestHelper::createImageAsset();
        $imageId = $image->getId();

        // a processed state with derived settings which differ from those the processing generates,
        // so that processing it again is noticeable
        $image->setCustomSetting('imageWidth', 12345);
        $image->setCustomSetting('imageHeight', 54321);
        $image->setCustomSetting('imageDimensionsCalculated', true);
        $image->setProcessingPending(false);
        $image->save();
        $processedVersion = $image->getLatestVersion(null, true);
        $this->assertNotNull($processedVersion);

        // the data is replaced, which creates a task bound to the new data
        $image->setData(file_get_contents(TestHelper::resolveFilePath('assets/images/image1.jpg')));
        $image->save();
        $image = Asset::getById($imageId, ['force' => true]);
        $this->assertTrue($image->isProcessingPending());
        $staleTask = $this->getLastQueuedTask($imageId);
        $this->assertSame($image->getDataGeneration(), $staleTask->getDataGeneration());
        $this->assertFalse($staleTask->isPreviewsOnly());

        // the processed state is restored before the task is handled, which only creates a task for its previews
        $queueSize = TestHelper::getAssetUpdateTaskQueueSize();
        $processedVersion->loadData()->save();
        $this->assertSame($queueSize + 1, TestHelper::getAssetUpdateTaskQueueSize());
        $image = Asset::getById($imageId, ['force' => true]);
        $this->assertFalse($image->isProcessingPending());
        $this->assertSame(12345, $image->getCustomSetting('imageWidth'));
        $previewTask = $this->getLastQueuedTask($imageId);
        $this->assertTrue($previewTask->isPreviewsOnly());
        $this->assertSame($image->getDataGeneration(), $previewTask->getDataGeneration());
        $this->assertNotSame($staleTask->getDataGeneration(), $previewTask->getDataGeneration());

        // handling the task of the replaced data leaves the restored state alone
        TestHelper::handleAssetUpdateTaskMessage($staleTask);
        $image = Asset::getById($imageId, ['force' => true]);
        $this->assertSame(12345, $image->getCustomSetting('imageWidth'));
        $this->assertSame(54321, $image->getCustomSetting('imageHeight'));
        $this->assertFalse($image->isProcessingPending());

        // in contrast to a task created on demand, which processes the asset in any case
        $image->triggerUpdateTask();
        $forcedTask = $this->getLastQueuedTask($imageId);
        $this->assertNull($forcedTask->getDataGeneration());
        TestHelper::handleAssetUpdateTaskMessage($forcedTask);
        $image = Asset::getById($imageId, ['force' => true]);
        $this->assertNotSame(12345, $image->getCustomSetting('imageWidth'));
        $this->assertGreaterThan(0, $image->getCustomSetting('imageWidth'));
        $this->assertFalse($image->isProcessingPending());
    }

    /**
     * When the data is replaced again before the task of the first replacement is handled, the first task is
     * skipped and the second one processes the current data
     */
    public function testTaskOfReplacedDataIsSkippedAfterAnotherReplacement(): void
    {
        $document = TestHelper::createDocumentAsset();
        $documentId = $document->getId();

        $document->setData(file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf')));
        $document->save();
        $firstTask = $this->getLastQueuedTask($documentId);
        $firstGeneration = Asset::getById($documentId, ['force' => true])->getDataGeneration();
        $this->assertNotNull($firstGeneration);
        $this->assertSame($firstGeneration, $firstTask->getDataGeneration());

        $document->setData(file_get_contents(TestHelper::resolveFilePath('assets/document/sonnenblume.pdf')));
        $document->save();
        $secondTask = $this->getLastQueuedTask($documentId);
        $secondGeneration = Asset::getById($documentId, ['force' => true])->getDataGeneration();
        $this->assertNotNull($secondGeneration);
        $this->assertNotSame($firstGeneration, $secondGeneration);
        $this->assertSame($secondGeneration, $secondTask->getDataGeneration());

        // the first task is skipped, as the data it was created for was replaced
        TestHelper::handleAssetUpdateTaskMessage($firstTask);
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertTrue($document->isProcessingPending());
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));

        // the second task processes the current data
        TestHelper::handleAssetUpdateTaskMessage($secondTask);
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertFalse($document->isProcessingPending());
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertNotSame('Pimcore Test Suite', $document->getEmbeddedMetaData(false)['CreatorTool'] ?? null);

        // a task whose processing was finished by others in the meantime is skipped as well
        TestHelper::handleAssetUpdateTaskMessage($secondTask);
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertFalse($document->isProcessingPending());
    }

    /**
     * Processing takes time, during which the data can be replaced by others. The results of the processing belong
     * to the previous data then and must not be saved, as this would overwrite the state of the replacement, whose
     * own task would find its processing finished and skip it, so the replacement would never be processed.
     */
    public function testResultsOfTaskAreDiscardedIfDataWasReplacedDuringProcessing(): void
    {
        $document = TestHelper::createDocumentAsset();
        $documentId = $document->getId();
        $previousTask = $this->getLastQueuedTask($documentId);
        $this->assertNotNull($previousTask->getDataGeneration());

        // the state the handler loaded when it started processing the previous data ...
        $loadedState = Asset::getById($documentId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $loadedState);
        $this->assertSame($previousTask->getDataGeneration(), $loadedState->getDataGeneration());

        // ... while the data is replaced, which creates a task for the replacement
        $replacement = Asset::getById($documentId, ['force' => true]);
        $replacement->setData(file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf')));
        $replacement->save();
        $replacementTask = $this->getLastQueuedTask($documentId);
        $replacementGeneration = Asset::getById($documentId, ['force' => true])->getDataGeneration();
        $this->assertNotNull($replacementGeneration);
        $this->assertNotSame($previousTask->getDataGeneration(), $replacementGeneration);
        $this->assertSame($replacementGeneration, $replacementTask->getDataGeneration());

        // the results of the previous task are discarded, so the state of the replacement remains untouched
        TestHelper::handleAssetUpdateTaskMessage($previousTask, $loadedState);
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertSame($replacementGeneration, $document->getDataGeneration());
        $this->assertTrue($document->isProcessingPending());
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));

        // the same applies to a task created on demand, which was handled with the previous state
        TestHelper::handleAssetUpdateTaskMessage(new AssetUpdateTasksMessage($documentId), $loadedState);
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertSame($replacementGeneration, $document->getDataGeneration());
        $this->assertTrue($document->isProcessingPending());
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));

        // the task of the replacement processes it
        TestHelper::handleAssetUpdateTaskMessage($replacementTask);
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertFalse($document->isProcessingPending());
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame('Pimcore Test Suite', $document->getEmbeddedMetaData(false)['CreatorTool'] ?? null);
    }

    /**
     * A task created on demand processes the asset in any case, but the results of a task handled with a state loaded
     * before a processed state was restored must not be saved either: they belong to the previous data and would
     * overwrite the derived settings of the restored state (which are not regenerated, as its processing is finished).
     * This also applies if the restored data is identical to the previous data.
     */
    public function testResultsOfTaskAreDiscardedIfProcessedStateWasRestoredDuringProcessing(): void
    {
        foreach (['assets/images/image1.jpg', 'assets/images/image5.jpg'] as $replacementFile) {
            $image = TestHelper::createImageAsset();
            $imageId = $image->getId();

            // a processed state with derived settings which differ from those the processing generates
            $image->setCustomSetting('imageWidth', 12345);
            $image->setCustomSetting('imageHeight', 54321);
            $image->setCustomSetting('imageDimensionsCalculated', true);
            $image->setProcessingPending(false);
            $image->save();
            $processedVersion = $image->getLatestVersion(null, true);
            $this->assertNotNull($processedVersion);

            // the data is replaced (by other or identical data) and processed, which is the state a task on demand loads ...
            $image->setData(file_get_contents(TestHelper::resolveFilePath($replacementFile)));
            $image->save();
            TestHelper::runAssetUpdateTasks($imageId);
            $loadedState = Asset::getById($imageId, ['force' => true]);
            $this->assertInstanceOf(Asset\Image::class, $loadedState);
            $this->assertFalse($loadedState->isProcessingPending());
            $this->assertNotSame(12345, $loadedState->getCustomSetting('imageWidth'));

            // ... while the processed state is restored
            $processedVersion->loadData()->save();
            $image = Asset::getById($imageId, ['force' => true]);
            $this->assertFalse($image->isProcessingPending());
            $this->assertSame(12345, $image->getCustomSetting('imageWidth'));
            $this->assertNotSame($loadedState->getDataGeneration(), $image->getDataGeneration());

            TestHelper::handleAssetUpdateTaskMessage(new AssetUpdateTasksMessage($imageId), $loadedState);
            $image = Asset::getById($imageId, ['force' => true]);
            $this->assertSame(12345, $image->getCustomSetting('imageWidth'), $replacementFile);
            $this->assertSame(54321, $image->getCustomSetting('imageHeight'), $replacementFile);

            // a task on demand handled with the current state processes it
            TestHelper::runAssetUpdateTasks($imageId);
            $image = Asset::getById($imageId, ['force' => true]);
            $this->assertNotSame(12345, $image->getCustomSetting('imageWidth'));
        }
    }

    /**
     * The preview thumbnail of a document is generated by its task. A task handled with a state loaded before the
     * data was replaced must not generate it, as the thumbnail would show the previous data, but survive the
     * replacement (which clears the thumbnails before the task of the replacement generates them again)
     */
    public function testThumbnailIsNotGeneratedForDataReplacedDuringDocumentProcessing(): void
    {
        if (!Document::isAvailable() || !(new Asset\Document())->isThumbnailsEnabled()) {
            $this->markTestSkipped('Document thumbnails are not available');
        }

        $document = TestHelper::createDocumentAsset();
        $this->assertThumbnailIsNotGeneratedForDataReplacedDuringProcessing(
            $document,
            file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf'))
        );
    }

    /**
     * The same applies to the preview thumbnail of an image (see
     * testThumbnailIsNotGeneratedForDataReplacedDuringDocumentProcessing())
     */
    public function testThumbnailIsNotGeneratedForDataReplacedDuringImageProcessing(): void
    {
        $image = TestHelper::createImageAsset();
        $this->assertThumbnailIsNotGeneratedForDataReplacedDuringProcessing(
            $image,
            file_get_contents(TestHelper::resolveFilePath('assets/images/image1.jpg'))
        );
    }

    private function assertThumbnailIsNotGeneratedForDataReplacedDuringProcessing(Asset $asset, string $newData): void
    {
        $assetId = $asset->getId();
        $previousTask = $this->getLastQueuedTask($assetId);
        $loadedState = Asset::getById($assetId, ['force' => true]);
        $this->assertInstanceOf(get_class($asset), $loadedState);

        $replacement = Asset::getById($assetId, ['force' => true]);
        $replacement->setData($newData);
        $replacement->save();
        $replacementTask = $this->getLastQueuedTask($assetId);
        $this->assertSame([], $this->getThumbnailFiles($replacement));

        TestHelper::handleAssetUpdateTaskMessage($previousTask, $loadedState);
        $this->assertSame([], $this->getThumbnailFiles($replacement));

        // the task of the replacement generates the thumbnail
        TestHelper::handleAssetUpdateTaskMessage($replacementTask);
        $this->assertNotSame([], $this->getThumbnailFiles($replacement));
    }

    /**
     * Restoring a processed state (e.g. from a version) clears the previews of the previous data, but doesn't process
     * the restored data again (its derived settings were restored as well). Its previews are generated again by a
     * task which leaves the derived settings alone.
     */
    public function testPreviewsAreGeneratedAgainAfterRestoringProcessedState(): void
    {
        $assets = [
            [TestHelper::createImageAsset(), 'imageWidth', 12345, 'assets/images/image1.jpg', true],
            [TestHelper::createDocumentAsset(), 'document_page_count', 99, 'assets/document/embedded-meta-data.pdf', Document::isAvailable() && (new Asset\Document())->isThumbnailsEnabled()],
            [TestHelper::createVideoAsset(), 'duration', 12.5, 'assets/video/example.mp4', false],
        ];

        foreach ($assets as [$asset, $derivedKey, $derivedValue, $replacementFile, $previewsAvailable]) {
            $label = get_class($asset);
            $assetId = $asset->getId();
            TestHelper::runAssetUpdateTasks($assetId);
            if ($previewsAvailable) {
                $this->assertNotSame([], $this->getThumbnailFiles($asset), $label . ': previews after processing');
            }

            // a processed state with a derived setting which differs from what the processing generates
            $asset = Asset::getById($assetId, ['force' => true]);
            $asset->setCustomSetting($derivedKey, $derivedValue);
            $asset->save();
            $this->assertFalse($asset->isProcessingPending(), $label);
            $processedVersion = $asset->getLatestVersion(null, true);
            $this->assertNotNull($processedVersion, $label);

            // the data is replaced and processed in the meantime
            $asset->setData(file_get_contents(TestHelper::resolveFilePath($replacementFile)));
            $asset->save();
            TestHelper::runAssetUpdateTasks($assetId);

            // restoring the processed state clears the previews and creates a task for generating them again
            $processedVersion->loadData()->save();
            $asset = Asset::getById($assetId, ['force' => true]);
            $this->assertSame([], $this->getThumbnailFiles($asset), $label . ': previews after restore');
            $this->assertSame($derivedValue, $asset->getCustomSetting($derivedKey), $label);
            $this->assertFalse($asset->isProcessingPending(), $label);
            $previewTask = $this->getLastQueuedTask($assetId);
            $this->assertTrue($previewTask->isPreviewsOnly(), $label);
            $this->assertSame($asset->getDataGeneration(), $previewTask->getDataGeneration(), $label);

            TestHelper::handleAssetUpdateTaskMessage($previewTask);
            $asset = Asset::getById($assetId, ['force' => true]);
            if ($previewsAvailable) {
                $this->assertNotSame([], $this->getThumbnailFiles($asset), $label . ': previews after task');
            }
            $this->assertSame($derivedValue, $asset->getCustomSetting($derivedKey), $label);
            $this->assertFalse($asset->isProcessingPending(), $label);
        }
    }

    /**
     * An instance of the asset loaded before its data was replaced by others can be saved afterwards (without
     * changing the data). Its custom settings are outdated then, but saving them must not discard the pending
     * processing of the replaced data and must not attach the derived settings (and the checksum) of the previous
     * data to the replaced data: the stored ones are kept instead.
     */
    public function testPendingProcessingSurvivesSaveOfOutdatedInstance(): void
    {
        $document = TestHelper::createDocumentAsset();
        $documentId = $document->getId();
        TestHelper::runAssetUpdateTasks($documentId);

        // loaded while the previous data was processed ...
        $outdatedInstance = Asset::getById($documentId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $outdatedInstance);
        $this->assertFalse($outdatedInstance->isProcessingPending());
        $this->assertTrue($outdatedInstance->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertNotSame('Pimcore Test Suite', $outdatedInstance->getEmbeddedMetaData(false)['CreatorTool'] ?? null);

        // ... the data is replaced by others ...
        $replacement = Asset::getById($documentId, ['force' => true]);
        $replacement->setData(file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf')));
        $replacement->save();
        $replacementTask = $this->getLastQueuedTask($documentId);
        $replacementGeneration = $replacementTask->getDataGeneration();
        $this->assertNotNull($replacementGeneration);
        $replacementChecksum = Asset::getById($documentId, ['force' => true])->getCustomSetting('checksum');
        $this->assertNotEmpty($replacementChecksum);
        $this->assertNotSame($replacementChecksum, $outdatedInstance->getCustomSetting('checksum'));

        // ... and the outdated instance is saved with an unrelated change
        $outdatedInstance->setCustomSetting('customSettingsTest', 'test');
        $outdatedInstance->setCustomSetting('document_page_count', 99);
        $outdatedInstance->save();

        // the state of the replaced data is kept, the outdated derived settings are not saved
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $document);
        $this->assertSame('test', $document->getCustomSetting('customSettingsTest'));
        $this->assertSame($replacementGeneration, $document->getDataGeneration());
        $this->assertTrue($document->isProcessingPending());
        $this->assertSame($replacementChecksum, $document->getCustomSetting('checksum'));
        $this->assertNull($document->getPageCount());
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));

        // the task of the replacement processes it
        TestHelper::handleAssetUpdateTaskMessage($replacementTask);
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $document);
        $this->assertFalse($document->isProcessingPending());
        $this->assertSame('test', $document->getCustomSetting('customSettingsTest'));
        $this->assertSame('Pimcore Test Suite', $document->getEmbeddedMetaData(false)['CreatorTool'] ?? null);
        if ($document->isPageCountProcessingEnabled() && Document::isAvailable()) {
            $this->assertSame(1, $document->getPageCount());
        }
    }

    /**
     * A stored checksum which is missing (as it couldn't be generated) must not be replaced by the outdated one of
     * an instance loaded before the data was replaced
     */
    public function testOutdatedInstanceDoesNotRestoreChecksumOfPreviousData(): void
    {
        $document = TestHelper::createDocumentAsset();
        $documentId = $document->getId();
        $outdatedInstance = Asset::getById($documentId, ['force' => true]);
        $this->assertNotEmpty($outdatedInstance->getCustomSetting('checksum'));

        $replacement = Asset::getById($documentId, ['force' => true]);
        $replacement->setData(file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf')));
        $replacement->save();
        // the checksum of the replaced data couldn't be generated
        $replacement->removeCustomSetting('checksum');
        $replacement->getDao()->updateCustomSettings();
        $this->assertNull(Asset::getById($documentId, ['force' => true])->getCustomSetting('checksum'));

        $outdatedInstance->setCustomSetting('customSettingsTest', 'test');
        $outdatedInstance->save();

        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertSame('test', $document->getCustomSetting('customSettingsTest'));
        $this->assertNull($document->getCustomSetting('checksum'));
        $this->assertTrue($document->isProcessingPending());
    }

    /**
     * An instance loaded while the processing of the data was pending can be saved after the processing finished.
     * Its derived settings are outdated then (they are missing, or belong to the previous data), so saving them must
     * not overwrite the results of the processing: the stored ones are kept instead.
     */
    public function testResultsOfProcessingSurviveSaveOfOutdatedInstance(): void
    {
        $document = TestHelper::createDocumentAsset(
            '',
            file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf'))
        );
        $documentId = $document->getId();

        // loaded while the processing was pending ...
        $outdatedInstance = Asset::getById($documentId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $outdatedInstance);
        $this->assertTrue($outdatedInstance->isProcessingPending());
        $this->assertNull($outdatedInstance->getCustomSetting('embeddedMetaDataExtracted'));

        // ... the processing finishes ...
        TestHelper::runAssetUpdateTasks($documentId);
        $processedDocument = Asset::getById($documentId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $processedDocument);
        $this->assertFalse($processedDocument->isProcessingPending());
        $this->assertTrue($processedDocument->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame('Pimcore Test Suite', $processedDocument->getEmbeddedMetaData(false)['CreatorTool'] ?? null);

        // ... and the outdated instance is saved with an unrelated change
        $outdatedInstance->setCustomSetting('customSettingsTest', 'test');
        $outdatedInstance->save();

        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $document);
        $this->assertSame('test', $document->getCustomSetting('customSettingsTest'));
        $this->assertFalse($document->isProcessingPending());
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame('Pimcore Test Suite', $document->getEmbeddedMetaData(false)['CreatorTool'] ?? null);
        $this->assertSame($processedDocument->getPageCount(), $document->getPageCount());
        $this->assertSame($processedDocument->getScanStatus(), $document->getScanStatus());
        $this->assertSame($processedDocument->getCustomSetting('checksum'), $document->getCustomSetting('checksum'));
    }

    /**
     * Derived settings of the previous data which were written back after the data was replaced (e.g. directly to
     * the database) are unknown to the processing of the replaced data, so it removes them, even if it doesn't
     * generate them again (e.g. because the required tools are not available)
     */
    public function testStaleDerivedSettingsAreRemovedWhenProcessingPendingData(): void
    {
        $document = TestHelper::createDocumentAsset();
        $documentId = $document->getId();
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $document);
        $this->assertTrue($document->isProcessingPending());
        $document->setCustomSetting('document_page_count', 99);
        $document->getDao()->updateCustomSettings();

        $video = TestHelper::createVideoAsset();
        $videoId = $video->getId();
        $video = Asset::getById($videoId, ['force' => true]);
        $this->assertInstanceOf(Asset\Video::class, $video);
        $this->assertTrue($video->isProcessingPending());
        $video->setCustomSetting('thumbnails', ['pimcore-system-treepreview' => ['status' => 'error']]);
        $video->setCustomSetting('duration', 12.5);
        $video->getDao()->updateCustomSettings();

        TestHelper::handleAssetUpdateTaskMessage($this->getLastQueuedTask($documentId));
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $document);
        $this->assertFalse($document->isProcessingPending());
        $this->assertNotSame(99, $document->getPageCount());

        TestHelper::handleAssetUpdateTaskMessage($this->getLastQueuedTask($videoId));
        $video = Asset::getById($videoId, ['force' => true]);
        $this->assertInstanceOf(Asset\Video::class, $video);
        $this->assertFalse($video->isProcessingPending());
        $this->assertNull($video->getCustomSetting('thumbnails'));
        if (!Video::isAvailable()) {
            $this->assertNull($video->getCustomSetting('duration'));
        } else {
            $this->assertNotSame(12.5, $video->getCustomSetting('duration'));
        }
    }

    /**
     * Custom settings which don't belong to the data can be changed by others while the data is processed. Saving
     * the results of the processing must not discard these changes.
     */
    public function testUnrelatedChangesSavedDuringProcessingSurvive(): void
    {
        $document = TestHelper::createDocumentAsset(
            '',
            file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf'))
        );
        $documentId = $document->getId();
        $task = $this->getLastQueuedTask($documentId);
        $loadedState = Asset::getById($documentId, ['force' => true]);

        // saved by others while the data is processed
        $other = Asset::getById($documentId, ['force' => true]);
        $other->setCustomSetting('customSettingsTest', 'test');
        $other->save();

        TestHelper::handleAssetUpdateTaskMessage($task, $loadedState);
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $document);
        $this->assertFalse($document->isProcessingPending());
        $this->assertSame('Pimcore Test Suite', $document->getEmbeddedMetaData(false)['CreatorTool'] ?? null);
        $this->assertSame('test', $document->getCustomSetting('customSettingsTest'));
    }

    /**
     * An outdated instance can even be of another type than the current data (e.g. an image replaced by a document).
     * Saving it must neither write its type back nor discard the derived settings of the current type.
     */
    public function testOutdatedInstanceOfPreviousTypeKeepsStoredResults(): void
    {
        $pdf = file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf'));
        $jpg = file_get_contents(TestHelper::resolveFilePath('assets/images/image1.jpg'));
        $cases = [
            [TestHelper::createImageAsset(), $pdf, 'pdf', Asset\Document::class, 'application/pdf', 'imageWidth'],
            // the scan status is used as derived setting of documents, as it doesn't need any external tool
            [TestHelper::createDocumentAsset('', $pdf), $jpg, 'jpg', Asset\Image::class, 'image/jpeg', Asset\Document::CUSTOM_SETTING_PDF_SCAN_STATUS],
        ];

        foreach ($cases as [$asset, $newData, $newExtension, $newClass, $newMimeType, $previousDerivedKey]) {
            $label = get_class($asset);
            $assetId = $asset->getId();
            TestHelper::runAssetUpdateTasks($assetId);
            $outdatedInstance = Asset::getById($assetId, ['force' => true]);
            $this->assertInstanceOf(get_class($asset), $outdatedInstance, $label);
            $this->assertNotNull($outdatedInstance->getCustomSetting($previousDerivedKey), $label);

            // the data is replaced by data of another type and processed
            $replacement = Asset::getById($assetId, ['force' => true]);
            $replacement->setData($newData);
            $replacement->setFilename(pathinfo($replacement->getFilename(), PATHINFO_FILENAME) . '.' . $newExtension);
            $replacement->save();
            TestHelper::runAssetUpdateTasks($assetId);
            $processed = Asset::getById($assetId, ['force' => true]);
            $this->assertInstanceOf($newClass, $processed, $label);
            $this->assertFalse($processed->isProcessingPending(), $label);
            $expectedSettings = [];
            foreach ($newClass::getDataDerivedCustomSettingKeys() as $key) {
                $expectedSettings[$key] = $processed->getCustomSetting($key);
            }
            $this->assertNotNull($expectedSettings['embeddedMetaDataExtracted'], $label);

            $outdatedInstance->setCustomSetting('customSettingsTest', 'test');
            $outdatedInstance->save();

            $current = Asset::getById($assetId, ['force' => true]);
            $this->assertInstanceOf($newClass, $current, $label);
            $this->assertSame($newMimeType, $current->getMimeType(), $label);
            $this->assertSame('test', $current->getCustomSetting('customSettingsTest'), $label);
            $this->assertSame($processed->getDataGeneration(), $current->getDataGeneration(), $label);
            $this->assertFalse($current->isProcessingPending(), $label);
            $this->assertNull($current->getCustomSetting($previousDerivedKey), $label);
            foreach ($expectedSettings as $key => $value) {
                $this->assertEquals($value, $current->getCustomSetting($key), $label . ': ' . $key);
            }

            // the version created by the save of the outdated instance must be of the current type as well
            $version = $current->getLatestVersion(null, true);
            $this->assertNotNull($version, $label);
            $versionAsset = $version->loadData();
            $this->assertInstanceOf($newClass, $versionAsset, $label . ': version');
            $this->assertSame($current->getType(), $versionAsset->getType(), $label . ': version');
            $this->assertSame('test', $versionAsset->getCustomSetting('customSettingsTest'), $label . ': version');
            foreach ($expectedSettings as $key => $value) {
                $this->assertEquals($value, $versionAsset->getCustomSetting($key), $label . ': version ' . $key);
            }
        }
    }

    /**
     * The marker of a failed processing belongs to the previous data, so it is removed when the data is replaced,
     * even by data of a type which isn't processed (nothing would remove it otherwise, and it would prevent the asset
     * from being added to the queue on demand)
     */
    public function testFailedProcessingMarkerIsRemovedWhenReplacedByUnprocessedType(): void
    {
        $image = TestHelper::createImageAsset();
        $imageId = $image->getId();
        $image->setCustomSetting(Asset::CUSTOM_SETTING_PROCESSING_FAILED, true);
        $image->setProcessingPending(false);
        $image->save();
        $this->assertTrue(Asset::getById($imageId, ['force' => true])->getCustomSetting(Asset::CUSTOM_SETTING_PROCESSING_FAILED));

        $image->setData('plain text, which is not processed by the asset update tasks queue');
        $image->setFilename(pathinfo($image->getFilename(), PATHINFO_FILENAME) . '.txt');
        $image->save();

        $asset = Asset::getById($imageId, ['force' => true]);
        $this->assertNotContains($asset->getType(), ['image', 'video', 'document']);
        $this->assertNull($asset->getCustomSetting(Asset::CUSTOM_SETTING_PROCESSING_FAILED));
        $this->assertFalse($asset->isProcessingPending());
    }

    /**
     * All settings derived from the data of a video (including the spherical meta data) belong to the previous data
     * when it is replaced, even by data of a type which isn't processed (nothing would remove them otherwise)
     */
    public function testDerivedSettingsAreRemovedWhenVideoDataIsReplacedByUnprocessedType(): void
    {
        $video = TestHelper::createVideoAsset();
        $videoId = $video->getId();
        $video->setCustomSetting('SphericalMetaData', ['ProjectionType' => 'equirectangular']);
        $video->setCustomSetting('duration', 12.5);
        $video->setProcessingPending(false);
        $video->save();
        $video = Asset::getById($videoId, ['force' => true]);
        $this->assertInstanceOf(Asset\Video::class, $video);
        $this->assertSame(['ProjectionType' => 'equirectangular'], $video->getSphericalMetaData());

        $video->setData('plain text, which is not processed by the asset update tasks queue');
        $video->setFilename(pathinfo($video->getFilename(), PATHINFO_FILENAME) . '.txt');
        $video->save();

        $asset = Asset::getById($videoId, ['force' => true]);
        $this->assertNotContains($asset->getType(), ['image', 'video', 'document']);
        $this->assertFalse($asset->isProcessingPending());
        $this->assertNull($asset->getCustomSetting('SphericalMetaData'));
        $this->assertNull($asset->getCustomSetting('duration'));
    }

    /**
     * A copy of an asset takes over the derived settings of the source instead of generating them again (which could
     * fail or differ), so only the previews of a processed source are generated for the copy
     */
    public function testCopyKeepsProcessedStateOfSource(): void
    {
        $source = TestHelper::createDocumentAsset(
            '',
            file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf'))
        );
        TestHelper::runAssetUpdateTasks($source->getId());
        $source = Asset::getById($source->getId(), ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $source);
        // derived settings which differ from what processing the data generates, so that processing is noticeable
        $source->setCustomSetting('embeddedMetaData', ['CreatorTool' => 'Copied from the source']);
        $source->setCustomSetting('document_page_count', 99);
        $source->save();
        $this->assertFalse($source->isProcessingPending());

        $folder = Asset\Service::createFolderByPath('/' . uniqid('copy-processed-'));
        $service = new Asset\Service();

        foreach (['copyAsChild', 'copyRecursive', 'copyContents'] as $copyMethod) {
            if ($copyMethod === 'copyContents') {
                $copy = $service->copyContents(TestHelper::createDocumentAsset(), $source);
            } else {
                $copy = $service->$copyMethod($folder, $source);
            }
            $copyId = $copy->getId();
            $this->assertNotSame($source->getId(), $copyId);

            $copy = Asset::getById($copyId, ['force' => true]);
            $this->assertInstanceOf(Asset\Document::class, $copy);
            $this->assertFalse($copy->isProcessingPending(), $copyMethod);
            $this->assertNotSame($source->getDataGeneration(), $copy->getDataGeneration(), $copyMethod);
            $copyTask = $this->getLastQueuedTask($copyId);
            $this->assertTrue($copyTask->isPreviewsOnly(), $copyMethod);
            $this->assertSame($copy->getDataGeneration(), $copyTask->getDataGeneration(), $copyMethod);

            TestHelper::handleAssetUpdateTaskMessage($copyTask);
            $copy = Asset::getById($copyId, ['force' => true]);
            $this->assertInstanceOf(Asset\Document::class, $copy);
            $this->assertSame('Copied from the source', $copy->getEmbeddedMetaData(false)['CreatorTool'] ?? null, $copyMethod);
            $this->assertSame(99, $copy->getPageCount(), $copyMethod);
            $this->assertFalse($copy->isProcessingPending(), $copyMethod);
        }
    }

    /**
     * A copy of a source whose data was replaced without saving the source has to be processed: the derived settings
     * of the source still belong to its previous data
     */
    public function testCopyOfUnsavedReplacementIsProcessed(): void
    {
        $source = TestHelper::createDocumentAsset();
        TestHelper::runAssetUpdateTasks($source->getId());
        $source = Asset::getById($source->getId(), ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $source);
        $source->setCustomSetting('document_page_count', 99);
        $source->save();
        $this->assertFalse($source->isProcessingPending());

        // the data is replaced, but the source is not saved
        $source->setData(file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf')));
        $this->assertSame(99, $source->getPageCount());

        $folder = Asset\Service::createFolderByPath('/' . uniqid('copy-replaced-'));
        $copy = (new Asset\Service())->copyAsChild($folder, $source);
        $copyId = $copy->getId();

        $copy = Asset::getById($copyId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $copy);
        $this->assertTrue($copy->isProcessingPending());
        $this->assertNull($copy->getPageCount());
        $copyTask = $this->getLastQueuedTask($copyId);
        $this->assertFalse($copyTask->isPreviewsOnly());
        $this->assertSame($copy->getDataGeneration(), $copyTask->getDataGeneration());

        TestHelper::handleAssetUpdateTaskMessage($copyTask);
        $copy = Asset::getById($copyId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $copy);
        $this->assertFalse($copy->isProcessingPending());
        $this->assertNotSame(99, $copy->getPageCount());
        $this->assertSame('Pimcore Test Suite', $copy->getEmbeddedMetaData(false)['CreatorTool'] ?? null);
    }

    /**
     * Already processed data can be processed again on demand (e.g. by pimcore:assets:add-to-update-task-queue).
     * An instance loaded before must not overwrite the new results with its outdated derived settings when it is
     * saved afterwards, although the data and the pending state didn't change.
     */
    public function testResultsOfReprocessingSurviveSaveOfOutdatedInstance(): void
    {
        $image = TestHelper::createImageAsset();
        $imageId = $image->getId();
        TestHelper::runAssetUpdateTasks($imageId);

        // the results of the previous processing differ from those the processing generates
        $image = Asset::getById($imageId, ['force' => true]);
        $image->setCustomSetting('imageWidth', 12345);
        $image->save();

        $outdatedInstance = Asset::getById($imageId, ['force' => true]);
        $this->assertInstanceOf(Asset\Image::class, $outdatedInstance);
        $this->assertFalse($outdatedInstance->isProcessingPending());
        $this->assertSame(12345, $outdatedInstance->getCustomSetting('imageWidth'));

        // the data is processed again on demand ...
        $image->triggerUpdateTask();
        TestHelper::handleAssetUpdateTaskMessage($this->getLastQueuedTask($imageId));
        $processedImage = Asset::getById($imageId, ['force' => true]);
        $this->assertNotSame(12345, $processedImage->getCustomSetting('imageWidth'));
        $this->assertNotSame($outdatedInstance->getDataState(), $processedImage->getDataState());

        // ... and the outdated instance is saved with an unrelated change
        $outdatedInstance->setCustomSetting('customSettingsTest', 'test');
        $outdatedInstance->save();

        $image = Asset::getById($imageId, ['force' => true]);
        $this->assertInstanceOf(Asset\Image::class, $image);
        $this->assertSame('test', $image->getCustomSetting('customSettingsTest'));
        $this->assertSame($processedImage->getCustomSetting('imageWidth'), $image->getCustomSetting('imageWidth'));
        $this->assertSame($processedImage->getDataState(), $image->getDataState());
        $this->assertFalse($image->isProcessingPending());
    }

    /**
     * An instance which finished the processing itself saves its results in any case, but only once: if it is
     * reused after others processed the same data again, its later saves must not overwrite their results either
     */
    public function testInstanceReusedAfterReprocessingByOthersTakesOverTheirResults(): void
    {
        $image = TestHelper::createImageAsset();
        $imageId = $image->getId();
        $task = $this->getLastQueuedTask($imageId);

        // this instance processes the data and saves its results
        $instance = Asset::getById($imageId, ['force' => true]);
        $this->assertInstanceOf(Asset\Image::class, $instance);
        TestHelper::handleAssetUpdateTaskMessage($task, $instance);
        $this->assertFalse($instance->isProcessingPending());
        $this->assertSame($instance->getDataState(), Asset::getById($imageId, ['force' => true])->getDataState());

        // others process the same data again, with results which differ from those of this instance
        TestHelper::runAssetUpdateTasks($imageId);
        $reprocessed = Asset::getById($imageId, ['force' => true]);
        $reprocessed->setCustomSetting('imageWidth', 54321);
        $reprocessed->save();
        $reprocessed = Asset::getById($imageId, ['force' => true]);
        $this->assertNotSame($instance->getDataState(), $reprocessed->getDataState());

        // this instance is saved with an unrelated change
        $instance->setCustomSetting('customSettingsTest', 'test');
        $instance->save();

        $image = Asset::getById($imageId, ['force' => true]);
        $this->assertSame('test', $image->getCustomSetting('customSettingsTest'));
        $this->assertSame(54321, $image->getCustomSetting('imageWidth'));
        $this->assertSame($reprocessed->getDataState(), $image->getDataState());
    }

    /**
     * When the results of an instance are discarded (as others finished processing the same data in the meantime),
     * a later save of the instance must not save them either, but take over the stored results
     */
    public function testDiscardedResultsAreNotSavedByLaterSaveOfInstance(): void
    {
        $image = TestHelper::createImageAsset();
        $imageId = $image->getId();
        $task = $this->getLastQueuedTask($imageId);
        $loadedState = Asset::getById($imageId, ['force' => true]);
        $this->assertInstanceOf(Asset\Image::class, $loadedState);
        $this->assertTrue($loadedState->isProcessingPending());

        // others finish processing the same data, with results which differ from those of this instance
        TestHelper::runAssetUpdateTasks($imageId);
        $processed = Asset::getById($imageId, ['force' => true]);
        $processed->setCustomSetting('imageWidth', 54321);
        $processed->save();
        $processed = Asset::getById($imageId, ['force' => true]);
        $this->assertFalse($processed->isProcessingPending());

        // the results of this instance are discarded ...
        TestHelper::handleAssetUpdateTaskMessage($task, $loadedState);
        $this->assertSame($processed->getDataState(), Asset::getById($imageId, ['force' => true])->getDataState());
        $this->assertSame(54321, Asset::getById($imageId, ['force' => true])->getCustomSetting('imageWidth'));

        // ... and must not be saved by a later save of the instance either
        $loadedState->setCustomSetting('customSettingsTest', 'test');
        $loadedState->save();

        $image = Asset::getById($imageId, ['force' => true]);
        $this->assertSame('test', $image->getCustomSetting('customSettingsTest'));
        $this->assertSame(54321, $image->getCustomSetting('imageWidth'));
        $this->assertSame($processed->getDataState(), $image->getDataState());
        $this->assertFalse($image->isProcessingPending());
    }

    /**
     * An outdated instance can still hold the stream of the previous data when the data was replaced by others (by
     * data of the same type). The version created by saving it must contain the current data, not the previous one
     * with the settings of the current data.
     */
    public function testVersionOfOutdatedInstanceContainsCurrentData(): void
    {
        $previousData = file_get_contents(TestHelper::resolveFilePath('assets/document/sonnenblume.pdf'));
        $newData = file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf'));

        $document = TestHelper::createDocumentAsset('', $previousData);
        $documentId = $document->getId();
        TestHelper::runAssetUpdateTasks($documentId);

        // the outdated instance opened the stream of the previous data ...
        $outdatedInstance = Asset::getById($documentId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $outdatedInstance);
        $this->assertSame($previousData, stream_get_contents($outdatedInstance->getStream()));

        // ... before the data was replaced by others
        $replacement = Asset::getById($documentId, ['force' => true]);
        $replacement->setData($newData);
        $replacement->save();
        TestHelper::runAssetUpdateTasks($documentId);
        $this->assertSame($previousData, stream_get_contents($outdatedInstance->getStream()));

        $outdatedInstance->setCustomSetting('customSettingsTest', 'test');
        $outdatedInstance->save();

        $current = Asset::getById($documentId, ['force' => true]);
        $this->assertSame($newData, $current->getData());
        $this->assertSame('test', $current->getCustomSetting('customSettingsTest'));

        $version = $current->getLatestVersion(null, true);
        $this->assertNotNull($version);
        $versionDocument = $version->loadData();
        $this->assertInstanceOf(Asset\Document::class, $versionDocument);
        $this->assertSame($newData, $versionDocument->getData());
        $this->assertSame('test', $versionDocument->getCustomSetting('customSettingsTest'));
        $this->assertSame('Pimcore Test Suite', $versionDocument->getEmbeddedMetaData(false)['CreatorTool'] ?? null);
    }

    /**
     * Restored data keeps the checksum dumped with it (it was generated for exactly this data), unless it is missing.
     * Only the checksum of replaced data is generated again.
     */
    public function testRestoredDataKeepsItsChecksum(): void
    {
        $document = TestHelper::createDocumentAsset();
        $documentId = $document->getId();
        TestHelper::runAssetUpdateTasks($documentId);

        // a checksum which differs from the generated one, so that generating it again is noticeable
        $document = Asset::getById($documentId, ['force' => true]);
        $generatedChecksum = $document->getCustomSetting('checksum');
        $this->assertNotEmpty($generatedChecksum);
        $document->setCustomSetting('checksum', 'dumped-with-the-data');
        $document->save();
        $versionWithChecksum = $document->getLatestVersion(null, true);
        $this->assertNotNull($versionWithChecksum);

        // a dump without checksum (e.g. of an asset saved before checksums were generated)
        $document->removeCustomSetting('checksum');
        $document->save();
        $versionWithoutChecksum = $document->getLatestVersion(null, true);
        $this->assertNotNull($versionWithoutChecksum);
        $this->assertNull($versionWithoutChecksum->loadData()->getCustomSetting('checksum'));

        $document->setData(file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf')));
        $document->save();
        $this->assertNotSame($generatedChecksum, Asset::getById($documentId, ['force' => true])->getCustomSetting('checksum'));

        $versionWithChecksum->loadData()->save();
        $this->assertSame('dumped-with-the-data', Asset::getById($documentId, ['force' => true])->getCustomSetting('checksum'));

        // a missing checksum is generated for the restored data
        $versionWithoutChecksum->loadData()->save();
        $this->assertSame($generatedChecksum, Asset::getById($documentId, ['force' => true])->getCustomSetting('checksum'));
    }

    /**
     * A copy of a source whose processing is pending is processed like the source
     */
    public function testCopyOfPendingSourceIsProcessed(): void
    {
        $source = TestHelper::createDocumentAsset(
            '',
            file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf'))
        );
        $this->assertTrue($source->isProcessingPending());

        $folder = Asset\Service::createFolderByPath('/' . uniqid('copy-pending-'));
        $copy = (new Asset\Service())->copyAsChild($folder, $source);
        $copyId = $copy->getId();

        $copy = Asset::getById($copyId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $copy);
        $this->assertTrue($copy->isProcessingPending());
        $copyTask = $this->getLastQueuedTask($copyId);
        $this->assertFalse($copyTask->isPreviewsOnly());
        $this->assertSame($copy->getDataGeneration(), $copyTask->getDataGeneration());

        TestHelper::handleAssetUpdateTaskMessage($copyTask);
        $copy = Asset::getById($copyId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $copy);
        $this->assertFalse($copy->isProcessingPending());
        $this->assertSame('Pimcore Test Suite', $copy->getEmbeddedMetaData(false)['CreatorTool'] ?? null);
    }

    /**
     * @return string[]
     */
    private function getThumbnailFiles(Asset $asset): array
    {
        $files = [];
        foreach (Storage::get('thumbnail')->listContents($asset->getRealPath() . $asset->getId(), true) as $item) {
            if ($item->isFile()) {
                $files[] = $item->path();
            }
        }

        return $files;
    }

    private function getLastQueuedTask(int $assetId): AssetUpdateTasksMessage
    {
        $tasks = TestHelper::getQueuedAssetUpdateTaskMessages($assetId);
        $this->assertNotEmpty($tasks);

        return $tasks[array_key_last($tasks)];
    }
}
