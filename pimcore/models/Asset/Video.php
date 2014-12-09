<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Asset
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Asset;

use Pimcore\File;
use Pimcore\Model;

class Video extends Model\Asset {

    /**
     * @var string
     */
    public $type = "video";

    /**
     * @return void
     */
    protected function update() {

        // only do this if the file exists and contains data
        if($this->getDataChanged() || !$this->getCustomSetting("duration")) {
            try {
                $this->setCustomSetting("duration", $this->getDurationFromBackend());
            } catch (\Exception $e) {
                \Logger::err("Unable to get duration of video: " . $this->getId());
            }
        }

        $this->clearThumbnails();
        parent::update();
    }

    /**
     *
     */
    public function delete() {
        parent::delete();
        $this->clearThumbnails();
    }

    /**
     * @return void
     */
    public function clearThumbnails($force = false) {

        if($this->_dataChanged || $force) {
            // clear the thumbnail custom settings
            $this->setCustomSetting("thumbnails", null);

            // video thumbnails and image previews
            $files = glob(PIMCORE_TEMPORARY_DIRECTORY . "/video-image-cache/video_" . $this->getId() . "__*");
            if(is_array($files)) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }

            recursiveDelete($this->getImageThumbnailSavePath());
            recursiveDelete($this->getVideoThumbnailSavePath());
        }
    }

    /**
     * @param string $config
     * @return Video\Thumbnail\Config|null
     */
    public function getThumbnailConfig ($config) {

        $thumbnail = null;

        if (is_string($config)) {
            try {
                $thumbnail = Video\Thumbnail\Config::getByName($config);
            }
            catch (\Exception $e) {
                \Logger::error("requested video-thumbnail " . $config . " is not defined");
                return null;
            }
        } else if ($config instanceof Video\Thumbnail\Config) {
            $thumbnail = $config;
        }

        return $thumbnail;
    }

    /**
     * Returns a path to a given thumbnail or an thumbnail configuration
     *
     * @param mixed
     * @return string
     */
    public function getThumbnail($thumbnailName, $onlyFormats = array()) {

        $thumbnail = $this->getThumbnailConfig($thumbnailName);

        if($thumbnail) {
            try {
                Video\Thumbnail\Processor::process($this, $thumbnail, $onlyFormats);

                // check for existing videos
                $customSetting = $this->getCustomSetting("thumbnails");
                if(is_array($customSetting) && array_key_exists($thumbnail->getName(), $customSetting)) {
                    return $customSetting[$thumbnail->getName()];
                }

            } catch (\Exception $e) {
                \Logger::error("Couldn't create thumbnail of video " . $this->getFullPath());
                \Logger::error($e);
            }
        }

        return null;
    }


    /**
     * @param  $config
     * @return Image\Thumbnail\Config|bool|Thumbnail
     */
    public function getImageThumbnailConfig ($config) {
        return Image\Thumbnail\Config::getByAutoDetect($config);
    }

    /**
     * @param $thumbnailName
     * @param null $timeOffset
     * @param null $imageAsset
     * @return mixed|string
     * @throws \Exception
     */
    public function getImageThumbnail($thumbnailName, $timeOffset = null, $imageAsset = null) {

        if(!\Pimcore\Video::isAvailable()) {
            \Logger::error("Couldn't create image-thumbnail of video " . $this->getFullPath() . " no video adapter is available");
            return "/pimcore/static/img/filetype-not-supported.png";
        }

        $cs = $this->getCustomSetting("image_thumbnail_time");
        $im = $this->getCustomSetting("image_thumbnail_asset");

        if($im || $imageAsset) {
            if($im) {
                $imageAsset = Model\Asset::getById($im);
            }

            if($imageAsset instanceof Image) {
                return $imageAsset->getThumbnail($thumbnailName);
            }
        }

        if(!$timeOffset && $cs) {
            $timeOffset = $cs;
        }

        // fallback
        if(!$timeOffset) {
            $timeOffset = ceil($this->getDuration() / 3);
        }

        $converter = \Pimcore\Video::getInstance();
        $converter->load($this->getFileSystemPath());
        $path = PIMCORE_TEMPORARY_DIRECTORY . "/video-image-cache/video_" . $this->getId() . "__thumbnail_" .  $timeOffset . ".png";
        if(!is_dir(dirname($path))) {
            File::mkdir(dirname($path));
        }

        if(!is_file($path)) {
            $lockKey = "video_image_thumbnail_" . $this->getId() . "_" . $timeOffset;
            Model\Tool\Lock::acquire($lockKey);

            // after we got the lock, check again if the image exists in the meantime - if not - generate it
            if(!is_file($path)) {
                $converter->saveImage($path, $timeOffset);
            }

            Model\Tool\Lock::release($lockKey);
        }

        $thumbnail = $this->getImageThumbnailConfig($thumbnailName);

        if($thumbnail) {
            $thumbnail->setFilenameSuffix("time-" . $timeOffset);

            try {
                $path = Image\Thumbnail\Processor::process($this, $thumbnail, $path);
            } catch (\Exception $e) {
                \Logger::error("Couldn't create image-thumbnail of video " . $this->getFullPath());
                \Logger::error($e);
                return "/pimcore/static/img/filetype-not-supported.png";
            }
        }

        $path = preg_replace("@^" . preg_quote(PIMCORE_DOCUMENT_ROOT, "@") . "@", "", $path);

        return $path;
    }

    /**
     * how many frames, delay in seconds between frames, pimcore thumbnail configuration
     *
     * @param int $frames
     * @param int $delay
     * @param null $thumbnail
     * @return string
     */
    public function getPreviewAnimatedGif($frames = 10, $delay = 200, $thumbnail = null) {

        if(!$frames) {
            $frames = 10;
        }
        if(!$delay) {
            $delay = 200; // no clue which unit this has ;-)
        }

        $thumbnailUniqueId = md5(serialize([$thumbnail, $frames, $delay]));
        $animGifPath = PIMCORE_TEMPORARY_DIRECTORY . "/video-image-cache/video_" . $this->getId() . "_" . $thumbnailUniqueId . ".gif";

        if(!is_file($animGifPath)) {
            $duration = $this->getDuration();
            $sampleRate = floor($duration / $frames);

            $thumbnails = [];
            $delays = [];

            $thumbnailConfig = $this->getImageThumbnailConfig($thumbnail);
            if(!$thumbnailConfig) {
                $thumbnailConfig = new Image\Thumbnail\Config();
            }
            $thumbnailConfig->setFormat("GIF");

            for($i=0; $i<=$frames; $i++) {
                $frameImage = $this->getImageThumbnail($thumbnailConfig, $i*$sampleRate);
                $frameImage = PIMCORE_DOCUMENT_ROOT . $frameImage;

                if(preg_match("/\.gif$/", $frameImage) && filesize($frameImage) > 10) {
                    // check if the image is correct and not a "not supported" placeholder
                    $thumbnails[] = $frameImage;
                    $delays[] = $delay;
                }
            }

            try {
                $animator = new \Pimcore\Image\GifAnimator($thumbnails, $delays, 0, 2, 255, 255, 255, "url");
                $animGifContent = $animator->GetAnimation();
            } catch (\Exception $e) {
                \Logger::error($e);
                $animGifContent = file_get_contents($thumbnails[0]);
            }

            File::put($animGifPath, $animGifContent);
        }

        $animGifPath = preg_replace("@^" . preg_quote(PIMCORE_DOCUMENT_ROOT, "@") . "@", "", $animGifPath);

        return $animGifPath;
    }

    /**
     * @return null
     * @throws \Exception
     */
    protected function getDurationFromBackend() {
        if(\Pimcore\Video::isAvailable()) {
            $converter = \Pimcore\Video::getInstance();
            $converter->load($this->getFileSystemPath());
            return $converter->getDuration();
        }
        return null;
    }

    /**
     * @return mixed
     */
    public function getDuration () {
        $duration = $this->getCustomSetting("duration");
        if(!$duration) {
            $duration = $this->getDurationFromBackend();
            $this->setCustomSetting("duration", $duration);

            Model\Version::disable();
            $this->save(); // auto save
            Model\Version::enable();
        }

        return $duration;
    }
}
