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

namespace Pimcore\Image\Adapter;

use Pimcore\Image\Adapter;

class GD extends Adapter {
    /**
     * @var string
     */
    protected $path;

    /**
     * contains imageresource
     * @var mixed
     */
    protected $resource;

    /**
     * @param $imagePath
     * @param array $options
     * @return $this|self
     */
    public function load ($imagePath, $options = []) {

        $this->path = $imagePath;
        if(!$this->resource = @imagecreatefromstring(file_get_contents($this->path))) {
            return false;
        }

        // set dimensions
        list($width, $height) = getimagesize($this->path);
        $this->setWidth($width);
        $this->setHeight($height);

        if(in_array(\Pimcore\File::getFileExtension($imagePath), ["png","gif"])) {
            // in GD only gif and PNG can have an alphachannel
            $this->setIsAlphaPossible(true);
        }

        $this->setModified(false);

        return $this;
    }

    /**
     * @param $path
     * @param null $format
     * @param null $quality
     * @return $this|mixed
     */
    public function save ($path, $format = null, $quality = null) {

        $format = strtolower($format);
        if(!$format) {
            $format = "png";
        }

        if(!$this->reinitializing && $this->getUseContentOptimizedFormat()) {
            $format = "pjpeg";
            if($this->hasAlphaChannel()) {
                $format = "png";
            }
        }

        // progressive jpeg
        if($format == "pjpeg") {
            imageinterlace($this->resource, true);
            $format = "jpeg";
        }

        if($format == "jpg") {
            $format = "jpeg";
        }

        $functionName = 'image' . $format;
        if(!function_exists($functionName)) {
            $functionName = "imagepng";
        }

        // always create a PNG24
        if($format == "png") {
            imagesavealpha($this->resource, true);
        }

        switch ($format) {
            case 'jpeg':
                $functionName($this->resource, $path, $quality);
                break;
            default:
                $functionName($this->resource, $path);
        }

        return $this;
    }

    /**
     * @return bool
     */
    protected function hasAlphaChannel() {

        if($this->isAlphaPossible) {
            $width = imagesx($this->resource); // Get the width of the image
            $height = imagesy($this->resource); // Get the height of the image

            // We run the image pixel by pixel and as soon as we find a transparent pixel we stop and return true.
            for($i = 0; $i < $width; $i++) {
                for($j = 0; $j < $height; $j++) {
                    $rgba = imagecolorat($this->resource, $i, $j);
                    if(($rgba & 0x7F000000) >> 24) {
                        return true;
                    }
                }
            }
        }

        // If we dont find any pixel the function will return false.
        return false;
    }

    /**
     * @return void
     */
    protected function destroy() {
        imagedestroy($this->resource);
    }

    /**
     * @param $width
     * @param $height
     * @return resource
     */
    protected function createImage ($width, $height) {
        $newImg = imagecreatetruecolor($width, $height);

        imagesavealpha($newImg, true);
        imagealphablending($newImg, false);
        $trans_colour = imagecolorallocatealpha($newImg, 0, 0, 0, 127);
        imagefill($newImg, 0, 0, $trans_colour);

        return $newImg;
    }

