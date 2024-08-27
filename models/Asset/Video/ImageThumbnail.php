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

namespace Pimcore\Model\Asset\Video;

use Exception;
use Pimcore;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\FrontendEvents;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Exception\ThumbnailFormatNotSupportedException;
use Pimcore\Tool\Storage;
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

    public function __construct(?Model\Asset\Video $asset, array|string|Image\Thumbnail\Config $config = null, int $timeOffset = null, Image $imageAsset = null, bool $deferred = true)
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
     * @throws Exception|\League\Flysystem\FilesystemException|ThumbnailFormatNotSupportedException
     *
     * @internal
     */
    public function generate(bool $deferredAllowed = true): void
    {
        $deferred = $deferredAllowed && $this->deferred;
        $generated = false;

        if ($this->asset && empty($this->pathReference)) {

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

            if (empty($this->pathReference)) {
                $timeOffset = $this->timeOffset;
                if (!is_numeric($timeOffset) && is_numeric($cs)) {
                    $timeOffset = $cs;
                }

                // fallback
                if (!is_numeric($timeOffset) && $this->asset instanceof Model\Asset\Video) {
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

                if (!$storage->fileExists($cacheFilePath)) {
                    $lock = Pimcore::getContainer()->get(LockFactory::class)->createLock($cacheFilePath);
                    $lock->acquire(true);

                    // after we got the lock, check again if the image exists in the meantime - if not - generate it
                    if (!$storage->fileExists($cacheFilePath)) {
                        $tempFile = File::getLocalTempFilePath('png');
                        $converter = \Pimcore\Video::getInstance();
                        $converter->load($this->asset->getLocalFile());
                        $converter->saveImage($tempFile, (int) $timeOffset);
                        $generated = true;
                        $storage->write($cacheFilePath, file_get_contents($tempFile));
                    }

                    $lock->release();
                }

                $cacheFileStream = $storage->readStream($cacheFilePath);

                if ($this->getConfig()) {
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
            $this->pathReference = [
                'type' => 'error',
                'src' => '/bundles/pimcoreadmin/img/filetype-not-supported.svg',
            ];
        }

        $event = new GenericEvent($this, [
            'deferred' => $deferred,
            'generated' => $generated,
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, AssetEvents::VIDEO_IMAGE_THUMBNAIL);
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
