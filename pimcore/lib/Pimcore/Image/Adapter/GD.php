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
 
class Pimcore_Image_Adapter_GD extends Pimcore_Image_Adapter {


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
     * @return bool|Pimcore_Image_Adapter_GD
     */
    public function load ($imagePath) {

        $this->path = $imagePath;
        if(!$this->resource = @imagecreatefromstring(file_get_contents($this->path))) {
            return false;
        }

        // set dimensions
        list($width, $height) = getimagesize($this->path);
        $this->setWidth($width);
        $this->setHeight($height);

        return $this;
    }

    /**
     * @param  $path
     * @return void
     */
    public function save ($path, $format = null, $quality = null) {

        $format = strtolower($format);
        if(!$format) {
            $format = "png";
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
        $trans_colour = imagecolorallocatealpha($newImg, 255, 0, 0, 127);
        imagefill($newImg, 0, 0, $trans_colour);

        return $newImg;
    }

    /**
     * @param  $width
     * @param  $height
     * @return Pimcore_Image_Adapter
     */
    public function resize ($width, $height) {

        $newImg = $this->createImage($width, $height);
        ImageCopyResampled($newImg, $this->resource, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->resource = $newImg;

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
     * @return Pimcore_Image_Adapter_GD
     */
    public function crop($x, $y, $width, $height) {

        $x = min($this->getWidth(), max(0, $x));
        $y = min($this->getHeight(), max(0, $y));
        $width   = min($width,  $this->getWidth() - $x);
        $height  = min($height, $this->getHeight() - $y);
        $new_img = $this->createImage($width, $height);

        imagecopy($new_img, $this->resource, 0, 0, $x, $y, $width, $height);

        $this->resource = $new_img;

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
     * @return Pimcore_Image_Adapter_GD
     */
    public function frame ($width, $height) {

        $this->contain($width, $height);

        $x = ($width - $this->getWidth()) / 2;
        $y = ($height - $this->getHeight()) / 2;

        $newImage = $this->createImage($width, $height);
        imagecopy($newImage, $this->resource,$x, $y, 0, 0, $this->getWidth(), $this->getHeight());
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

        list($r,$g,$b) = $this->colorhex2colorarray($color);

        // just imagefill() on the existing image doesn't work, so we have to create a new image, fill it and then merge
        // the source image with the background-image together
        $newImg = imagecreatetruecolor($this->getWidth(), $this->getHeight());
        $color = imagecolorallocate($newImg, $r, $g, $b);
        imagefill($newImg, 0, 0, $color);

        imagecopy($newImg, $this->resource,0, 0, 0, 0, $this->getWidth(), $this->getHeight());
        $this->resource = $newImg;

        $this->reinitializeImage();

        return $this;
    }

    /**
     * @return Pimcore_Image_Adapter_GD
     */
    public function grayscale () {
        imagefilter($this->resource, IMG_FILTER_GRAYSCALE);

        $this->reinitializeImage();

        return $this;
    }

    /**
     * @return Pimcore_Image_Adapter_GD
     */
    public function sepia () {

        imagefilter($this->resource, IMG_FILTER_GRAYSCALE);
        imagefilter($this->resource, IMG_FILTER_COLORIZE, 100, 50, 0);

        $this->reinitializeImage();

        return $this;
    }
}
