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
        $this->assertSame($image->getProcessingToken(), $staleTask->getProcessingToken());

        // the processed state is restored before the task is handled
        $queueSize = TestHelper::getAssetUpdateTaskQueueSize();
        $processedVersion->loadData()->save();
        $this->assertSame($queueSize, TestHelper::getAssetUpdateTaskQueueSize());
        $image = Asset::getById($imageId, ['force' => true]);
        $this->assertFalse($image->isProcessingPending());
        $this->assertSame(12345, $image->getCustomSetting('imageWidth'));

        // handling the task of the replaced data leaves the restored state alone
        TestHelper::handleAssetUpdateTaskMessage($staleTask);
        $image = Asset::getById($imageId, ['force' => true]);
        $this->assertSame(12345, $image->getCustomSetting('imageWidth'));
        $this->assertSame(54321, $image->getCustomSetting('imageHeight'));
        $this->assertFalse($image->isProcessingPending());

        // in contrast to a task created on demand, which processes the asset in any case
        $image->triggerUpdateTask();
        $forcedTask = $this->getLastQueuedTask($imageId);
        $this->assertNull($forcedTask->getProcessingToken());
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
        $firstToken = Asset::getById($documentId, ['force' => true])->getProcessingToken();
        $this->assertNotNull($firstToken);
        $this->assertSame($firstToken, $firstTask->getProcessingToken());

        $document->setData(file_get_contents(TestHelper::resolveFilePath('assets/document/sonnenblume.pdf')));
        $document->save();
        $secondTask = $this->getLastQueuedTask($documentId);
        $secondToken = Asset::getById($documentId, ['force' => true])->getProcessingToken();
        $this->assertNotNull($secondToken);
        $this->assertNotSame($firstToken, $secondToken);
        $this->assertSame($secondToken, $secondTask->getProcessingToken());

        // the first task is skipped, as the data it was created for was replaced
        TestHelper::handleAssetUpdateTaskMessage($firstTask);
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertSame($secondToken, $document->getProcessingToken());
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));

        // the second task processes the current data
        TestHelper::handleAssetUpdateTaskMessage($secondTask);
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertFalse($document->isProcessingPending());
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertNotSame('Pimcore Test Suite', $document->getEmbeddedMetaData(false)['CreatorTool'] ?? null);
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
        $this->assertNotNull($previousTask->getProcessingToken());

        // the state the handler loaded when it started processing the previous data ...
        $loadedState = Asset::getById($documentId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $loadedState);
        $this->assertSame($previousTask->getProcessingToken(), $loadedState->getProcessingToken());

        // ... while the data is replaced, which creates a task for the replacement
        $replacement = Asset::getById($documentId, ['force' => true]);
        $replacement->setData(file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf')));
        $replacement->save();
        $replacementTask = $this->getLastQueuedTask($documentId);
        $replacementToken = Asset::getById($documentId, ['force' => true])->getProcessingToken();
        $this->assertNotNull($replacementToken);
        $this->assertNotSame($previousTask->getProcessingToken(), $replacementToken);
        $this->assertSame($replacementToken, $replacementTask->getProcessingToken());

        // the results of the previous task are discarded, so the state of the replacement remains untouched
        TestHelper::handleAssetUpdateTaskMessage($previousTask, $loadedState);
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertSame($replacementToken, $document->getProcessingToken());
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));

        // the same applies to a task created on demand, which was handled with the previous state
        TestHelper::handleAssetUpdateTaskMessage(new AssetUpdateTasksMessage($documentId), $loadedState);
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertSame($replacementToken, $document->getProcessingToken());
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));

        // the task of the replacement processes it
        TestHelper::handleAssetUpdateTaskMessage($replacementTask);
        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertFalse($document->isProcessingPending());
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame('Pimcore Test Suite', $document->getEmbeddedMetaData(false)['CreatorTool'] ?? null);
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
        $documentId = $document->getId();
        $previousTask = $this->getLastQueuedTask($documentId);
        $loadedState = Asset::getById($documentId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $loadedState);

        $replacement = Asset::getById($documentId, ['force' => true]);
        $replacement->setData(file_get_contents(TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf')));
        $replacement->save();
        $replacementTask = $this->getLastQueuedTask($documentId);
        $this->assertSame([], $this->getThumbnailFiles($replacement));

        TestHelper::handleAssetUpdateTaskMessage($previousTask, $loadedState);
        $this->assertSame([], $this->getThumbnailFiles($replacement));

        // the task of the replacement generates the thumbnail
        TestHelper::handleAssetUpdateTaskMessage($replacementTask);
        $this->assertNotSame([], $this->getThumbnailFiles($replacement));
    }

    /**
     * An instance of the asset loaded before its data was replaced by others can be saved afterwards (without
     * changing the data). Its custom settings are outdated then, but saving them must not discard the pending
     * processing of the replaced data: the token is kept, and the processing generates the derived settings again,
     * even though the outdated instance wrote the ones of the previous data
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
        $replacementToken = $replacementTask->getProcessingToken();
        $this->assertNotNull($replacementToken);

        // ... and the outdated instance is saved with an unrelated change
        $outdatedInstance->setCustomSetting('customSettingsTest', 'test');
        $outdatedInstance->setCustomSetting('document_page_count', 99);
        $outdatedInstance->save();

        $document = Asset::getById($documentId, ['force' => true]);
        $this->assertInstanceOf(Asset\Document::class, $document);
        $this->assertSame('test', $document->getCustomSetting('customSettingsTest'));
        $this->assertSame($replacementToken, $document->getProcessingToken());

        // the task of the replacement processes it, including the derived settings the outdated instance wrote
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
