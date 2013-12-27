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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
class Pimcore_Image_Adapter_Imagick extends Pimcore_Image_Adapter {


    /**
     * @var string
     */
    protected static $RGBColorProfile;

    /**
     * @var string
     */
    protected static $CMYKColorProfile;

    /**
     * @var Imagick
     */
    protected $resource;

    /**
     * @var string
     */
    protected $imagePath;

    /**
     * @param $imagePath
     * @return bool|Pimcore_Image_Adapter|Pimcore_Image_Adapter_Imagick
     */
    public function load ($imagePath) {

        if($this->resource) {
            unset($this->resource);
            $this->resource = null;
        }

        try {
            $this->resource = new Imagick();

            // transparency for EPS files
            if(method_exists($this->resource, "setcolorspace")) {
                $this->resource->setBackgroundColor(new ImagickPixel('transparent')); //set .png transparent (print)
                $this->resource->setcolorspace(Imagick::COLORSPACE_SRGB);
            }

            if(!$this->resource->readImage($imagePath."[0]") || !filesize($imagePath)) {
                return false;
            }

            $this->imagePath = $imagePath;

        } catch (Exception $e) {
            Logger::error($e);
            return false;
        }

        // set dimensions
        $this->setWidth($this->resource->getImageWidth());
        $this->setHeight($this->resource->getImageHeight());

        $this->setModified(false);

        return $this;
    }

    /**
     * @param $path
     * @param null $format
     * @param null $quality
     * @param null $colorProfile
     * @return $this|mixed
     */
    public function save ($path, $format = null, $quality = null, $colorProfile = null) {

        if(!$format) {
            $format = "png";
        }

        if(!$colorProfile) {
            $colorProfile = Imagick::COLORSPACE_RGB;
        } else {
            $colorProfile = constant("Imagick::COLORSPACE_" . $colorProfile);
        }

        $originalFilename = null;
        if(!$this->reinitializing) {
            if($this->getUseContentOptimizedFormat() && $colorProfile == Imagick::COLORSPACE_RGB) {
                $format = "jpeg";
                if($this->resource->getImageAlphaChannel()) {
                    $format = "png";
                }
            }
        }

        $this->setColorspace($colorProfile);

        $this->resource->stripimage();
        $this->resource->profileImage('*', null);
        $this->resource->setImageFormat($format);

        if($quality) {
            $this->resource->setCompressionQuality((int) $quality);
            $this->resource->setImageCompressionQuality((int) $quality);
        }

        if($format == "tiff") {
            $this->resource->setCompression(Imagick::COMPRESSION_LZW);
        }

        $this->resource->writeImage($format . ":" . $path);

        return $this;
    }


    /**
     * @return  void
     */
    protected function destroy() {
        if($this->resource) {
            $this->resource->clear();
            $this->resource->destroy();
            $this->resource = null;
        }
    }

    /**
     * @param string $type
     * @return Pimcore_Image_Adapter|void
     */
    public function setColorspace($type = "RGB") {

        $type = strtoupper($type);
        if(!in_array($type, array("RGB","CMYK"))) {
            $type = "RGB";
        }

        $imageColorspace = $this->resource->getImageColorspace();

        if ($imageColorspace == Imagick::COLORSPACE_CMYK && $type == "RGB") {
            if(self::getCMYKColorProfile() && self::getRGBColorProfile()) {
                $profiles = $this->resource->getImageProfiles('*', false);
                // we're only interested if ICC profile(s) exist
                $has_icc_profile = (array_search('icc', $profiles) !== false);
                // if it doesn't have a CMYK ICC profile, we add one
                if ($has_icc_profile === false) {
                    $this->resource->profileImage('icc', self::getCMYKColorProfile());
                }
                // then we add an RGB profile
                $this->resource->profileImage('icc', self::getRGBColorProfile());
                $this->resource->setImageColorspace(Imagick::COLORSPACE_SRGB); // we have to use SRGB here, no clue why but it works
            }
        } else if (in_array($imageColorspace, array(Imagick::COLORSPACE_RGB, Imagick::COLORSPACE_SRGB)) && $type == "CMYK") {
            if(self::getCMYKColorProfile() && self::getRGBColorProfile()) {
                $profiles = $this->resource->getImageProfiles('*', false);
                // we're only interested if ICC profile(s) exist
                $has_icc_profile = (array_search('icc', $profiles) !== false);
                // if it doesn't have a CMYK ICC profile, we add one
                if ($has_icc_profile === false) {
                    $this->resource->profileImage('icc', self::getRGBColorProfile());
                }
                // then we add an RGB profile
                $this->resource->profileImage('icc', self::getCMYKColorProfile());
                $this->resource->setImageColorspace(Imagick::COLORSPACE_CMYK);
            }
        } else if ($imageColorspace == Imagick::COLORSPACE_GRAY && $type == "RGB") {
            $this->resource->setImageColorspace(Imagick::COLORSPACE_SRGB);
        } else if (!in_array($imageColorspace, array(Imagick::COLORSPACE_RGB, Imagick::COLORSPACE_SRGB)) && $type == "RGB") {
            $this->resource->setImageColorspace(Imagick::COLORSPACE_SRGB);
        }
        // this is a HACK to force grayscale images to be real RGB - truecolor, this is important if you want to use
        // thumbnails in PDF's because they do not support "real" grayscale JPEGs or PNGs
        // problem is described here: http://imagemagick.org/Usage/basics/#type
        // and here: http://www.imagemagick.org/discourse-server/viewtopic.php?f=2&t=6888#p31891
        $draw = new ImagickDraw();
        $draw->setFillColor("#ff0000");
        $draw->setfillopacity(.01);
        $draw->point($this->getWidth()-1,$this->getHeight()-1); // place it in the right bottom corner
        $this->resource->drawImage($draw);

        return $this;
    }

