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

namespace Pimcore\Messenger\Handler;

use Exception;
use Pimcore\Db;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Messenger\AssetUpdateTasksMessage;
use Pimcore\Model\Asset;
use Pimcore\Model\Version;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Throwable;
use function sprintf;

/**
 * @internal
 */
class AssetUpdateTasksHandler
{
    public function __construct(
        protected LoggerInterface $logger,
        protected LongRunningHelper $longRunningHelper,
        protected LockFactory $lockFactory
    ) {
    }

    public function __invoke(AssetUpdateTasksMessage $message): void
    {
        $asset = Asset::getById($message->getId());
        if (!$asset) {
            $this->logger->debug(sprintf('Asset with ID %s not found', $message->getId()));

            return;
        }
        $this->logger->debug(sprintf('Processing asset with ID %s | Path: %s', $asset->getId(), $asset->getRealFullPath()));

        // a task created for replaced data (see Asset::save()) is only handled as long as the processing of this data
        // is still pending: if the data was replaced in the meantime, it is processed by the task of the replacement,
        // whose results this task must not overwrite, and if a processed state was restored, there is nothing left to
        // process (processing it anyway could overwrite the restored derived data). A task without token (e.g. created
        // on demand) processes the asset in any case.
        $processingToken = $asset->getProcessingToken();
        if ($message->getProcessingToken() !== null && $message->getProcessingToken() !== $processingToken) {
            $this->logger->debug(sprintf(
                'Skipping the task for asset with ID %s, as the data it was created for was replaced or restored in the meantime',
                $asset->getId()
            ));

            return;
        }

        $asset->removeCustomSetting(Asset::CUSTOM_SETTING_PROCESSING_FAILED);

        if ($asset instanceof Asset\Image) {
            $this->processImage($asset, $processingToken);
        } elseif ($asset instanceof Asset\Document) {
            $this->processDocument($asset, $processingToken);
        } elseif ($asset instanceof Asset\Video) {
            $this->processVideo($asset, $processingToken);
        }

        $this->longRunningHelper->deleteTemporaryFiles();
        $this->lockFactory->createLock($asset->getUpdateQueueLockId())->release();
    }

    /**
     * Saves the results of the processing, unless the processing token of the asset changed since it was loaded (see
     * Asset::getProcessingToken()): its data was replaced or restored in the meantime then, so the results belong to
     * previous data and are discarded, as they would overwrite the state of the current data, whose own task would
     * find its processing finished and skip it (or which doesn't need any processing, as it was restored from a
     * processed state). The asset is locked against concurrent saves while this is checked and the results are saved,
     * so that a replacement can't slip in between.
     *
     * @param string|null $processingToken the processing token the asset had when it was loaded
     *
     * @return bool whether the results were saved
     *
     * @throws Exception
     */
    private function saveAsset(Asset $asset, ?string $processingToken, array $saveParams = []): bool
    {
        $db = Db::get();
        $db->beginTransaction();

        try {
            if ($asset->getStoredProcessingTokenForUpdate() !== $processingToken) {
                $db->rollBack();
                $this->logger->info(sprintf(
                    'Discarding the processing results of asset with ID %s, as its data was replaced or restored in the meantime',
                    $asset->getId()
                ));

                return false;
            }

            Version::disable();

            try {
                $asset->markFieldDirty('modificationDate'); // prevent modificationDate from being changed
                $asset->save($saveParams);
            } finally {
                Version::enable();
            }

            $db->commit();
        } catch (Throwable $e) {
            try {
                $db->rollBack();
            } catch (Throwable $rollbackException) {
                $this->logger->info((string) $rollbackException);
            }

            throw $e;
        }

        return true;
    }

