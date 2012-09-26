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

        try {
            // save the current data into a tmp file to calculate the dimensions, otherwise updates wouldn't be updated
            // because the file is written in parent::update();
            $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . uniqid();
            file_put_contents($tmpFile, $this->getData());
            $dimensions = $this->getDimensions($tmpFile);
            unlink($tmpFile);

            if($dimensions && $dimensions["width"]) {
                $this->setCustomSetting("imageWidth", $dimensions["width"]);
                $this->setCustomSetting("imageHeight", $dimensions["height"]);
            }
        } catch (Exception $e) {
            Logger::error("Problem getting the dimensions of the image with ID " . $this->getId());
        }

        // this is to be downward compatible so that the controller can check if the dimensions are already calculated
        // and also to just do the calculation once, because the calculation can fail, an then the controller tries to
        // calculate the dimensions on every request an also will create a version, ...
        $this->setCustomSetting("imageDimensionsCalculated", true);

        parent::update();

        $this->clearThumbnails();

        // @TODO: this is for the upload useless, because there the non-specific Asset class is initialized
        // now directly create "system" thumbnails (eg. for the tree, ...)
        try {
            $this->getThumbnail($this->getThumbnailConfig(array(
                "width" => 130,
                "aspectratio" => true
            )));
        } catch (Exception $e) {
            Logger::error("Problem while creating system-thumbnails for image " . $this->getFullPath());
            Logger::error($e);
        }
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
                return "/pimcore/static/img/filetype-not-supported.png";
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
    public function getDimensions($path = null) {

        if(!$path) {
            $path = $this->getFileSystemPath();
        }

        $image = self::getImageTransformInstance();

        $status = $image->load($path);
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
