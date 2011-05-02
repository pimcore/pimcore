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

    public $thumbnails = array();

    /**
     * Get the current name of the class
     *
     * @return string
     */
    public static function getClassName() {
        return __CLASS__;
    }

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
                $thumbnail = Asset_Image_Thumbnail::getByName($config);
            }
            catch (Exception $e) {
                Logger::error("requested thumbnail " . $config . " is not defined");
                return false;
            }
        }
        else if (is_array($config)) {
            $thumbnail = new Asset_Image_Thumbnail();

            $hash = md5(serialize($config));

            $thumbnail->setName("auto_" . $hash);
            $thumbnail->setValues($config);
        }
        else if ($config instanceof Asset_Image_Thumbnail) {
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
        
        if(!$thumbnail) {
            $fsPath = $this->getFileSystemPath();
            $path = str_replace(PIMCORE_DOCUMENT_ROOT, "", $fsPath);
            return $path;
        }
        
        $format = strtolower($thumbnail->getFormat());
        
        // simple detection for source type if SOURCE is selected 
        if($format == "source") {
            $typeMapping = array(
                "gif" => "gif",
                "jpeg" => "jpeg",
                "jpg" => "jpeg",
                "png" => "png"
            );
            
            $fileExt = Pimcore_File::getFileExtension($this->getFilename());
            if($typeMapping[$fileExt]) {
                $format = $typeMapping[$fileExt];
            } else {
                // use PNG if source doesn't have a valid mapping
                $format = "png";
            }
        }
        
        
        $filename = "thumb_" . $this->getId() . "__" . $thumbnail->getName() . "." . $format;

        $fsPath = PIMCORE_TEMPORARY_DIRECTORY . "/" . $filename;
        $path = str_replace(PIMCORE_DOCUMENT_ROOT, "", $fsPath);

        $this->thumbnails[$thumbnail->getName()] = $path;

        if (is_file($fsPath) and filemtime($fsPath) > $this->getModificationDate()) {
            return $path;
        }

        // check dimensions
        $width = $this->getWidth();
        $height = $this->getHeight();
        
  
        // transform image 
        $image = self::getImageTransformInstance();

        $status = $image->load($this->getFileSystemPath());
        if($status !== true) {
            return "/pimcore/static/img/image-not-supported.png";
        }

        // create image with original dimensions, as a fallback
        $imageFallback = self::getImageTransformInstance();
        $imageFallback->load($this->getFileSystemPath());
        $imageFallback->resize($width, $height);
        $imageFallback->save($fsPath, $format, $thumbnail->getQuality());


        // now try to resize
        if ($thumbnail->getCover()) {
            // return original image if this is smaller than the thumb dimensions
            if ($width < $thumbnail->getWidth() && $height < $thumbnail->getHeight()) {
                return $path;
            }
            
            $image->fitOnCanvas($thumbnail->getWidth(), $thumbnail->getHeight());
        }
        else if ($thumbnail->getContain()) {
            // return original image if this is smaller than the thumb dimensions
            if ($width < $thumbnail->getWidth() && $height < $thumbnail->getHeight()) {
                return $path;
            }
            
            $image->fit($thumbnail->getWidth(), $thumbnail->getHeight());
        }
        else if ($thumbnail->getAspectratio()) {
            
            // return original image if this is smaller than the thumb dimensions
            if ($width < $thumbnail->getWidth() && $thumbnail->getWidth() > 0) {
                return $path;
            }
        
            if ($height < $thumbnail->getHeight() && $thumbnail->getHeight() > 0) {
                return $path;
            }
            
            if ($thumbnail->getHeight() > 0 && $thumbnail->getWidth() > 0) {
                $image->fit($thumbnail->getWidth(), $thumbnail->getHeight());
            }
            else if ($thumbnail->getHeight() > 0) {
                $image->scaleByY($thumbnail->getHeight());
            }
            else {
                $image->scaleByX($thumbnail->getWidth());
            }
        }
        else {
            $image->resize($thumbnail->getWidth(), $thumbnail->getHeight());
        }
        $image->save($fsPath, $format, $thumbnail->getQuality());

        return $path;
    }

    public static function getImageTransformInstance () {
        // try to use ImageMagick
        $image = Image_Transform::factory('Imagick3');
        if($image instanceof PEAR_Error){
            // use (php) build-in GD
            $image = Image_Transform::factory('GD');
        }

        if(!$image instanceof Image_Transform){
            if($image instanceof PEAR_Error){
                Logger::error($image->getMessage());
                throw new Exception($image->getMessage());
            } else throw new Exception("failed to get create instance of Image_Transform. Could not transform image.");
        }

        return $image;
    }

    public function getFormat() {
        if ($this->getWith() > $this->getHeight()) {
            return "landscape";
        }
        else if ($this->getWith() == $this->getHeight()) {
            return "square";
        }
        else if ($this->getHeight() > $this->getWidth()) {
            return "portrait";
        }
        return "unknown";
    }
    
    public function getRelativeFileSystemPath() {
        return str_replace(PIMCORE_DOCUMENT_ROOT, "", $this->getFileSystemPath());
    }
    
    public function getDimensions() {

        $image = self::getImageTransformInstance();

        $status = $image->load($this->getFileSystemPath());
        if($status !== true) {
            return;
        }

        $dimensions = array(
            "width" => $image->getImageWidth(),
            "height" => $image->getImageHeight()
        );

        return $dimensions;
    }

    public function getWidth() {
        $dimensions = $this->getDimensions();
        return $dimensions["width"];
    }

    public function getHeight() {
        $dimensions = $this->getDimensions();
        return $dimensions["height"];
    }
}
