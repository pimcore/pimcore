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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\Video;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\FrontendEvents;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset\Image;
use Pimcore\Tool\Storage;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Lock\LockFactory;

final class ImageThumbnail
{
    use Model\Asset\Thumbnail\ImageThumbnailTrait;

    /**
     * @internal
     *
     * @var int
     */
    protected $timeOffset;

    /**
     * @internal
     *
     * @var Image|null
     */
    protected $imageAsset;

    /**
     * @param Model\Asset\Video $asset
     * @param string|array|Image\Thumbnail\Config|null $config
     * @param int|null $timeOffset
     * @param Image|null $imageAsset
     * @param bool $deferred
     */
    public function __construct($asset, $config = null, $timeOffset = null, $imageAsset = null, $deferred = true)
    {
        $this->asset = $asset;
        $this->timeOffset = $timeOffset;
        $this->imageAsset = $imageAsset;
        $this->config = $this->createConfig($config);
        $this->deferred = $deferred;
    }

    /**
     * @param bool $deferredAllowed
     *
     * @return string
     */
    public function getPath($deferredAllowed = true)
    {
        $pathReference = $this->getPathReference($deferredAllowed);
        $path = $this->convertToWebPath($pathReference);

        $event = new GenericEvent($this, [
            'pathReference' => $pathReference,
            'frontendPath' => $path,
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, FrontendEvents::ASSET_VIDEO_IMAGE_THUMBNAIL);
        $path = $event->getArgument('frontendPath');

        return $path;
    }

    /**
     * @internal
     *
     * @param bool $deferredAllowed
     *
     * @throws \Exception
     */
    public function generate($deferredAllowed = true)
    {
        $deferred = $deferredAllowed && $this->deferred;
        $generated = false;

        if ($this->asset && empty($this->pathReference)) {
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
                if (!$this->timeOffset && $cs) {
                    $timeOffset = $cs;
                }

                // fallback
                if (!$timeOffset && $this->asset instanceof Model\Asset\Video) {
                    $timeOffset = ceil($this->asset->getDuration() / 3);
                }

                $storage = Storage::get('asset_cache');
                $cacheFilePath = sprintf('%s/image-thumb__%s__video_original_image/time_%s.png',
                    rtrim($this->asset->getRealPath(), '/'),
                    $this->asset->getId(),
                    $timeOffset
                );

                if (!$storage->fileExists($cacheFilePath)) {
                    $lock = \Pimcore::getContainer()->get(LockFactory::class)->createLock($cacheFilePath);
                    $lock->acquire(true);

                    // after we got the lock, check again if the image exists in the meantime - if not - generate it
                    if (!$storage->fileExists($cacheFilePath)) {
                        $tempFile = File::getLocalTempFilePath('png');
                        $converter = \Pimcore\Video::getInstance();
                        $converter->load($this->asset->getLocalFile());
                        $converter->saveImage($tempFile, $timeOffset);
                        $generated = true;
                        $storage->write($cacheFilePath, file_get_contents($tempFile));
                        unlink($tempFile);
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
                    } catch (\Exception $e) {
                        Logger::error("Couldn't create image-thumbnail of video " . $this->asset->getRealFullPath());
                        Logger::error($e);
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
            \Pimcore::getEventDispatcher()->dispatch($event, AssetEvents::VIDEO_IMAGE_THUMBNAIL);
        }
    }

    /**
     * Get the public path to the thumbnail image.
     * This method is here for backwards compatility.
     * Up to Pimcore 1.4.8 a thumbnail was returned as a path to an image.
     *
     * @return string Public path to thumbnail image.
     */
    public function __toString()
    {
        return $this->getPath();
    }

    /**
     * @param string|array|Image\Thumbnail\Config $selector
     *
     * @return Image\Thumbnail\Config|null
     *
     * @throws Model\Exception\NotFoundException
     */
    private function createConfig($selector)
    {
        $thumbnailConfig = Image\Thumbnail\Config::getByAutoDetect($selector);

        if (!empty($selector) && $thumbnailConfig === null) {
            throw new Model\Exception\NotFoundException('Thumbnail definition "' . (is_string($selector) ? $selector : '') . '" does not exist');
        }

        return $thumbnailConfig;
    }

    /**
     * @param string $name
     * @param int $highRes
     *
     * @return Image\Thumbnail|null
     *
     * @throws \Exception
     */
    public function getMedia($name, $highRes = 1)
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
                throw new \Exception("Media query '" . $name . "' doesn't exist in thumbnail configuration: " . $thumbConfig->getName());
            }
        }

        return null;
    }
}
