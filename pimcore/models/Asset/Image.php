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

class Asset_Image extends Asset {

    /**
     * @return void
     */
    public function update() {
        parent::update();

        $this->clearThumbnails();
    }

    /**
     * @return void
     */
    public function clearThumbnails() {
        $files = scandir(PIMCORE_TEMPORARY_DIRECTORY);
        foreach ($files as $file) {
            if (is_file(PIMCORE_TEMPORARY_DIRECTORY . "/" . $file)) {
                if (preg_match("/thumb_" . $this->getId() . "/", $file)) {
                    unlink(PIMCORE_TEMPORARY_DIRECTORY . "/" . $file);
                }
            }
        }
    }

    /**
     * @param  $config
     * @return Asset_Image_Thumbnail|bool|Thumbnail
     */
    public function getThumbnailConfig ($config) {

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
     * Returns a path to a given thumbnail or an thumbnail configuration
     *
     * @param mixed
     * @return string
     */
    public function getThumbnail($thumbnailName) {

        $thumbnail = $this->getThumbnailConfig($thumbnailName);
        $path = "";

        if($thumbnail) {
            try {
                $path = Asset_Image_Thumbnail_Processor::process($this, $thumbnail);
            } catch (Exception $e) {
                Logger::error("Couldn't create thumbnail of image " . $this->getFullPath());
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

    /**
     * @static
     * @throws Exception
     * @return null|Pimcore_Image_Adapter
     */
    public static function getImageTransformInstance () {

        try {
            $image = Pimcore_Image::getInstance();
        } catch (Exception $e) {
            $image = null;
        }

        if(!$image instanceof Pimcore_Image_Adapter){
            throw new Exception("Couldn't get instance of image tranform processor.");
        }

        return $image;
    }

    /**
     * @return string
     */
    public function getFormat() {
        if ($this->getWidth() > $this->getHeight()) {
            return "landscape";
        }
        else if ($this->getWidth() == $this->getHeight()) {
            return "square";
        }
        else if ($this->getHeight() > $this->getWidth()) {
            return "portrait";
        }
        return "unknown";
    }

    /**
     * @return string
     */
    public function getRelativeFileSystemPath() {
        return str_replace(PIMCORE_DOCUMENT_ROOT, "", $this->getFileSystemPath());
    }

    /**
     * @return array
     */
    public function getDimensions() {

        $image = self::getImageTransformInstance();

        $status = $image->load($this->getFileSystemPath());
        if($status === false) {
            return;
        }

        $dimensions = array(
            "width" => $image->getWidth(),
            "height" => $image->getHeight()
        );

        return $dimensions;
    }

    /**
     * @return int
     */
    public function getWidth() {
        $dimensions = $this->getDimensions();
        return $dimensions["width"];
    }

    /**
     * @return int
     */
    public function getHeight() {
        $dimensions = $this->getDimensions();
        return $dimensions["height"];
    }
}
