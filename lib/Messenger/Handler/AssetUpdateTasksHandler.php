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
use RuntimeException;

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

    private function saveAsset(Asset $asset): void
    {
        Version::disable();
        $asset->markFieldDirty('modificationDate'); // prevent modificationDate from being changed
        $asset->save();
        Version::enable();
    }

    private function processDocument(Asset\Document $asset): void
    {
        if ($asset->getMimeType() === 'application/pdf' && $asset->checkIfPdfContainsJS()) {
            $asset->save(['versionNote' => 'PDF scan result']);
        }

        $pageCount = $asset->getCustomSetting('document_page_count');
        if (!$pageCount || $pageCount === 'failed') {
            if ($asset->processPageCount()) {
                $this->saveAsset($asset);
            }

            if ($asset->getCustomSetting('document_page_count') === 'failed') {
                throw new RuntimeException(sprintf('Failed processing page count for document asset %s.', $asset->getId()));
            }
        }

        $asset->getImageThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->generate(false);
    }

    private function processVideo(Asset\Video $asset): void
    {
        if ($duration = $asset->getDurationFromBackend()) {
            $asset->setCustomSetting('duration', $duration);
        } else {
            $asset->removeCustomSetting('duration');
        }

        if ($dimensions = $asset->getDimensionsFromBackend()) {
            $asset->setCustomSetting('videoWidth', $dimensions['width']);
            $asset->setCustomSetting('videoHeight', $dimensions['height']);
        } else {
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