    /**
     * @param string $CMYKColorProfile
     */
    public static function setCMYKColorProfile($CMYKColorProfile)
    {
        self::$CMYKColorProfile = $CMYKColorProfile;
    }

    /**
     * @return string
     */
    public static function getCMYKColorProfile()
    {
        if(!self::$CMYKColorProfile) {
            if($path = Pimcore_Config::getSystemConfig()->assets->icc_cmyk_profile) {
                if(file_exists($path)) {
                    self::$CMYKColorProfile = file_get_contents($path);
                }
            }
        }

        return self::$CMYKColorProfile;
    }

    /**
     * @param string $RGBColorProfile
     */
    public static function setRGBColorProfile($RGBColorProfile)
    {
        self::$RGBColorProfile = $RGBColorProfile;
    }

    /**
     * @return string
     */
    public static function getRGBColorProfile()
    {
        if(!self::$RGBColorProfile) {
            if($path = Pimcore_Config::getSystemConfig()->assets->icc_rgb_profile) {
                if(file_exists($path)) {
                    self::$RGBColorProfile = file_get_contents($path);
                }
            }
        }

        return self::$RGBColorProfile;
    }

    /**
     * @param  $width
     * @param  $height
     * @return Pimcore_Image_Adapter
     */
    public function resize ($width, $height) {

        $this->preModify();

        // this is the check for vector formats because they need to have a resolution set
        // this does only work if "resize" is the first step in the image-pipeline

        if($this->isVectorGraphic()) {
            // the resolution has to be set before loading the image, that's why we have to destroy the instance and load it again
            $res = $this->resource->getImageResolution();
            $x_ratio = $res['x'] / $this->resource->getImageWidth();
            $y_ratio = $res['y'] / $this->resource->getImageHeight();
            $this->resource->removeImage();

            $this->resource->setResolution($width * $x_ratio, $height * $y_ratio);
            $this->resource->readImage($this->imagePath);
        }

        $width  = (int)$width;
        $height = (int)$height;

        $this->resource->resizeimage($width, $height, Imagick::FILTER_UNDEFINED, 1, false);

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
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function crop($x, $y, $width, $height) {

        $this->preModify();

        $this->resource->cropImage($width, $height, $x, $y);
        $this->resource->setImagePage($width, $height, 0, 0);

        $this->setWidth($width);
        $this->setHeight($height);

        $this->postModify();

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

        $this->preModify();

        $this->contain($width, $height);

        $x = ($width - $this->getWidth()) / 2;
        $y = ($height - $this->getHeight()) / 2;


        $newImage = $this->createImage($width, $height);
        $newImage->compositeImage($this->resource, Imagick::COMPOSITE_COPY , $x, $y);
        $this->resource = $newImage;

        $this->setWidth($width);
        $this->setHeight($height);

        $this->postModify();

        return $this;
    }

    /**
     * @param  $color
     * @return Pimcore_Image_Adapter
     */
    public function setBackgroundColor ($color) {

        $this->preModify();

        $newImage = $this->createImage($this->getWidth(), $this->getHeight(), $color);
        $newImage->compositeImage($this->resource, Imagick::COMPOSITE_DEFAULT , 0, 0);
        $this->resource = $newImage;

        $this->postModify();

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

        $this->preModify();

        $this->resource->rotateImage(new ImagickPixel('none'), $angle);
        $this->setWidth($this->resource->getimagewidth());
        $this->setHeight($this->resource->getimageheight());

        $this->postModify();

        return $this;
    }


    /**
     * @param  $x
     * @param  $y
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function roundCorners ($x, $y) {

        $this->preModify();

        $this->resource->roundCorners($x, $y);

        $this->postModify();

        return $this;
    }


    /**
     * @param  $color
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function setBackgroundImage ($image) {

        $this->preModify();

        $image = ltrim($image,"/");
        $image = PIMCORE_DOCUMENT_ROOT . "/" . $image;

        if(is_file($image)) {
            $newImage = new Imagick();
            $newImage->readimage($image);
            $newImage->resizeimage($this->getWidth(), $this->getHeight(), Imagick::FILTER_UNDEFINED, 1, false);
            $newImage->compositeImage($this->resource, Imagick::COMPOSITE_DEFAULT, 0 ,0);
            $this->resource = $newImage;
        }

        $this->postModify();

        return $this;
    }

    /**
     * @param string $image
     * @param int $x Amount of horizontal pixels the overlay should be offset from the origin
     * @param int $y Amount of vertical pixels the overlay should be offset from the origin
     * @param int $alpha Opacity in a scale of 0 (transparent) to 100 (opaque)
     * @param string $origin Origin of the X and Y coordinates (top-left, top-right, bottom-left, bottom-right or center)
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function  addOverlay ($image, $x = 0, $y = 0, $alpha = 100, $composite = "COMPOSITE_DEFAULT", $origin = 'top-left') {

        $this->preModify();

        $image = ltrim($image,"/");
        $image = PIMCORE_DOCUMENT_ROOT . "/" . $image;

        // 100 alpha is default
        if(empty($alpha)) {
            $alpha = 100;
        }
        $alpha = round($alpha / 100, 1);

        //Make sure the composite constant exists.
        if(is_null(constant("Imagick::" . $composite))) {
            $composite = "COMPOSITE_DEFAULT";
        }

        if(is_file($image)) {
            $newImage = new Imagick();
            $newImage->readimage($image);

            if($origin == 'top-right') {
                $x = $this->resource->getImageWidth() - $newImage->getImageWidth() - $x;
            } elseif($origin == 'bottom-left') {
                $y = $this->resource->getImageHeight() - $newImage->getImageHeight() - $y;
            } elseif($origin == 'bottom-right') {
                $x = $this->resource->getImageWidth() - $newImage->getImageWidth() - $x;
                $y = $this->resource->getImageHeight() - $newImage->getImageHeight() - $y;
            } elseif($origin == 'center') {
                $x = round($this->resource->getImageWidth() / 2) - round($newImage->getImageWidth() / 2) + $x;
                $y = round($this->resource->getImageHeight() / 2) -round($newImage->getImageHeight() / 2) + $y;
            }

            $newImage->evaluateImage(Imagick::EVALUATE_MULTIPLY, $alpha, Imagick::CHANNEL_ALPHA); 
            $this->resource->compositeImage($newImage, constant("Imagick::" . $composite), $x ,$y);
        }

        $this->postModify();

        return $this;
    }


    /**
     * @param  $image
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function applyMask ($image) {

        $this->preModify();
        $image = ltrim($image,"/");
        $image = PIMCORE_DOCUMENT_ROOT . "/" . $image;

        if(is_file($image)) {
            $this->resource->setImageMatte(1);
            $newImage = new Imagick();
            $newImage->readimage($image);
            $newImage->resizeimage($this->getWidth(), $this->getHeight(), Imagick::FILTER_UNDEFINED, 1, false);
            $this->resource->compositeImage($newImage, Imagick::COMPOSITE_COPYOPACITY, 0 ,0, Imagick::CHANNEL_ALPHA);
        }

        $this->postModify();

        return $this;
    }


    /**
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function grayscale () {

        $this->preModify();
        $this->resource->setImageType(imagick::IMGTYPE_GRAYSCALEMATTE);
        $this->postModify();

        return $this;
    }

    /**
     * @return Pimcore_Image_Adapter_Imagick
     */
    public function sepia () {

        $this->preModify();
        $this->resource->sepiatoneimage(85);
        $this->postModify();

        return $this;
    }

    /**
     * Sharpen the image with an unsharp mask operator. The image is convolved
     * with a Gaussian operator of the given radius and standard deviation (sigma).
     * For reasonable results, radius should be larger than sigma.
     * Use a radius of 0 to have the method select a suitable radius.
     *
     * @param float $radius The radius of the Gaussian, in pixels, not counting
     *        the center pixel.
     * @param float $sigma The standard deviation of the Gaussian, in pixels.
     * @param float $amount The fraction of the difference between the original
     *        and the blur image that is added back into the original.
     * @param float $threshold The threshold, as a fraction of QuantumRange,
     *        needed to apply the difference amount.
     * @return \Pimcore_Image_Adapter_Imagick
     */
    public function sharpen ($radius = 0, $sigma = 1.0, $amount = 1.0, $threshold = 0.05) {

        $this->preModify();
        $this->resource->normalizeImage();
        $this->resource->unsharpMaskImage($radius, $sigma, $amount, $threshold);
        $this->postModify();

        return $this;
    }

    public function isVectorGraphic () {

        try {
            $type = $this->resource->getimageformat();
            $vectorTypes = array("EPT","EPDF","EPI","EPS","EPS2","EPS3","EPSF","EPSI","EPT","PDF","PFA","PFB","PFM","PS","PS2","PS3","PSB","SVG","SVGZ");

            if(in_array($type,$vectorTypes)) {
                return true;
            }
        } catch (Exception $e) {
            Logger::err($e);
        }

        return false;
    }
}