    /**
     * @param  $width
     * @param  $height
     * @return self
     */
    public function resize ($width, $height) {

        $this->preModify();

        $newImg = $this->createImage($width, $height);
        ImageCopyResampled($newImg, $this->resource, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->resource = $newImg;

        $this->setWidth($width);
        $this->setHeight($height);

        $this->postModify();

        return $this;
    }

    /**
     * @param  $x
     * @param  $y
     * @param  $width
     * @param  $height
     * @return self
     */
    public function crop($x, $y, $width, $height) {

        $this->preModify();

        $x = min($this->getWidth(), max(0, $x));
        $y = min($this->getHeight(), max(0, $y));
        $width   = min($width,  $this->getWidth() - $x);
        $height  = min($height, $this->getHeight() - $y);
        $new_img = $this->createImage($width, $height);

        imagecopy($new_img, $this->resource, 0, 0, $x, $y, $width, $height);

        $this->resource = $new_img;

        $this->setWidth($width);
        $this->setHeight($height);

        $this->postModify();

        return $this;
    }


    /**
     * @param  $width
     * @param  $height
     * @return self
     */
    public function frame ($width, $height) {

        $this->preModify();

        $this->contain($width, $height);

        $x = ($width - $this->getWidth()) / 2;
        $y = ($height - $this->getHeight()) / 2;

        $newImage = $this->createImage($width, $height);
        imagecopy($newImage, $this->resource,$x, $y, 0, 0, $this->getWidth(), $this->getHeight());
        $this->resource = $newImage;

        $this->setWidth($width);
        $this->setHeight($height);

        $this->postModify();

        $this->setIsAlphaPossible(true);

        return $this;
    }

    /**
     * @param  $color
     * @return Pimcore_Image_Adapter
     */
    public function setBackgroundColor ($color) {

        $this->preModify();

        list($r,$g,$b) = $this->colorhex2colorarray($color);

        // just imagefill() on the existing image doesn't work, so we have to create a new image, fill it and then merge
        // the source image with the background-image together
        $newImg = imagecreatetruecolor($this->getWidth(), $this->getHeight());
        $color = imagecolorallocate($newImg, $r, $g, $b);
        imagefill($newImg, 0, 0, $color);

        imagecopy($newImg, $this->resource,0, 0, 0, 0, $this->getWidth(), $this->getHeight());
        $this->resource = $newImg;

        $this->postModify();

        $this->setIsAlphaPossible(false);

        return $this;
    }

    /**
     * @return self
     */
    public function grayscale () {

        $this->preModify();

        imagefilter($this->resource, IMG_FILTER_GRAYSCALE);

        $this->postModify();

        return $this;
    }

    /**
     * @return self
     */
    public function sepia () {

        $this->preModify();

        imagefilter($this->resource, IMG_FILTER_GRAYSCALE);
        imagefilter($this->resource, IMG_FILTER_COLORIZE, 100, 50, 0);

        $this->postModify();

        return $this;
    }

    public function  addOverlay ($image, $x = 0, $y = 0, $alpha = 100, $composite = "COMPOSITE_DEFAULT", $origin = 'top-left') {

        $this->preModify();

        $image = ltrim($image,"/");
        $image = PIMCORE_DOCUMENT_ROOT . "/" . $image;

        // 100 alpha is default
        if(empty($alpha)) {
            $alpha = 100;
        }
        $alpha = round($alpha / 100, 1);


        if(is_file($image)) {

            list($oWidth, $oHeight) = getimagesize($image);

            if($origin == 'top-right') {
                $x = $this->getWidth() - $oWidth - $x;
            } elseif($origin == 'bottom-left') {
                $y = $this->getHeight() - $oHeight - $y;
            } elseif($origin == 'bottom-right') {
                $x = $this->getWidth() - $oWidth - $x;
                $y = $this->getHeight() - $oHeight - $y;
            } elseif($origin == 'center') {
                $x = round($this->getWidth() / 2) - round($oWidth / 2) + $x;
                $y = round($this->getHeight() / 2) -round($oHeight / 2) + $y;
            }

            $overlay = imagecreatefromstring(file_get_contents($image));
            imagealphablending($this->resource, true);
            imagecopyresampled($this->resource, $overlay, $x, $y, 0, 0, $oWidth, $oHeight, $oWidth, $oHeight);
        }

        $this->postModify();

        return $this;
    }

    /**
     * @param string $mode
     * @return $this|self
     */
    public function mirror($mode) {

        $this->preModify();

        if($mode == "vertical") {
            imageflip($this->resource, IMG_FLIP_VERTICAL);
        } else if ($mode == "horizontal") {
            imageflip($this->resource, IMG_FLIP_HORIZONTAL);
        }

        $this->postModify();

        return $this;
    }

    /**
     * @param $angle
     * @return $this|self
     */
    public function rotate ($angle) {

        $this->preModify();
        $angle = 360 - $angle;
        $this->resource = imagerotate($this->resource, $angle, imageColorAllocateAlpha($this->resource, 0, 0, 0, 127));

        $this->setWidth(imagesx($this->resource));
        $this->setHeight(imagesy($this->resource));

        $this->postModify();

        $this->setIsAlphaPossible(true);

        return $this;
    }
}
