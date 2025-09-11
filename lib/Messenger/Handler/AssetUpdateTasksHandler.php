<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Messenger\Handler;

use Exception;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Messenger\AssetUpdateTasksMessage;
use Pimcore\Model\Asset;
use Pimcore\Model\Version;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class AssetUpdateTasksHandler
{
    public function __construct(protected LoggerInterface $logger, protected LongRunningHelper $longRunningHelper)
    {
    }

    public function __invoke(AssetUpdateTasksMessage $message): void
    {
        $asset = Asset::getById($message->getId());
        if (!$asset) {
            $this->logger->debug(sprintf('Asset with ID %s not found', $message->getId()));

            return;
        }
        $this->logger->debug(sprintf('Processing asset with ID %s | Path: %s', $asset->getId(), $asset->getRealFullPath()));

        if ($asset instanceof Asset\Image) {
            $this->processImage($asset);
        } elseif ($asset instanceof Asset\Document) {
            $this->processDocument($asset);
        } elseif ($asset instanceof Asset\Video) {
            $this->processVideo($asset);
        }

        $this->longRunningHelper->deleteTemporaryFiles();
    }

    private function saveAsset(Asset $asset, array $saveParams = []): void
    {
        Version::disable();
        $asset->markFieldDirty('modificationDate'); // prevent modificationDate from being changed
        $asset->save($saveParams);
        Version::enable();
    }

    private function processDocument(Asset\Document $asset): void
    {
        $save = false;
        $saveParams = [];
        $asset->removeCustomSetting(Asset::CUSTOM_SETTING_UPDATE_TASK_PROCESSING_FAILED);
        if ($asset->getMimeType() === 'application/pdf' && $asset->checkIfPdfContainsJS()) {
            $save = true;
            $saveParams['versionNote'] = 'PDF scan result';
        }

        $pageCount = $asset->getCustomSetting('document_page_count');
        if (!$pageCount || $pageCount === 'failed') {
            if (!$asset->processPageCount() || $asset->getCustomSetting('document_page_count') === 'failed') {
                $asset->setCustomSetting(Asset::CUSTOM_SETTING_UPDATE_TASK_PROCESSING_FAILED, true);
            } else {
                $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
            }

            $save = true;
        }

        if ($save) {
            $this->saveAsset($asset, $saveParams);
        }
    }

    private function processVideo(Asset\Video $asset): void
    {
        $asset->removeCustomSetting(Asset::CUSTOM_SETTING_UPDATE_TASK_PROCESSING_FAILED);
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
            $asset->setCustomSetting(Asset::CUSTOM_SETTING_UPDATE_TASK_PROCESSING_FAILED, true);
            $asset->removeCustomSetting('duration');
            $asset->removeCustomSetting('videoWidth');
            $asset->removeCustomSetting('videoHeight');
        }

        $sphericalMetaData = $asset->getSphericalMetaDataFromBackend();
        if (!empty($sphericalMetaData)) {
            $asset->setCustomSetting('SphericalMetaData', $sphericalMetaData);
        } else {
            $asset->removeCustomSetting('SphericalMetaData');
        }

        $asset->handleEmbeddedMetaData();
        $this->saveAsset($asset);

        if ($asset->getCustomSetting('videoWidth') && $asset->getCustomSetting('videoHeight')) {
            $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
        }
    }

    private function processImage(Asset\Image $image): void
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

        try {
            $image->handleEmbeddedMetaData();
        } catch (Exception $e) {
            $this->logger->warning($e->getMessage());
        }

        $this->saveAsset($image);

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
