<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Asset
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Asset\Video;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\FrontendEvents;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset\Image;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Lock\Factory as LockFactory;

class ImageThumbnail
{
    use Model\Asset\Thumbnail\ImageThumbnailTrait;

    /**
     * @var int
     */
    protected $timeOffset;

    /**
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
        $fsPath = $this->getFileSystemPath($deferredAllowed);
        $path = $this->convertToWebPath($fsPath);

        $event = new GenericEvent($this, [
            'filesystemPath' => $fsPath,
            'frontendPath' => $path,
        ]);
        \Pimcore::getEventDispatcher()->dispatch(FrontendEvents::ASSET_VIDEO_IMAGE_THUMBNAIL, $event);
        $path = $event->getArgument('frontendPath');

        return $path;
    }

    /**
     * @param bool $deferredAllowed
     *
     * @throws \Exception
     */
    public function generate($deferredAllowed = true)
    {
        $errorImage = PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img/filetype-not-supported.svg';
        $deferred = $deferredAllowed && $this->deferred;
        $generated = false;

        if (!$this->asset) {
            $this->filesystemPath = $errorImage;
        } elseif (!$this->filesystemPath) {
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
                    $this->filesystemPath = $imageThumbnail->getFileSystemPath();
                }
            }

            if (!$this->filesystemPath) {
                $timeOffset = $this->timeOffset;
                if (!$this->timeOffset && $cs) {
                    $timeOffset = $cs;
                }

                // fallback
                if (!$timeOffset) {
                    $timeOffset = ceil($this->asset->getDuration() / 3);
                }

                $converter = \Pimcore\Video::getInstance();
                $converter->load($this->asset->getFileSystemPath());
                $path = $this->asset->getImageThumbnailSavePath() . '/video-image-cache__' . $this->asset->getId() . '__thumbnail_' . $timeOffset . '.png';

                if (!is_dir(dirname($path))) {
                    File::mkdir(dirname($path));
                }

                if (!is_file($path)) {
                    $lockKey = 'video_image_thumbnail_' . $this->asset->getId() . '_' . $timeOffset;
                    $lock = \Pimcore::getContainer()->get(LockFactory::class)->createLock($lockKey);
                    $lock->acquire(true);

                    // after we got the lock, check again if the image exists in the meantime - if not - generate it
                    if (!is_file($path)) {
                        $converter->saveImage($path, $timeOffset);
                        $generated = true;
                    }

                    $lock->release();
                }

                if ($this->getConfig()) {
                    $this->getConfig()->setFilenameSuffix('time-' . $timeOffset);

                    try {
                        // The path can be remote. In that case, the processor will create a local copy of the asset, which is the video itself.
                        // That is not what is intended, as we are tying to generate a thumbnail based on the already existing video still that
                        // the converter created earlier. To prevent the processor from doing that, we will create a local copy here if needed
                        if (!stream_is_local($path)) {
                            $path = $this->asset->getTemporaryFile();
                        }

                        $path = Image\Thumbnail\Processor::process(
                            $this->asset,
                            $this->getConfig(),
                            $path,
                            $deferred,
                            true,
                            $generated
                        );
                    } catch (\Exception $e) {
                        Logger::error("Couldn't create image-thumbnail of video " . $this->asset->getRealFullPath());
                        Logger::error($e);
                        $path = $errorImage;
                    }
                }

                $this->filesystemPath = $path;
            }

            \Pimcore::getEventDispatcher()->dispatch(AssetEvents::VIDEO_IMAGE_THUMBNAIL, new GenericEvent($this, [
                'deferred' => $deferred,
                'generated' => $generated,
            ]));
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
     */
    protected function createConfig($selector)
    {
        return Image\Thumbnail\Config::getByAutoDetect($selector);
    }
}
