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

        if($this->resource) {
            unset($this->resource);
            $this->resource = null;
        }

        try {
            $this->resource = new Imagick();
            if(!$this->resource->readImage($imagePath)) {
                return false;
            }
        } catch (Exception $e) {
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

        $this->resource->stripimage();
        $this->resource->setImageFormat($format);

        if($quality) {
            $this->resource->setCompressionQuality((int) $quality);
            $this->resource->setImageCompressionQuality((int) $quality);
        }
        
        $this->resource->writeImage($path);

        return $this;
    }

    /**
     * @return void
     */
    protected function destroy() {
        $this->resource->destroy();
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

        $this->reinitializeImage();

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
        $this->resource->setImagePage($width, $height, 0, 0);

        $this->setWidth($width);
        $this->setHeight($height);

        $this->reinitializeImage();

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

        $this->reinitializeImage();

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

        $this->reinitializeImage();

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

        $this->reinitializeImage();

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

        $this->reinitializeImage();

        return $this;
    }


    /**
     * @param  $x
     * @param  $y
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function roundCorners ($x, $y) {

        $this->resource->roundCorners($x, $y);

        $this->reinitializeImage();

        return $this;
    }


    /**
     * @param  $color
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function setBackgroundImage ($image) {

        $image = ltrim($image,"/");
        $image = PIMCORE_DOCUMENT_ROOT . "/" . $image;

        if(is_file($image)) {
            $newImage = new Imagick();
            $newImage->readimage($image);
            $newImage->resizeimage($this->getWidth(), $this->getHeight(), Imagick::FILTER_UNDEFINED, 1, false);
            $newImage->compositeImage($this->resource, Imagick::COMPOSITE_DEFAULT, 0 ,0);
            $this->resource = $newImage;
        }

        $this->reinitializeImage();

        return $this;
    }

    /**
     * @param string $image
     * @param int $x
     * @param int $y
     * @param int $alpha
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function  addOverlay ($image, $x = 0, $y = 0, $alpha = 100) {
                
        $image = ltrim($image,"/");
        $image = PIMCORE_DOCUMENT_ROOT . "/" . $image;
        $alpha = round($alpha / 100, 1);

        if(is_file($image)) {
            $newImage = new Imagick();
            $newImage->readimage($image);
            $newImage->setimageopacity($alpha);
            $this->resource->compositeImage($newImage, Imagick::COMPOSITE_DEFAULT, $x ,$y);
        }

        $this->reinitializeImage();

        return $this;
    }


    /**
     * @param  $image
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function applyMask ($image) {

        $image = ltrim($image,"/");
        $image = PIMCORE_DOCUMENT_ROOT . "/" . $image;

        if(is_file($image)) {
            $this->resource->setImageMatte(1);
            $newImage = new Imagick();
            $newImage->readimage($image);
            $newImage->resizeimage($this->getWidth(), $this->getHeight(), Imagick::FILTER_UNDEFINED, 1, false);
            $this->resource->compositeImage($newImage, Imagick::COMPOSITE_DSTIN, 0 ,0);
        }

        $this->reinitializeImage();

        return $this;
    }


    /**
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function grayscale () {

        $this->resource->setImageType(imagick::IMGTYPE_GRAYSCALEMATTE);
        $this->reinitializeImage();

        return $this;
    }

    /**
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function sepia () {

        $this->resource->sepiatoneimage(85);
        $this->reinitializeImage();

        return $this;
    }
}
