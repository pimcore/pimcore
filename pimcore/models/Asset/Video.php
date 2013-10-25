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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Asset_Video extends Asset {

    /**
     * @var string
     */
    public $type = "video";

    /**
     * @return void
     */
    protected function update() {
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
     * @return Asset_Video_Thumbnail|null
     */
    public function getThumbnailConfig ($config) {

        $thumbnail = null;

        if (is_string($config)) {
            try {
                $thumbnail = Asset_Video_Thumbnail_Config::getByName($config);
            }
            catch (Exception $e) {
                Logger::error("requested video-thumbnail " . $config . " is not defined");
                return null;
            }
        } else if ($config instanceof Asset_Video_Thumbnail_Config) {
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
                Asset_Video_Thumbnail_Processor::process($this, $thumbnail, $onlyFormats);

                // check for existing videos
                $customSetting = $this->getCustomSetting("thumbnails");
                if(is_array($customSetting) && array_key_exists($thumbnail->getName(), $customSetting)) {
                    return $customSetting[$thumbnail->getName()];
                }

            } catch (Exception $e) {
                Logger::error("Couldn't create thumbnail of video " . $this->getFullPath());
                Logger::error($e);
            }
        }

        return null;
    }


    /**
     * @param  $config
     * @return Asset_Image_Thumbnail|bool|Thumbnail
     */
    public function getImageThumbnailConfig ($config) {
        return Asset_Image_Thumbnail_Config::getByAutoDetect($config);
    }

    /**
     * @param $thumbnailName
     */
    public function getImageThumbnail($thumbnailName, $timeOffset = null, $imageAsset = null) {

        if(!Pimcore_Video::isAvailable()) {
            Logger::error("Couldn't create image-thumbnail of video " . $this->getFullPath() . " no video adapter is available");
            return "/pimcore/static/img/filetype-not-supported.png";
        }

        $cs = $this->getCustomSetting("image_thumbnail_time");
        $im = $this->getCustomSetting("image_thumbnail_asset");
        if(!$timeOffset && !$imageAsset && $cs) {
            $timeOffset = $cs;
        } else if (!$timeOffset && !$imageAsset && $im) {
            $imageAsset = Asset::getById($im);
        }

        // fallback
        if(!$timeOffset && !$imageAsset) {
            $timeOffset = 5;
        }

        if($imageAsset instanceof Asset_Image) {
            return $imageAsset->getThumbnail($thumbnailName);
        }

        $thumbnail = $this->getImageThumbnailConfig($thumbnailName);
        $thumbnail->setName($thumbnail->getName()."-".$timeOffset);

        $converter = Pimcore_Video::getInstance();
        $converter->load($this->getFileSystemPath());
        $path = PIMCORE_TEMPORARY_DIRECTORY . "/video-image-cache/video_" . $this->getId() . "__thumbnail_" .  $timeOffset . ".png";
        if(!is_dir(dirname($path))) {
            Pimcore_File::mkdir(dirname($path));
        }

        if(!is_file($path)) {
            $converter->saveImage($path, $timeOffset);
        }

        if($thumbnail) {
            try {
                $path = Asset_Image_Thumbnail_Processor::process($this, $thumbnail, $path);
            } catch (Exception $e) {
                Logger::error("Couldn't create image-thumbnail of video " . $this->getFullPath());
                Logger::error($e);
                return "/pimcore/static/img/filetype-not-supported.png";
            }
        }

        // if no thumbnail config is given return the original image
        if(empty($path)) {
            $fsPath = $this->getFileSystemPath();
            $path = str_replace(PIMCORE_DOCUMENT_ROOT, "", $fsPath);
        }

        return $path;
    }

    /**
     * @return mixed
     */
    public function getDuration () {
        $converter = Pimcore_Video::getInstance();
        $converter->load($this->getFileSystemPath());

        return $converter->getDuration();
    }
}
