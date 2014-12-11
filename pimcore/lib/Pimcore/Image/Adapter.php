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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Image;

abstract class Adapter {

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var bool
     */
    protected $reinitializing = false;

    /**
     * @var array
     */
    protected $tmpFiles = array();

    /**
     * @var bool
     */
    protected $useContentOptimizedFormat = false;

    /**
     * @var bool
     */
    protected $modified = false;

    /**
     * @var bool
     */
    protected $isAlphaPossible = false;

    /**
     * @param $height
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param $width
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
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
     * @return self
     */
    public function resize ($width, $height) {

        return $this;
    }

    /**
     * @param  $width
     * @return self
     */
    public function scaleByWidth ($width, $forceResize = false) {

        if($forceResize || $width <= $this->getWidth() || $this->isVectorGraphic()) {
            $height = round(($width / $this->getWidth()) * $this->getHeight(), 0);
            $this->resize(max(1, $width), max(1, $height));
        }

        return $this;
    }

    /**
     * @param  $height
     * @return self
     */
    public function scaleByHeight ($height, $forceResize = false) {

        if($forceResize || $height < $this->getHeight() || $this->isVectorGraphic()) {
            $width = round(($height / $this->getHeight()) * $this->getWidth(), 0);
            $this->resize(max(1, $width), max(1, $height));
        }

        return $this;
    }

    /**
     * @param  $width
     * @param  $height
     * @return self
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
     * @return self
     */
    public function cover ($width, $height, $orientation = "center", $doNotScaleUp = true) {

        $scaleUp = $doNotScaleUp ? false : true;

        $ratio = $this->getWidth() / $this->getHeight();

        if (($width / $height) > $ratio) {
           $this->scaleByWidth($width, $scaleUp);
        } else {
           $this->scaleByHeight($height, $scaleUp);
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
            \Logger::error("Cropping not processed, because X or Y is not defined or null, proceeding with next step");
        }

        return $this;
    }

    /**
     * @param $width
     * @param $height
     * @return $this
     */
    public function frame ($width, $height) {
        
        return $this;
    }

    /**
     * @param  int $tolerance
     * @return self
     */
    public function trim ($tolerance) {

        return $this;
    }

    /**
     * @param $angle
     * @return $this
     */
    public function rotate ($angle) {

        return $this;
    }

    /**
     * @param  $x
     * @param  $y
     * @param  $width
     * @param  $height
     * @return self
     */
    public function crop ($x, $y, $width, $height) {

        return $this;
    }


    /**
     * @param  $color
     * @return self
     */
    public function setBackgroundColor ($color) {
        return $this;
    }

    /**
     * @param  $image
     * @return self
     */
    public function setBackgroundImage ($image) {
        
        return $this;
    }


    /**
     * @param  $x
     * @param  $y
     * @return self
     */
    public function roundCorners ($x, $y) {

        return $this;
    }

    /**
     * @param string $image
     * @param int $x
     * @param int $y
     * @param int $alpha
     * @param string $origin Origin of the X and Y coordinates (top-left, top-right, bottom-left, bottom-right or center)
     * @return self
     */
    public function  addOverlay ($image, $x = 0, $y = 0, $alpha = 100, $composite = "COMPOSITE_DEFAULT", $origin = 'top-left') {

        return $this;
    }

    /**
     * @param  $image
     * @return self
     */
    public function applyMask ($image) {

        return $this;
    }

    /**
     * @param $width
     * @param $height
     * @param $x
     * @param $y
     * @return self
     */
    public function cropPercent ($width, $height, $x, $y) {

        if($this->isVectorGraphic()) {
            // rasterize before cropping
            $dimensions = $this->getVectorRasterDimensions();
            $this->resize($dimensions["width"], $dimensions["height"]);
        }

        $originalWidth = $this->getWidth();
        $originalHeight = $this->getHeight();

        $widthPixel = ceil($originalWidth * ($width / 100));
        $heightPixel = ceil($originalHeight * ($height / 100));
        $xPixel = ceil($originalWidth * ($x / 100));
        $yPixel = ceil($originalHeight * ($y / 100));

        return $this->crop($xPixel, $yPixel, $widthPixel, $heightPixel);
    }

    /**
     * @return self
     */
    public function grayscale () {

        return $this;
    }

    /**
     * @return self
     */
    public function sepia () {

        return $this;
    }

    /**
     * @return self
     */
    public function sharpen () {

        return $this;
    }

    /**
     * @return self
     */
    public function mirror ($mode) {

        return $this;
    }

    /**
     * @abstract
     * @param  $imagePath
     * @return self
     */
    public abstract function load ($imagePath, $options = []);


    /**
     * @param $path
     * @param null $format
     * @param null $quality
     * @return mixed
     */
    public abstract function save ($path, $format = null, $quality = null);


    /**
     * @abstract
     * @return void
     */
    protected abstract function destroy ();

    /**
     *
     */
    public function preModify() {
        if($this->getModified()) {
            $this->reinitializeImage();
        }
    }

    /**
     *
     */
    public function postModify() {
        $this->setModified(true);
    }

    /**
     * @return void
     */
    protected function reinitializeImage() {

        $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . uniqid() . "_pimcore_image_tmp_file.png";
        $this->tmpFiles[] = $tmpFile;

        $this->reinitializing = true;
        $this->save($tmpFile); // do not optimize image
        $this->destroy();
        $this->load($tmpFile);
        $this->reinitializing = false;

        $this->modified = false;
    }

    /**
     * 
     */
    public function __destruct() {
        $this->destroy();
        $this->removeTmpFiles();
    }


    /**
     * @return bool
     */
    public function isVectorGraphic () {
        return false;
    }

    /**
     * @return array
     */
    public function getVectorRasterDimensions() {

        $targetWidth = 5000;
        $factor = $targetWidth / $this->getWidth();

        return [
            "width" => $this->getWidth() * $factor,
            "height" => $this->getHeight() * $factor
        ];
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setColorspace($type = "RGB") {
        return $this;
    }

    /**
     * @param boolean $useContentOptimizedFormat
     */
    public function setUseContentOptimizedFormat($useContentOptimizedFormat)
    {
        $this->useContentOptimizedFormat = $useContentOptimizedFormat;
    }

    /**
     * @return boolean
     */
    public function getUseContentOptimizedFormat()
    {
        return $this->useContentOptimizedFormat;
    }

    /**
     * @param boolean $modified
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    }

    /**
     * @return boolean
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @param bool $value
     */
    public function setIsAlphaPossible($value) {
        $this->isAlphaPossible = $value;
    }
}
