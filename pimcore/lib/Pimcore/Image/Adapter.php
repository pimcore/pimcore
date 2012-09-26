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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
abstract class Pimcore_Image_Adapter {

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var array
     */
    protected $tmpFiles = array();


    /**
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }


    /**
     * @return void
     */
    protected function removeTmpFiles () {
        // remove tmp files
        if(!empty($this->tmpFiles)) {
            foreach ($this->tmpFiles as $tmpFile) {
                if(file_exists($tmpFile)) {
                    unlink($tmpFile);
                }
            }
        }
    }


    /**
     * @param  $colorhex
     * @return array
     */
    public function colorhex2colorarray($colorhex) {
        $r = hexdec(substr($colorhex, 1, 2));
        $g = hexdec(substr($colorhex, 3, 2));
        $b = hexdec(substr($colorhex, 5, 2));
        return array($r, $g, $b, 'type' => 'RGB');
    }


    /**
     * @param  $width
     * @param  $height
     * @return Pimcore_Image_Adapter
     */
    public function resize ($width, $height) {

        return $this;
    }

    /**
     * @param  $width
     * @return Pimcore_Image_Adapter
     */
    public function scaleByWidth ($width) {

        $height = round(($width / $this->getWidth()) * $this->getHeight(), 0);
        $this->resize(max(1, $width), max(1, $height));

        return $this;
    }

    /**
     * @param  $height
     * @return Pimcore_Image_Adapter
     */
    public function scaleByHeight ($height) {

        $width = round(($height / $this->getHeight()) * $this->getWidth(), 0);
        $this->resize(max(1, $width), max(1, $height));

        return $this;
    }

    /**
     * @param  $width
     * @param  $height
     * @return Pimcore_Image_Adapter
     */
    public function contain ($width, $height) {

        $x = $this->getWidth() / $width;
        $y = $this->getHeight() / $height;
        if ($x <= 1 && $y <= 1 && !$this->isVectorGraphic()) {
            return $this;
        } else if ($x > $y) {
            $this->scaleByWidth($width);
        } else {
            $this->scaleByHeight($height);
        }

        return $this;
    }

    /**
     * @param  $width
     * @param  $height
     * @param string $orientation
     * @return Pimcore_Image_Adapter
     */
    public function cover ($width, $height, $orientation = "center") {

        $ratio = $this->getWidth() / $this->getHeight();

        if (($width / $height) > $ratio) {
           $this->scaleByWidth($width);
        } else {
           $this->scaleByHeight($height);
        }

        if($orientation == "center") {
            $cropX = ($this->getWidth() - $width)/2;
            $cropY = ($this->getHeight() - $height)/2;
        } else if ($orientation == "topleft") {
            $cropX = 0;
            $cropY = 0;
        } else if ($orientation == "topright") {
            $cropX = $this->getWidth() - $width;
            $cropY = 0;
        } else if ($orientation == "bottomleft") {
            $cropX = 0;
            $cropY = $this->getHeight() - $height;
        } else if ($orientation == "bottomright") {
            $cropX = $this->getWidth() - $width;
            $cropY = $this->getHeight() - $height;
        } else if ($orientation == "centerleft") {
            $cropX = 0;
            $cropY = ($this->getHeight() - $height)/2;
        } else if ($orientation == "centerright") {
            $cropX = $this->getWidth() - $width;
            $cropY = ($this->getHeight() - $height)/2;
        } else if ($orientation == "topcenter") {
            $cropX = ($this->getWidth() - $width)/2;
            $cropY = 0;
        } else if ($orientation == "bottomcenter") {
            $cropX = ($this->getWidth() - $width)/2;
            $cropY = $this->getHeight() - $height;
        } else {
            $cropX = null;
            $cropY = null;
        }

        if($cropX !== null && $cropY !== null) {
            $this->crop($cropX, $cropY, $width, $height);
        } else {
            Logger::error("Cropping not processed, because X or Y is not defined or null, proceeding with next step");
        }

        return $this;
    }

    /**
     * @param  $width
     * @param  $height
     * @param string $color
     * @param string $orientation
     * @return Pimcore_Image_Adapter
     */
    public function frame ($width, $height) {
        
        return $this;
    }

    /**
     * @param  $angle
     * @param bool $autoResize
     * @param string $color
     * @return Pimcore_Image_Adapter
     */
    public function rotate ($angle) {

        return $this;
    }

    /**
     * @param  $x
     * @param  $y
     * @param  $width
     * @param  $height
     * @return Pimcore_Image_Adapter
     */
    public function crop ($x, $y, $width, $height) {

        return $this;
    }


    /**
     * @param  $color
     * @return Pimcore_Image_Adapter
     */
    public function setBackgroundColor ($color) {

        return $this;
    }

    /**
     * @param  $image
     * @return Pimcore_Image_Adapter
     */
    public function setBackgroundImage ($image) {
        
        return $this;
    }


    /**
     * @param  $x
     * @param  $y
     * @return Pimcore_Image_Adapter
     */
    public function roundCorners ($x, $y) {

        return $this;
    }

    /**
     * @param string $image
     * @param int $x
     * @param int $y
     * @param int $alpha
     * @return Pimcore_Image_Adapter
     */
    public function  addOverlay ($image, $x = 0, $y = 0, $alpha = 100, $composite = "COMPOSITE_DEFAULT") {

        return $this;
    }

    /**
     * @param  $image
     * @return Pimcore_Image_Adapter
     */
    public function applyMask ($image) {

        return $this;
    }

    /**
     * @param $width
     * @param $height
     * @param $x
     * @param $y
     * @return Pimcore_Image_Adapter
     */
    public function  cropPercent ($width, $height, $x, $y) {

        $originalWidth = $this->getWidth();
        $originalHeight = $this->getHeight();

        $widthPixel = $originalWidth * ($width / 100);
        $heightPixel = $originalHeight * ($height / 100);
        $xPixel = $originalWidth * ($x / 100);
        $yPixel = $originalHeight * ($y / 100);

        return $this->crop($xPixel, $yPixel, $widthPixel, $heightPixel);
    }

    /**
     * @return Pimcore_Image_Adapter
     */
    public function grayscale () {

        return $this;
    }

    /**
     * @return Pimcore_Image_Adapter
     */
    public function sepia () {

        return $this;
    }


    /**
     * @abstract
     * @param  $imagePath
     * @return Pimcore_Image_Adapter
     */
    public abstract function load ($imagePath);


    /**
     * @abstract
     * @param  $path
     * @return Pimcore_Image_Adapter
     */
    public abstract function save ($path, $format = null, $quality = null);


    /**
     * @abstract
     * @return void
     */
    protected abstract function destroy ();

    /**
     * @return void
     */
    protected function reinitializeImage() {

        $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . uniqid() . "_pimcore_image_tmp_file";
        $this->tmpFiles[] = $tmpFile;

        $this->save($tmpFile);
        $this->destroy();
        $this->load($tmpFile);
    }

    /**
     * 
     */
    public function __destruct() {
        $this->removeTmpFiles();
    }


    /**
     * @return bool
     */
    public function isVectorGraphic () {
        return false;
    }


    /**
     * @param $type
     */
    public function setColorspace($type = "RGB") {
        return $this;
    }
}
