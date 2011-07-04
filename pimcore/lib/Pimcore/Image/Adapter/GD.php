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
    public function save ($path, $format, $quality = null) {

        if($format == "jpg") {
            $format = "jpeg";
        }

        $functionName = 'image' . $format;
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
     * @param $width
     * @param $height
     * @return resource
     */
    protected function createImage ($width, $height) {
        $newImg = imagecreatetruecolor($width, $height);
        //$black = imagecolorallocate($newImg, 0, 0, 0);
        //imagecolortransparent($newImg, $black);

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

        return $this;
    }

    /**
     * @param  $color
     * @return Pimcore_Image_Adapter
     */
    public function setBackgroundColor ($color) {

        list($r,$g,$b) = $this->colorhex2colorarray($color);
        $color = imagecolorallocate($this->resource, $r, $g, $b);
        imagefill($this->resource, 0, 0, $color);

        return $this;
    }


    /**
     * @param  $angle
     * @param bool $autoResize
     * @param string $color
     * @return Pimcore_Image_Adapter_GD
     */
    public function rotate ($angle) {


        $color = imagecolorallocatealpha($this->resource, 0, 0, 0, 127);
        $this->resource = imagerotate($this->resource, $angle, $color, 1);

        $tmpFile = PIMCORE_TEMPORARY_DIRECTORY . "/" . uniqid() . ".image-tmp";
        imagepng($this->resource,$tmpFile);

        list($width, $height) = getimagesize($tmpFile);
        $this->setWidth($width);
        $this->setHeight($height);

        unlink($tmpFile);

        return $this;
    }


    /**
     * @param  $x
     * @param  $y
     * @return Pimcore_Image_Adapter_GD
     */
    public function roundCorners ($x, $y) {

        $corner_image = imagecreatetruecolor( $x, $y );
        $clear_colour = imagecolorallocate($corner_image, 0, 0, 0);
        $solid_colour = imagecolorallocatealpha($corner_image, 0, 0, 0, 127);

        // top left
        imagecolortransparent($corner_image, $clear_colour);
        imagefill($corner_image, 0, 0, $solid_colour);
        imagefilledellipse( $corner_image, $x, $y, $x * 2, $y * 2,  $clear_colour );
        imagecopymerge($this->resource, $corner_image,  0, 0,  0,  0, $x,  $y, 100);

        // bottom right
        $corner_image = imagerotate($corner_image, 180, 0);
        imagecopymerge($this->resource, $corner_image, $this->getWidth() - $x,  $this->getHeight() - $y, 0, 0, $x, $y, 100);




        $corner_image = imagecreatetruecolor( $x, $y );
        $clear_colour = imagecolorallocate($corner_image, 0, 0, 0);
        $solid_colour = imagecolorallocatealpha($corner_image, 0, 0, 0, 127);

        // bottom left
        imagecolortransparent($corner_image, $clear_colour);
        imagefill($corner_image, 0, 0, $solid_colour);
        imagefilledellipse( $corner_image, $x, 0, $x * 2, $y * 2,  $clear_colour );
        //$corner_image = imagerotate($corner_image, 90, 0);
        imagecopymerge($this->resource, $corner_image, 0,  $this->getHeight() - $y, 0, 0, $x, $y, 100);



        $corner_image = imagerotate($corner_image, 180, 0);
        imagecopymerge( $this->resource, $corner_image, $this->getWidth() - $x, 0,  0, 0, $x, $y, 100 );

        
        return $this;
    }

}
