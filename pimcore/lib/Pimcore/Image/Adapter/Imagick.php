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
 
class Pimcore_Image_Adapter_Imagick extends Pimcore_Image_Adapter {


    /**
     * @var Imagick
     */
    protected $resource;

    public function load ($imagePath) {

        $this->resource = new Imagick();
        if(!$this->resource->readImage($imagePath)) {
            return false;
        }

        // set dimensions
        $this->setWidth($this->resource->getImageWidth());
        $this->setHeight($this->resource->getImageHeight());

        return $this;
    }

    /**
     * @param  $path
     * @return void
     */
    public function save ($path, $format, $quality = null) {

        $this->resource->setImageFormat($format);

        if($quality) {
            $this->resource->setCompressionQuality((int) $quality);
            $this->resource->setImageCompressionQuality((int) $quality);
        }
        
        $this->resource->writeImage($path);

        return $this;
    }


    /**
     * @param  $width
     * @param  $height
     * @return Pimcore_Image_Adapter
     */
    public function resize ($width, $height) {
        $this->resource->resizeimage($width, $height, Imagick::FILTER_UNDEFINED, 1, false);

        $this->setWidth($width);
        $this->setHeight($height);

        return $this;
    }

    /**
     * @param  $x
     * @param  $y
     * @param  $width
     * @param  $height
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function crop($x, $y, $width, $height) {
        $this->resource->cropImage($width, $height, $x, $y);

        $this->setWidth($width);
        $this->setHeight($height);

        return $this;
    }


    /**
     * @param  $width
     * @param  $height
     * @param string $color
     * @param string $orientation
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function frame ($width, $height) {

        $this->contain($width, $height);

        $x = ($width - $this->getWidth()) / 2;
        $y = ($height - $this->getHeight()) / 2;


        $newImage = $this->createImage($width, $height);
        $newImage->compositeImage($this->resource, Imagick::COMPOSITE_DEFAULT , $x, $y);
        $this->resource = $newImage;

        $this->setWidth($width);
        $this->setHeight($height);

        return $this;
    }

    /**
     * @param  $color
     * @return Pimcore_Image_Adapter
     */
    public function setBackgroundColor ($color) {

        $newImage = $this->createImage($this->getWidth(), $this->getHeight(), $color);
        $newImage->compositeImage($this->resource, Imagick::COMPOSITE_DEFAULT , 0, 0);
        $this->resource = $newImage;

        return $this;
    }

    /**
     * @param $width
     * @param $height
     * @return Imagick
     */
    protected  function createImage ($width, $height, $color = "transparent") {
        $newImage = new Imagick();
        $newImage->newimage($width, $height, $color);
        return $newImage;
    }


    /**
     * @param  $angle
     * @param bool $autoResize
     * @param string $color
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function rotate ($angle) {

        $this->resource->rotateImage(new ImagickPixel('none'), $angle);
        $this->setWidth($this->resource->getimagewidth());
        $this->setHeight($this->resource->getimageheight());

        return $this;
    }


    /**
     * @param  $x
     * @param  $y
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function roundCorners ($x, $y) {

        $this->resource->roundCorners($x, $y);

        return $this;
    }
}