    private function processDocument(Asset\Document $asset, ?string $processingToken): void
    {
        $save = false;
        $saveParams = [];
        if ($asset->getMimeType() === 'application/pdf' && $asset->checkIfPdfContainsJS()) {
            $save = true;
            $saveParams['versionNote'] = 'PDF scan result';
        }

        if ($asset->isPageCountProcessingEnabled()) {
            // getPageCount() is also falsy when the last processing attempt failed
            if (!$asset->getPageCount()) {
                if (!$asset->processPageCount()) {
                    $asset->setCustomSetting(Asset::CUSTOM_SETTING_PROCESSING_FAILED, true);
                    $this->logger->warning(sprintf('Failed processing page count for document asset %s.', $asset->getId()));
                }

                $save = true;
            }
        }

        // handleEmbeddedMetaData() skips already extracted metadata on its own using the same condition,
        // but checking it here too avoids an unnecessary save in that case
        if (!$asset->getCustomSetting('embeddedMetaDataExtracted') || $asset->isDataReplaced()) {
            $asset->handleEmbeddedMetaData();
            $save = true;
        }

        if ($asset->isProcessingPending()) {
            $asset->setProcessingPending(false);
            $save = true;
        }

        if ($asset->isThumbnailsEnabled() && !$asset->getCustomSetting(Asset::CUSTOM_SETTING_PROCESSING_FAILED)) {
            $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
        }

        if ($save) {
            $this->saveAsset($asset, $processingToken, $saveParams);
        }
    }

    private function processVideo(Asset\Video $asset, ?string $processingToken): void
    {
        $failed = true;

        if ($duration = $asset->getDurationFromBackend()) {
            $asset->setCustomSetting('duration', $duration);
            if ($dimensions = $asset->getDimensionsFromBackend()) {
                $asset->setCustomSetting('videoWidth', $dimensions['width']);
                $asset->setCustomSetting('videoHeight', $dimensions['height']);
                $failed = false;
            }
        }

        if ($failed) {
            $asset->setCustomSetting(Asset::CUSTOM_SETTING_PROCESSING_FAILED, true);
            $asset->removeCustomSetting('duration');
            $asset->removeCustomSetting('videoWidth');
            $asset->removeCustomSetting('videoHeight');
        }

        $asset->removeCustomSetting('SphericalMetaData');
        $sphericalMetaData = $asset->getSphericalMetaDataFromBackend();
        if ($sphericalMetaData) {
            $asset->setCustomSetting('SphericalMetaData', $sphericalMetaData);
        }

        $asset->handleEmbeddedMetaData();
        $asset->setProcessingPending(false);
        if (!$this->saveAsset($asset, $processingToken)) {
            return;
        }

        if ($asset->getCustomSetting('videoWidth') && $asset->getCustomSetting('videoHeight')) {
            $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
        }
    }

    private function processImage(Asset\Image $image, ?string $processingToken): void
    {
        // getDimensions() might fail, so assume `false` first
        $imageDimensionsCalculated = false;

        try {
            $dimensions = $image->getDimensions(null, true);
            if ($dimensions && $dimensions['width']) {
                $image->setCustomSetting('imageWidth', $dimensions['width']);
                $image->setCustomSetting('imageHeight', $dimensions['height']);
                $imageDimensionsCalculated = true;
            }
        } catch (Exception $e) {
            $this->logger->warning('Problem getting the dimensions of the image with ID ' . $image->getId());
        }

        // this is to be downward compatible so that the controller can check if the dimensions are already calculated
        // and also to just do the calculation once, because the calculation can fail, an then the controller tries to
        // calculate the dimensions on every request an also will create a version, ...
        $image->setCustomSetting('imageDimensionsCalculated', $imageDimensionsCalculated);
        $image->handleEmbeddedMetaData();
        $image->setProcessingPending(false);
        if (!$this->saveAsset($image, $processingToken)) {
            return;
        }

        // generating the thumbnails must be after saving the image, because otherwise the generated
        // thumbnail would be invalidated on the next call, because it's older than the modification date of the asset
        $image->getThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);

        try {
            $image->generateLowQualityPreview();
        } catch (Exception $e) {
            $this->logger->warning($e->getMessage());
        }
    }
}
