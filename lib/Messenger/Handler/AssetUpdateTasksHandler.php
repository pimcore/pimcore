<?php

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

use Pimcore\Logger;
use Pimcore\Messenger\AssetUpdateTasksMessage;
use Pimcore\Model\Asset;
use Pimcore\Model\Version;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

/**
 * @internal
 */
class AssetUpdateTasksHandler
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    public function __invoke(AssetUpdateTasksMessage $message)
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
    }

    private function saveAsset(Asset $asset)
    {
        Version::disable();
        $asset->save();
        Version::enable();
    }

    private function processDocument(Asset\Document $asset)
    {
        if (!$asset->getCustomSetting('document_page_count')) {
            $asset->processPageCount();
            $this->saveAsset($asset);
        }

        $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
    }

    private function processVideo(Asset\Video $asset): void
    {
        try {
            $asset->setCustomSetting('duration', $asset->getDurationFromBackend());
        } catch (\Exception $e) {
            Logger::err('Unable to get duration of video: ' . $asset->getId());

            throw new UnrecoverableMessageHandlingException($e->getMessage(), 0, $e);
        }

        try {
            $dimensions = $asset->getDimensionsFromBackend();
            if ($dimensions) {
                $asset->setCustomSetting('videoWidth', $dimensions['width']);
                $asset->setCustomSetting('videoHeight', $dimensions['height']);
            } else {
                $asset->removeCustomSetting('videoWidth');
                $asset->removeCustomSetting('videoHeight');
            }
        } catch (\Exception $e) {
            Logger::err('Unable to get dimensions of video: ' . $asset->getId());

            throw new UnrecoverableMessageHandlingException($e->getMessage(), 0, $e);
        }

        $asset->handleEmbeddedMetaData(true);
        $this->saveAsset($asset);

        $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
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
        } catch (\Exception $e) {
            $this->logger->warning('Problem getting the dimensions of the image with ID ' . $image->getId());
        }

        // this is to be downward compatible so that the controller can check if the dimensions are already calculated
        // and also to just do the calculation once, because the calculation can fail, an then the controller tries to
        // calculate the dimensions on every request an also will create a version, ...
        $image->setCustomSetting('imageDimensionsCalculated', $imageDimensionsCalculated);

        $customSettings = $image->getCustomSettings();

        if (!isset($customSettings['disableImageFeatureAutoDetection'])) {
            $image->detectFaces();
        }

        if (!isset($customSettings['disableFocalPointDetection'])) {
            $image->detectFocalPoint();
        }

        try {
            $image->handleEmbeddedMetaData(true);
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }

        $this->saveAsset($image);

        // generating the thumbnails must be after saving the image, because otherwise the generated
        // thumbnail would be invalidated on the next call, because it's older than the modification date of the asset
        $image->getThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);

        try {
            $image->generateLowQualityPreview();
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }
    }
}
