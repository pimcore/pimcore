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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Asset_Video extends Asset {

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
    public function getThumbnail($thumbnailName) {

        $thumbnail = $this->getThumbnailConfig($thumbnailName);

        if($thumbnail) {
            try {
                Asset_Video_Thumbnail_Processor::process($this, $thumbnail);
            } catch (Exception $e) {
                Logger::error("Couldn't create thumbnail of video " . $this->getFullPath());
                Logger::error($e);
            }

            // check for existing videos
            $customSetting = $this->getCustomSetting("thumbnails");
            if(is_array($customSetting) && array_key_exists($thumbnail->getName(), $customSetting)) {
                return $customSetting[$thumbnail->getName()];
            }
        }

        return null;
    }


    /**
     * @param  $config
     * @return Asset_Image_Thumbnail|bool|Thumbnail
     */
    public function getImageThumbnailConfig ($config) {

        if (is_string($config)) {
            try {
                $thumbnail = Asset_Image_Thumbnail_Config::getByName($config);
            }
            catch (Exception $e) {
                Logger::error("requested thumbnail " . $config . " is not defined");
                return false;
            }
        }
        else if (is_array($config)) {
            // check if it is a legacy config or a new one
            if(array_key_exists("items", $config)) {
                $thumbnail = Asset_Image_Thumbnail_Config::getByArrayConfig($config);
            } else {
                $thumbnail = Asset_Image_Thumbnail_Config::getByLegacyConfig($config);
            }
        }
        else if ($config instanceof Asset_Image_Thumbnail_Config) {
            $thumbnail = $config;
        }

        return $thumbnail;
    }

    /**
     * @param $thumbnailName
     */
    public function getImageThumbnail($thumbnailName, $timeOffset = null) {

        $thumbnail = $this->getImageThumbnailConfig($thumbnailName);
        $thumbnail->setName($thumbnail->getName()."-".$timeOffset);

        if(!$timeOffset) {
            $timeOffset = 5;
        }

        $converter = Pimcore_Video::getInstance();
        $converter->load($this->getFileSystemPath());
        $path = PIMCORE_TEMPORARY_DIRECTORY . "/video-thumbnail_" . $this->getId() . "__" .  $timeOffset . ".png";

        if(!is_file($path)) {
            $converter->saveImage($path, $timeOffset);
        }

        if($thumbnail) {
            try {
                $path = Asset_Image_Thumbnail_Processor::process($this, $thumbnail, $path);
            } catch (Exception $e) {
                Logger::error("Couldn't create image-thumbnail of video " . $this->getFullPath());
                Logger::error($e);
                return "/pimcore/static/img/image-not-supported.png";
            }
        }

        // if no thumbnail config is given return the original image
        if(empty($path)) {
            $fsPath = $this->getFileSystemPath();
            $path = str_replace(PIMCORE_DOCUMENT_ROOT, "", $fsPath);
            return $path;
        }

        return $path;
    }
}
