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

namespace Pimcore\Model\Asset\Video;

use Exception;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Pimcore;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\FrontendEvents;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Exception\ThumbnailFormatNotSupportedException;
use Pimcore\Tool\Storage;
use Pimcore\Video;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Lock\LockFactory;

/**
 * @property Model\Asset\Video|null $asset
 */
final class ImageThumbnail implements ImageThumbnailInterface
{
    use Model\Asset\Thumbnail\ImageThumbnailTrait;

    /**
     * @internal
     *
     */
    protected ?int $timeOffset = null;

    /**
     * @internal
     *
     */
    protected ?Image $imageAsset = null;

    public function __construct(?Model\Asset\Video $asset, array|string|Image\Thumbnail\Config|null $config = null, ?int $timeOffset = null, ?Image $imageAsset = null, bool $deferred = true)
    {
        $this->asset = $asset;
        $this->timeOffset = $timeOffset;
        $this->imageAsset = $imageAsset;
        $this->config = $this->createConfig($config ?? []);
        $this->deferred = $deferred;
    }

    public function getPath(array $args = []): string
    {
        // set defaults
        $deferredAllowed = $args['deferredAllowed'] ?? true;
        $frontend = $args['frontend'] ?? \Pimcore\Tool::isFrontend();

        $pathReference = $this->getPathReference($deferredAllowed);

        $path = $this->convertToWebPath($pathReference, $frontend);

        $event = new GenericEvent($this, [
            'pathReference' => $pathReference,
            'frontendPath' => $path,
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, FrontendEvents::ASSET_VIDEO_IMAGE_THUMBNAIL);
        $path = $event->getArgument('frontendPath');

        return $path;
    }

    /**
     * @throws Exception|FilesystemException|ThumbnailFormatNotSupportedException
     *
     * @internal
     */
    public function generate(bool $deferredAllowed = true): void
    {
        $deferred = $deferredAllowed && $this->deferred;
        $generated = false;

        if ($this->asset instanceof Model\Asset\Video && empty($this->pathReference)) {

            if (!$this->checkAllowedFormats($this->config->getFormat(), $this->asset)) {
                throw new ThumbnailFormatNotSupportedException();
            }

            $cs = $this->asset->getCustomSetting('image_thumbnail_time');
            $im = $this->asset->getCustomSetting('image_thumbnail_asset');

            if ($im || $this->imageAsset) {
                if ($this->imageAsset) {
                    $im = $this->imageAsset;
                } else {
                    $im = Model\Asset::getById($im);
                }

                if ($im instanceof Image) {
                    $imageThumbnail = $im->getThumbnail($this->getConfig());
                    $this->pathReference = $imageThumbnail->getPathReference();
                }
            }

            if (!$this->pathReference) {
                $timeOffset = $this->timeOffset;
                if (!is_numeric($timeOffset) && is_numeric($cs)) {
                    $timeOffset = $cs;
                }

                // fallback
                if (!is_numeric($timeOffset)) {
                    $timeOffset = ceil($this->asset->getDuration() / 3);
                }

                $storage = Storage::get('asset_cache');
                $cacheFilePath = sprintf(
                    '%s/%s/image-thumb__%s__video_original_image/time_%s.png',
                    rtrim($this->asset->getRealPath(), '/'),
                    $this->asset->getId(),
                    $this->asset->getId(),
                    $timeOffset
                );

                $imageAvailable = $storage->fileExists($cacheFilePath);
                if (!$imageAvailable) {
                    $lock = Pimcore::getContainer()->get(LockFactory::class)->createLock($cacheFilePath);
                    $lock->acquire(true);

                    try {
                        // after we got the lock, check again if the image exists in the meantime - if not - generate it
                        $imageAvailable = $storage->fileExists($cacheFilePath);
                        if (!$imageAvailable) {
                            $generated = $this->writeOriginalImage(
                                $this->asset,
                                $storage,
                                $cacheFilePath,
                                (int) $timeOffset
                            );
                            $imageAvailable = $generated;
                        }
                    } finally {
                        $lock->release();
                    }
                }

                // if the original image could not be extracted from the video (e.g. no video adapter available or a
                // broken/missing video file), don't bail out here, so that the error path reference below is used
                if ($imageAvailable && $this->getConfig()) {
                    $cacheFileStream = $storage->readStream($cacheFilePath);

                    $this->getConfig()->setFilenameSuffix('time-' . $timeOffset);

                    try {
                        $this->pathReference = Image\Thumbnail\Processor::process(
                            $this->asset,
                            $this->getConfig(),
                            $cacheFileStream,
                            $deferred,
                            $generated
                        );
                    } catch (Exception $e) {
                        Logger::error("Couldn't create image-thumbnail of video " . $this->asset->getRealFullPath() . ': ' . $e);
                    }
                }
            }
        }

        if (empty($this->pathReference)) {
            $this->pathReference = $this->getErrorPathReference();
        }

        $event = new GenericEvent($this, [
            'deferred' => $deferred,
            'generated' => $generated,
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, AssetEvents::VIDEO_IMAGE_THUMBNAIL);
    }

    /**
     * Extracts the frame at the given time offset from the video and writes it to the asset cache storage.
     *
     * @return bool whether the image has been written to the asset cache storage
     *
     * @throws Exception|FilesystemException
     */
    private function writeOriginalImage(
        Model\Asset\Video $asset,
        FilesystemOperator $storage,
        string $cacheFilePath,
        int $timeOffset
    ): bool {
        $tempFile = File::getLocalTempFilePath('png');
        $converter = Video::newInstance();
        if ($converter === null) {
            Logger::error('No video adapter available to create image thumbnail for video ' . $asset->getRealFullPath() . '.');

            return false;
        }

        $converter->load($asset->getLocalFile());
        if (false === $converter->saveImage($tempFile, $timeOffset)) {
            Logger::info('Creation of image thumbnail for video ' . $asset->getRealFullPath() . ' failed.');

            return false;
        }

        $tempFileContent = file_get_contents($tempFile);
        if (false === $tempFileContent) {
            Logger::info('Could not read temporary image thumbnail file for video ' . $asset->getRealFullPath() . '.');

            return false;
        }

        $storage->write($cacheFilePath, $tempFileContent);

        return true;
    }

    /**
     * Get the public path to the thumbnail image.
     * This method is here for backwards compatility.
     * Up to Pimcore 1.4.8 a thumbnail was returned as a path to an image.
     *
     * @return string Public path to thumbnail image.
     */
    public function __toString(): string
    {
        return $this->getPath();
    }

    /**
     * @throws Model\Exception\NotFoundException
     */
    private function createConfig(array|string|Image\Thumbnail\Config $selector): ?Image\Thumbnail\Config
    {
        $thumbnailConfig = Image\Thumbnail\Config::getByAutoDetect($selector);

        if (!empty($selector) && $thumbnailConfig === null) {
            throw new Model\Exception\NotFoundException('Thumbnail definition "' . (is_string($selector) ? $selector : '') . '" does not exist');
        }

        return $thumbnailConfig;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function getMedia(string $name, int $highRes = 1): ?Image\ThumbnailInterface
    {
        $thumbConfig = $this->getConfig();
        if ($thumbConfig instanceof Image\Thumbnail\Config) {
            $mediaConfigs = $thumbConfig->getMedias();

            if (isset($mediaConfigs[$name])) {
                $thumbConfigRes = clone $thumbConfig;
                $thumbConfigRes->selectMedia($name);
                $thumbConfigRes->setHighResolution($highRes);
                $thumbConfigRes->setMedias([]);
                $imgId = $this->asset->getCustomSetting('image_thumbnail_asset');
                $img = Model\Asset::getById($imgId);

                if ($img instanceof Image) {
                    $thumb = $img->getThumbnail($thumbConfigRes);
                }

                return $thumb ?? null;
            } else {
                throw new Exception("Media query '" . $name . "' doesn't exist in thumbnail configuration: " . $thumbConfig->getName());
            }
        }

        return null;
    }
}
