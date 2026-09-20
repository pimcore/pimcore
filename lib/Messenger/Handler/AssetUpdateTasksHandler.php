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

use Closure;
use Exception;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Messenger\AssetUpdateTasksMessage;
use Pimcore\Model\Asset;
use Pimcore\Model\Version;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
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

        // a task created for certain data (see Asset::save()) is only handled as long as this data is the current one:
        // if the data was replaced or restored in the meantime, the new data has its own task (whose results this task
        // must not overwrite) or doesn't need any processing (processing it anyway could overwrite the restored derived
        // data). A task processing the data is also skipped if its processing was finished by others in the meantime.
        // A task without data generation (e.g. created on demand) processes the asset in any case.
        if ($message->getDataGeneration() !== null) {
            if ($message->getDataGeneration() !== $asset->getDataGeneration()) {
                $this->logger->debug(sprintf(
                    'Skipping the task for asset with ID %s, as the data it was created for was replaced or restored in the meantime',
                    $asset->getId()
                ));

                return;
            }
            if (!$message->isPreviewsOnly() && !$asset->isProcessingPending()) {
                $this->logger->debug(sprintf(
                    'Skipping the task for asset with ID %s, as the processing of its data was finished in the meantime',
                    $asset->getId()
                ));

                return;
            }
        }

        // the state of the data the results are generated for (see completeProcessing())
        $dataState = $asset->getDataState();

        if ($message->isPreviewsOnly()) {
            if ($generatePreviews = $this->getPreviewGenerator($asset)) {
                $this->completeProcessing($asset, $dataState, false, [], $generatePreviews);
            }
        } else {
            $this->process($asset, $dataState);
        }

        $this->longRunningHelper->deleteTemporaryFiles();
        $this->lockFactory->createLock($asset->getUpdateQueueLockId())->release();
    }

    private function process(Asset $asset, string $dataState): void
    {
        $asset->removeCustomSetting(Asset::CUSTOM_SETTING_PROCESSING_FAILED);

        // the settings derived from data whose processing is pending are unknown: they belong to the previous data.
        // They were normally cleared when the data was replaced, but an outdated instance of the asset saved by
        // others in the meantime can have written them again, so they are removed before the data is processed,
        // instead of being taken for already generated (or being kept, if the processing doesn't generate them, e.g.
        // because it is disabled)
        if ($asset->isProcessingPending()) {
            foreach ($asset->getDataDerivedCustomSettingKeys() as $key) {
                $asset->removeCustomSetting($key);
            }
        }

        if ($asset instanceof Asset\Image) {
            $this->processImage($asset, $dataState);
        } elseif ($asset instanceof Asset\Document) {
            $this->processDocument($asset, $dataState);
        } elseif ($asset instanceof Asset\Video) {
            $this->processVideo($asset, $dataState);
        }
    }

    /**
     * Completes the processing: generates the previews (if requested) and saves the results. The results are only
     * saved if the state of the data is still the one the asset was loaded with (see Asset::saveProcessingResults()):
     * if its data was replaced or restored in the meantime (or its processing finished by others), the results belong
     * to previous data and are discarded, as they would overwrite the state of the current data, whose own task would
     * find its processing finished and skip it (or which doesn't need any processing, as it was restored from a
     * processed state). The previews are generated before, without holding a lock (generating them can take long):
     * if the state of the data turns out to have changed, they are cleared, as they might show the previous data
     * (the previews of the current data are generated by its own task, or on demand).
     *
     * @param string $dataState the state of the data the asset had when it was loaded
     *
     * @return bool whether the processing was completed
     *
     * @throws Exception
     */
    private function completeProcessing(
        Asset $asset,
        string $dataState,
        bool $save = true,
        array $saveParams = [],
        ?Closure $generatePreviews = null
    ): bool {
        if ($generatePreviews) {
            $generatePreviews();
        }

        if ($save) {
            Version::disable();

            try {
                $asset->markFieldDirty('modificationDate'); // prevent modificationDate from being changed
                $completed = $asset->saveProcessingResults($dataState, $saveParams);
            } finally {
                Version::enable();
            }
        } else {
            $completed = $asset->isDataStateStored($dataState);
        }

        if (!$completed) {
            $this->logger->info(sprintf(
                'Discarding the processing results of asset with ID %s, as its data was replaced or restored in the meantime',
                $asset->getId()
            ));
            if ($generatePreviews) {
                $asset->clearThumbnails(true);
            }
        }

        return $completed;
    }

    /**
     * Returns the generation of the previews of the asset (see completeProcessing()), or null if there is nothing
     * to generate
     */
    private function getPreviewGenerator(Asset $asset): ?Closure
    {
        if ($asset instanceof Asset\Image) {
            return function () use ($asset): void {
                $asset->getThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);

                try {
                    $asset->generateLowQualityPreview();
                } catch (Exception $e) {
                    $this->logger->warning($e->getMessage());
                }
            };
        }

        if ($asset instanceof Asset\Document) {
            if (!$asset->isThumbnailsEnabled() || $asset->getCustomSetting(Asset::CUSTOM_SETTING_PROCESSING_FAILED)) {
                return null;
            }

            return function () use ($asset): void {
                $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
            };
        }

        if ($asset instanceof Asset\Video) {
            if (!$asset->getCustomSetting('videoWidth') || !$asset->getCustomSetting('videoHeight')) {
                return null;
            }

            return function () use ($asset): void {
                $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
            };
        }

        return null;
    }

    private function processDocument(Asset\Document $asset, string $dataState): void
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

        $generatePreviews = $this->getPreviewGenerator($asset);
        if ($save || $generatePreviews) {
            $this->completeProcessing($asset, $dataState, $save, $saveParams, $generatePreviews);
        }
    }

    private function processVideo(Asset\Video $asset, string $dataState): void
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

        $this->completeProcessing($asset, $dataState, true, [], $this->getPreviewGenerator($asset));
    }

    private function processImage(Asset\Image $image, string $dataState): void
    {
        // getDimensions() might fail, so assume `false` first
        $imageDimensionsCalculated = false;

        try {
            // getDimensionsFromFile() instead of getDimensions(): the latter writes the custom settings of the
            // loaded state to the database right away, bypassing the check whether the data changed in the
            // meantime (see completeProcessing())
            $dimensions = $image->getDimensionsFromFile($image->getLocalFile());
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

        // the previews are generated before the results are saved (see completeProcessing()). Saving the image
        // afterwards doesn't invalidate them, as the modification date of the image is kept.
        $this->completeProcessing($image, $dataState, true, [], $this->getPreviewGenerator($image));
    }
}
