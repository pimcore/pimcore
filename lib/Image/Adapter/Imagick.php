<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Image\Adapter;

use Pimcore\Cache;
use Pimcore\Config;
use Pimcore\Image\Adapter;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Symfony\Component\Filesystem\Filesystem;

class Imagick extends Adapter
{
    protected static ?string $RGBColorProfile = null;

    protected static ?string $CMYKColorProfile = null;

    /**
     * @var \Imagick|null
     */
    protected mixed $resource = null;

    protected string $imagePath;

    /**
     * @var array<string, bool>
     */
    protected static array $supportedFormatsCache = [];

    public function load(string $imagePath, array $options = []): static|false
    {
        if (isset($options['preserveColor'])) {
            // set this option to TRUE to skip all color transformations during the loading process
            // this can massively improve performance if the color information doesn't matter, ...
            // eg. when using this function to obtain dimensions from an image
            $this->setPreserveColor($options['preserveColor']);
        }

        if (isset($options['asset']) && preg_match('@\.svgz?$@', $imagePath) && preg_match('@[^a-zA-Z0-9\-\.~_/]+@', $imagePath)) {
            // Imagick/Inkscape delegate has problems with special characters in the file path, eg. "ß" causes
            // Inkscape to completely go crazy -> Debian 8.10, Inkscape 0.48.5 r10040, Imagick 6.8.9-9 Q16, Imagick 3.4.3
            // we create a local temp file, to workaround this problem
            $imagePath = $options['asset']->getTemporaryFile();
            $this->tmpFiles[] = $imagePath;
        }

        if ($this->resource) {
            unset($this->resource);
            $this->resource = null;
        }

        try {
            $i = new \Imagick();
            $this->imagePath = $imagePath;

            if (isset($options['resolution'])) {
                $i->setResolution($options['resolution']['x'], $options['resolution']['y']);
            }

            $imagePathLoad = $imagePath;

            $imagePathLoad = $imagePathLoad . '[0]';

            if (!$i->readImage($imagePathLoad) || !@filesize($imagePath)) {
                return false;
            }

            $this->resource = $i;

            if (!$this->reinitializing && !$this->isPreserveColor()) {
                $i->setColorspace(\Imagick::COLORSPACE_SRGB);

                if ($this->isVectorGraphic($imagePath)) {
                    // only for vector graphics
                    // the below causes problems with PSDs when target format is PNG32 (nobody knows why ;-))
                    $i->setBackgroundColor(new \ImagickPixel('transparent'));
                    //for certain edge-cases simply setting the background-color to transparent does not seem to work
                    //workaround by using transparentPaintImage (somehow even works without setting a target. no clue why)
                    $i->transparentPaintImage('', 1, 0, false);
                }

                $this->setColorspaceToRGB();
            }

            // set dimensions
            $dimensions = $this->getDimensions();
            $this->setWidth($dimensions['width']);
            $this->setHeight($dimensions['height']);

            if (!$this->sourceImageFormat) {
                $this->sourceImageFormat = $i->getImageFormat();
            }

            // check if image can have alpha channel
            if (!$this->reinitializing) {
                $alphaChannel = $i->getImageAlphaChannel();
                if ($alphaChannel) {
                    $this->setIsAlphaPossible(true);
                }
            }

            if ($this->checkPreserveAnimation($i->getImageFormat(), $i, false)) {
                if (!$this->resource->readImage($imagePath) || !filesize($imagePath)) {
                    return false;
                }
                $this->resource = $this->resource->coalesceImages();
            }

            $isClipAutoSupport = Config::getSystemConfiguration('assets')['image']['thumbnails']['clip_auto_support'];
            if ($isClipAutoSupport && !$this->reinitializing && $this->has8BIMClippingPath()) {
                // the following way of determining a clipping path is very resource intensive (using Imagick),
                // so we try with the approach in has8BIMClippingPath() instead
                // check for the existence of an embedded clipping path (8BIM / Adobe profile meta data)
                //$identifyRaw = $i->identifyImage(true)['rawOutput'];
                //if (strpos($identifyRaw, 'Clipping path') && strpos($identifyRaw, '<svg')) {
                // if there's a clipping path embedded, apply the first one
                try {
                    $i->setImageAlphaChannel(\Imagick::ALPHACHANNEL_TRANSPARENT);
                    $i->clipImage();
                    $i->setImageAlphaChannel(\Imagick::ALPHACHANNEL_OPAQUE);
                } catch (\Exception $e) {
                    Logger::info(sprintf('Although automatic clipping support is enabled, your current ImageMagick / Imagick version does not support this operation on the image %s', $imagePath));
                }
                //}
            }
        } catch (\Exception $e) {
            Logger::error('Unable to load image ' . $imagePath . ': ' . $e);

            return false;
        }

        $this->setModified(false);

        return $this;
    }

    private function has8BIMClippingPath(): bool
    {
        $handle = fopen($this->imagePath, 'rb');
        $chunk = fread($handle, 1024*1000); // read the first 1MB
        fclose($handle);

        // according to 8BIM format: https://www.adobe.com/devnet-apps/photoshop/fileformatashtml/#50577409_pgfId-1037504
        // we're looking for the resource id 'Name of clipping path' which is 8BIM 2999 (decimal) or 0x0BB7 in hex
        if (preg_match('/8BIM\x0b\xb7/', $chunk)) {
            return true;
        }

        return false;
    }

    public function getContentOptimizedFormat(): string
    {
        $format = 'pjpeg';
        if ($this->hasAlphaChannel()) {
            $format = 'png32';
        }

        return $format;
    }

    public function save(string $path, string $format = null, int $quality = null): static
    {
        if (!$format) {
            $format = 'png32';
        }

        if ($format == 'original') {
            $format = $this->sourceImageFormat;
        }

        $format = strtolower($format);

        if ($format == 'png') {
            // we need to force imagick to create png32 images, otherwise this can cause some strange effects
            // when used with gray-scale images
            $format = 'png32';
        }

        $originalFilename = null;
        $i = $this->resource; // this is because of HHVM which has problems with $this->resource->writeImage();

        if (in_array($format, ['jpeg', 'pjpeg', 'jpg']) && $this->isAlphaPossible) {
            // set white background for transparent pixels
            $i->setImageBackgroundColor('#ffffff');

            if ($i->getImageAlphaChannel() !== 0) { // Note: returns (int) 0 if there's no AlphaChannel, PHP Docs are wrong. See: https://www.imagemagick.org/api/channel.php
                // Imagick version compatibility
                $alphaChannel = 11; // This works at least as far back as version 3.1.0~rc1-1
                if (defined('Imagick::ALPHACHANNEL_REMOVE')) {
                    // Imagick::ALPHACHANNEL_REMOVE has been added in 3.2.0b2
                    $alphaChannel = \Imagick::ALPHACHANNEL_REMOVE;
                }
                $i->setImageAlphaChannel($alphaChannel);
            }

            $i->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
        }

        if (!$this->isPreserveMetaData()) {
            $i->stripImage();
            if ($format == 'png32') {
                // do not include any meta-data
                // this is due a bug in -strip, therefore we have to use this custom option
                // see also: https://github.com/ImageMagick/ImageMagick/issues/156
                $i->setOption('png:include-chunk', 'none');
            }
        }
        if (!$this->isPreserveColor()) {
            $i->profileImage('*', '');
        }

        if ($quality && !$this->isPreserveColor()) {
            $i->setCompressionQuality($quality);
            $i->setImageCompressionQuality($quality);
        }

        if ($format == 'tiff') {
            $i->setCompression(\Imagick::COMPRESSION_LZW);
        }

        // force progressive JPEG if filesize >= 10k
        // normally jpeg images are bigger than 10k so we avoid the double compression (baseline => filesize check => if necessary progressive)
        // and check the dimensions here instead to faster generate the image
        // progressive JPEG - better compression, smaller filesize, especially for web optimization
        if ($format == 'jpeg' && !$this->isPreserveColor()) {
            if (($this->getWidth() * $this->getHeight()) > 35000) {
                $i->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
            }
        }

        // Imagick isn't able to work with custom stream wrappers, so we make a workaround
        $realTargetPath = null;
        if (!stream_is_local($path)) {
            $realTargetPath = $path;
            $path = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/imagick-tmp-' . uniqid() . '.' . pathinfo($path, PATHINFO_EXTENSION);
        }

        $filesystem = new Filesystem();
        if (!stream_is_local($path)) {
            $i->setImageFormat($format);
            $filesystem->dumpFile($path, $i->getImageBlob());
            $success = file_exists($path);
        } else {
            if ($this->checkPreserveAnimation($format, $i)) {
                $success = $i->writeImages('GIF:' . $path, true);
            } else {
                $success = $i->writeImage($format . ':' . $path);
            }
        }

        if (!$success) {
            throw new \Exception('Unable to write image: ' . $path);
        }

        if ($realTargetPath) {
            $filesystem->rename($path, $realTargetPath, true);
        }

        return $this;
    }

    private function checkPreserveAnimation(string $format = '', \Imagick $i = null, bool $checkNumberOfImages = true): bool
    {
        if (!$this->isPreserveAnimation()) {
            return false;
        }

        if (!$i) {
            $i = $this->resource;
        }

        if ($i && $checkNumberOfImages && $i->getNumberImages() <= 1) {
            return false;
        }

        if ($format && !in_array(strtolower($format), ['gif', 'original', 'auto'])) {
            return false;
        }

        return true;
    }

    protected function destroy(): void
    {
        if ($this->resource) {
            $this->resource->clear();
            $this->resource->destroy();
            $this->resource = null;
        }
    }

    private function hasAlphaChannel(): bool
    {
        if ($this->isAlphaPossible) {
            $width = $this->resource->getImageWidth(); // Get the width of the image
            $height = $this->resource->getImageHeight(); // Get the height of the image

            // We run the image pixel by pixel and as soon as we find a transparent pixel we stop and return true.
            for ($i = 0; $i < $width; $i++) {
                for ($j = 0; $j < $height; $j++) {
                    $pixel = $this->resource->getImagePixelColor($i, $j);
                    $color = $pixel->getColor(1); // get the real alpha not just 1/0
                    if ($color['a'] < 1) { // if there's an alpha pixel, return true
                        return true;
                    }
                }
            }
        }

        // If we dont find any pixel the function will return false.
        return false;
    }

    private function setColorspaceToRGB(): static
    {
        $imageColorspace = $this->resource->getImageColorspace();

        if (in_array($imageColorspace, [\Imagick::COLORSPACE_RGB, \Imagick::COLORSPACE_SRGB])) {
            // no need to process (s)RGB images
            return $this;
        }

        $profiles = $this->resource->getImageProfiles('icc', true);

        if (isset($profiles['icc'])) {
            if (str_contains($profiles['icc'], 'RGB')) {
                // no need to process (s)RGB images
                return $this;
            }

            // Workaround for ImageMagick (e.g. 6.9.10-23) bug, that lets it crash immediately if the tagged colorspace is
            // different from the colorspace of the embedded icc color profile
            // If that is the case we just ignore the color profiles
            if (str_contains($profiles['icc'], 'CMYK') && $imageColorspace !== \Imagick::COLORSPACE_CMYK) {
                return $this;
            }
        }

        if ($imageColorspace == \Imagick::COLORSPACE_CMYK) {
            if (self::getCMYKColorProfile() && self::getRGBColorProfile()) {
                // if it doesn't have a CMYK ICC profile, we add one
                if (!isset($profiles['icc'])) {
                    $this->resource->profileImage('icc', self::getCMYKColorProfile());
                }
                // then we add an RGB profile
                $this->resource->profileImage('icc', self::getRGBColorProfile());
                $this->resource->setImageColorspace(\Imagick::COLORSPACE_SRGB); // we have to use SRGB here, no clue why but it works
            } else {
                $this->resource->setImageColorspace(\Imagick::COLORSPACE_SRGB);
            }
        } elseif ($imageColorspace == \Imagick::COLORSPACE_GRAY) {
            $this->resource->setImageColorspace(\Imagick::COLORSPACE_SRGB);
        } elseif (!in_array($imageColorspace, [\Imagick::COLORSPACE_RGB, \Imagick::COLORSPACE_SRGB])) {
            $this->resource->setImageColorspace(\Imagick::COLORSPACE_SRGB);
        } else {
            // this is to handle all other embedded icc profiles
            if (isset($profiles['icc'])) {
                try {
                    // if getImageColorspace() says SRGB but the embedded icc profile is CMYK profileImage() will throw an exception
                    $this->resource->profileImage('icc', self::getRGBColorProfile());
                    $this->resource->setImageColorspace(\Imagick::COLORSPACE_SRGB);
                } catch (\Exception $e) {
                    Logger::warn((string) $e);
                }
            }
        }

        // this is a HACK to force grayscale images to be real RGB - truecolor, this is important if you want to use
        // thumbnails in PDF's because they do not support "real" grayscale JPEGs or PNGs
        // problem is described here: http://imagemagick.org/Usage/basics/#type
        // and here: http://www.imagemagick.org/discourse-server/viewtopic.php?f=2&t=6888#p31891

        // 20.7.2018: this seems to cause new issues with newer Imagick/PHP versions, so we take it out for now ...
        //  not sure if this workaround is actually still necessary (wouldn't assume so).

        /*$currentLocale = setlocale(LC_ALL, '0'); // this locale hack thing is also a hack for imagick
        setlocale(LC_ALL, 'en'); // Set locale to "en" for ImagickDraw::point() to ensure the involved tostring() methods keep the decimal point

        $draw = new \ImagickDraw();
        $draw->setFillColor('#ff0000');
        $draw->setfillopacity(.01);
        $draw->point(floor($this->getWidth() / 2), floor($this->getHeight() / 2)); // place it in the middle of the image
        $this->resource->drawImage($draw);

        setlocale(LC_ALL, $currentLocale); // see setlocale() above, for details ;-)
        */

        return $this;
    }

    /**
     *
     * @internal
     */
    public static function setCMYKColorProfile(string $CMYKColorProfile): void
    {
        self::$CMYKColorProfile = $CMYKColorProfile;
    }

    /**
     * @internal
     *
     */
    public static function getCMYKColorProfile(): string
    {
        if (!self::$CMYKColorProfile) {
            $path = Config::getSystemConfiguration('assets')['icc_cmyk_profile'] ?? null;
            if (!$path || !file_exists($path)) {
                $path = __DIR__ . '/../icc-profiles/ISOcoated_v2_eci.icc'; // default profile
            }

            if (file_exists($path)) {
                self::$CMYKColorProfile = file_get_contents($path);
            }
        }

        return self::$CMYKColorProfile;
    }

    /**
     *
     * @internal
     *
     */
    public static function setRGBColorProfile(string $RGBColorProfile): void
    {
        self::$RGBColorProfile = $RGBColorProfile;
    }

    /**
     * @internal
     *
     */
    public static function getRGBColorProfile(): string
    {
        if (!self::$RGBColorProfile) {
            $path = Config::getSystemConfiguration('assets')['icc_rgb_profile'] ?? null;
            if (!$path || !file_exists($path)) {
                $path = __DIR__ . '/../icc-profiles/sRGB_IEC61966-2-1_black_scaled.icc'; // default profile
            }

            if (file_exists($path)) {
                self::$RGBColorProfile = file_get_contents($path);
            }
        }

        return self::$RGBColorProfile;
    }

    public function resize(int $width, int $height): static
    {
        $this->preModify();

        // this is the check for vector formats because they need to have a resolution set
        // this does only work if "resize" is the first step in the image-pipeline

        if ($this->isVectorGraphic()) {
            // the resolution has to be set before loading the image, that's why we have to destroy the instance and load it again
            $res = $this->resource->getImageResolution();
            if ($res['x'] && $res['y']) {
                $x_ratio = $res['x'] / $this->getWidth();
                $y_ratio = $res['y'] / $this->getHeight();
                $this->resource->removeImage();

                $newRes = ['x' => $width * $x_ratio, 'y' => $height * $y_ratio];

                // only use the calculated resolution if we need a higher one that the one we got from the metadata (getImageResolution)
                // this is because sometimes the quality is much better when using the "native" resolution from the metadata
                if ($newRes['x'] > $res['x'] && $newRes['y'] > $res['y']) {
                    $res = $newRes;
                }
            } else {
                // this is mostly for SVGs, it seems that getImageResolution() doesn't return a value anymore for SVGs
                // so we calculate the density ourselves, Inkscape/ImageMagick seem to use 96ppi, so that's how we get
                // the right values for -density (setResolution)
                $res = [
                    'x' => ($width / $this->getWidth()) * 96,
                    'y' => ($height / $this->getHeight()) * 96,
                ];
            }

            $this->resource->setResolution($res['x'], $res['y']);
            $this->resource->readImage($this->imagePath);

            if (!$this->isPreserveColor()) {
                $this->setColorspaceToRGB();
            }
        }

        if ($this->getWidth() !== $width || $this->getHeight() !== $height) {
            if ($this->checkPreserveAnimation()) {
                foreach ($this->resource as $i => $frame) {
                    $frame->resizeimage($width, $height, \Imagick::FILTER_UNDEFINED, 1, false);
                }
            } else {
                $this->resource->resizeimage($width, $height, \Imagick::FILTER_UNDEFINED, 1, false);
            }
            $this->setWidth($width);
            $this->setHeight($height);
        }

        $this->postModify();

        return $this;
    }

    public function crop(int $x, int $y, int $width, int $height): static
    {
        $this->preModify();

        $this->resource->cropImage($width, $height, $x, $y);
        $this->resource->setImagePage($width, $height, 0, 0);

        $this->setWidth($width);
        $this->setHeight($height);

        $this->postModify();

        return $this;
    }

    public function frame(int $width, int $height, bool $forceResize = false): static
    {
        $this->preModify();

        $this->contain($width, $height, $forceResize);

        $x = (int)(($width - $this->getWidth()) / 2);
        $y = (int)(($height - $this->getHeight()) / 2);

        $newImage = $this->createCompositeImageFromResource($width, $height, $x, $y);
        $this->resource = $newImage;

        $this->setWidth($width);
        $this->setHeight($height);

        $this->postModify();

        $this->setIsAlphaPossible(true);

        return $this;
    }

    public function trim(int $tolerance): static
    {
        $this->preModify();

        $this->resource->trimimage($tolerance);

        $dimensions = $this->getDimensions();
        $this->setWidth($dimensions['width']);
        $this->setHeight($dimensions['height']);

        $this->postModify();

        return $this;
    }

    public function setBackgroundColor(string $color): static
    {
        $this->preModify();
        $newImage = $this->createCompositeImageFromResource($this->getWidth(), $this->getHeight(), 0, 0, $color);
        $this->resource = $newImage;

        $this->postModify();

        $this->setIsAlphaPossible(false);

        return $this;
    }

    private function createImage(int $width, int $height, string $color = 'transparent'): \Imagick
    {
        $newImage = new \Imagick();
        $newImage->newimage($width, $height, $color);
        $newImage->setImageFormat($this->resource->getImageFormat());

        return $newImage;
    }

    private function createCompositeImageFromResource(int $width, int $height, int $x, int $y, string $color = 'transparent', int $composite = \Imagick::COMPOSITE_DEFAULT): \Imagick
    {
        $newImage = null;
        if ($this->checkPreserveAnimation()) {
            foreach ($this->resource as $i => $frame) {
                $imageFrame = $this->createImage($width, $height, $color);
                $imageFrame->compositeImage($frame, $composite, $x, $y);
                if (!$newImage) {
                    $newImage = $imageFrame;
                } else {
                    $newImage->addImage($imageFrame);
                }
            }
        } else {
            $newImage = $this->createImage($width, $height, $color);
            $newImage->compositeImage($this->resource, $composite, $x, $y);
        }

        return $newImage;
    }

    public function rotate(int $angle): static
    {
        $this->preModify();

        $this->resource->rotateImage(new \ImagickPixel('none'), $angle);
        $this->setWidth($this->resource->getimagewidth());
        $this->setHeight($this->resource->getimageheight());

        $this->postModify();

        $this->setIsAlphaPossible(true);

        return $this;
    }

    public function roundCorners(int $width, int $height): static
    {
        $this->preModify();

        $this->internalRoundCorners($width, $height);

        $this->postModify();

        $this->setIsAlphaPossible(true);

        return $this;
    }

    /**
     * Workaround for Imagick PHP extension v3.4.4 which removed Imagick::roundCorners
     */
    private function internalRoundCorners(int $width, int $height): void
    {
        $imageWidth = $this->resource->getImageWidth();
        $imageHeight = $this->resource->getImageHeight();

        $rectangle = new \ImagickDraw();
        $rectangle->setFillColor(new \ImagickPixel('black'));
        $rectangle->roundRectangle(0, 0, $imageWidth - 1, $imageHeight - 1, $width, $height);

        $mask = new \Imagick();
        $mask->newImage($imageWidth, $imageHeight, new \ImagickPixel('transparent'), 'png');
        $mask->drawImage($rectangle);

        $this->resource->compositeImage($mask, \Imagick::COMPOSITE_DSTIN, 0, 0);
    }

    public function setBackgroundImage(string $image, string $mode = null): static
    {
        $this->preModify();

        $image = ltrim($image, '/');
        $image = PIMCORE_WEB_ROOT . '/' . $image;

        if (is_file($image)) {
            $newImage = new \Imagick();

            if ($mode == 'asTexture') {
                $newImage->newImage($this->getWidth(), $this->getHeight(), new \ImagickPixel());
                $texture = new \Imagick($image);
                $newImage = $newImage->textureImage($texture);
            } else {
                $newImage->readimage($image);
                if ($mode == 'cropTopLeft') {
                    $newImage->cropImage($this->getWidth(), $this->getHeight(), 0, 0);
                } else {
                    // default behavior (fit)
                    $newImage->resizeimage($this->getWidth(), $this->getHeight(), \Imagick::FILTER_UNDEFINED, 1, false);
                }
            }

            $newImage->compositeImage($this->resource, \Imagick::COMPOSITE_DEFAULT, 0, 0);
            $this->resource = $newImage;
        }

        $this->postModify();

        return $this;
    }

    public function addOverlay(mixed $image, int $x = 0, int $y = 0, int $alpha = 100, string $composite = 'COMPOSITE_DEFAULT', string $origin = 'top-left'): static
    {
        $this->preModify();

        // 100 alpha is default
        if (empty($alpha)) {
            $alpha = 100;
        }
        $alpha = round($alpha / 100, 1);

        //Make sure the composite constant exists.
        if (is_null(constant('Imagick::' . $composite))) {
            $composite = 'COMPOSITE_DEFAULT';
        }

        $newImage = null;

        if (is_string($image)) {
            $asset = Asset\Image::getByPath($image);
            if ($asset instanceof Asset\Image) {
                $image = $asset->getTemporaryFile();
            } else {
                trigger_deprecation(
                    'pimcore/pimcore',
                    '10.3',
                    'Using relative path for Image Thumbnail overlay is deprecated, use Asset Image path.'
                );

                $image = ltrim($image, '/');
                $image = PIMCORE_PROJECT_ROOT . '/' . $image;
            }

            $newImage = new \Imagick();
            $newImage->readimage($image);
        } elseif ($image instanceof \Imagick) {
            $newImage = $image;
        }

        if ($newImage) {
            if ($origin === 'top-right') {
                $x = $this->resource->getImageWidth() - $newImage->getImageWidth() - $x;
            } elseif ($origin === 'bottom-left') {
                $y = $this->resource->getImageHeight() - $newImage->getImageHeight() - $y;
            } elseif ($origin === 'bottom-right') {
                $x = $this->resource->getImageWidth() - $newImage->getImageWidth() - $x;
                $y = $this->resource->getImageHeight() - $newImage->getImageHeight() - $y;
            } elseif ($origin === 'center') {
                $x = round($this->resource->getImageWidth() / 2) - round($newImage->getImageWidth() / 2) + $x;
                $y = round($this->resource->getImageHeight() / 2) - round($newImage->getImageHeight() / 2) + $y;
            }

            $newImage->evaluateImage(\Imagick::EVALUATE_MULTIPLY, $alpha, \Imagick::CHANNEL_ALPHA);
            $this->resource->compositeImage($newImage, constant('Imagick::' . $composite), (int)$x, (int)$y);
        }

        $this->postModify();

        return $this;
    }

    public function addOverlayFit(string $image, string $composite = 'COMPOSITE_DEFAULT'): static
    {
        $asset = Asset\Image::getByPath($image);
        if ($asset instanceof Asset\Image) {
            $image = $asset->getTemporaryFile();
        } else {
            trigger_deprecation(
                'pimcore/pimcore',
                '10.3',
                'Using relative path for Image Thumbnail overlay is deprecated, use Asset Image path.'
            );

            $image = ltrim($image, '/');
            $image = PIMCORE_PROJECT_ROOT . '/' . $image;
        }

        $newImage = new \Imagick();
        $newImage->readimage($image);
        $newImage->resizeimage($this->getWidth(), $this->getHeight(), \Imagick::FILTER_UNDEFINED, 1, false);

        $this->addOverlay($newImage, 0, 0, 100, $composite);

        return $this;
    }

    public function applyMask(string $image): static
    {
        $this->preModify();
        $image = ltrim($image, '/');
        $image = PIMCORE_PROJECT_ROOT . '/' . $image;

        if (is_file($image)) {
            $this->resource->setImageMatte(true);
            $newImage = new \Imagick();
            $newImage->readimage($image);
            $newImage->resizeimage($this->getWidth(), $this->getHeight(), \Imagick::FILTER_UNDEFINED, 1, false);
            $this->resource->compositeImage($newImage, \Imagick::COMPOSITE_COPYOPACITY, 0, 0, \Imagick::CHANNEL_ALPHA);
        }

        $this->postModify();

        $this->setIsAlphaPossible(true);

        return $this;
    }

    public function grayscale(): static
    {
        $this->preModify();
        $this->resource->setImageType(\Imagick::IMGTYPE_GRAYSCALEMATTE);
        $this->postModify();

        return $this;
    }

    public function sepia(): static
    {
        $this->preModify();
        $this->resource->sepiatoneimage(85);
        $this->postModify();

        return $this;
    }

    public function sharpen(float $radius = 0, float $sigma = 1.0, float $amount = 1.0, float $threshold = 0.05): static
    {
        $this->preModify();
        $this->resource->normalizeImage();
        $this->resource->unsharpMaskImage($radius, $sigma, $amount, $threshold);
        $this->postModify();

        return $this;
    }

    public function gaussianBlur(int $radius = 0, float $sigma = 1.0): static
    {
        $this->preModify();
        $this->resource->gaussianBlurImage($radius, $sigma);
        $this->postModify();

        return $this;
    }

    public function brightnessSaturation(int $brightness = 100, int $saturation = 100, int $hue = 100): static
    {
        $this->preModify();
        $this->resource->modulateImage($brightness, $saturation, $hue);
        $this->postModify();

        return $this;
    }

    public function mirror(string $mode): static
    {
        $this->preModify();

        if ($mode == 'vertical') {
            $this->resource->flipImage();
        } elseif ($mode == 'horizontal') {
            $this->resource->flopImage();
        }

        $this->postModify();

        return $this;
    }

    public function isVectorGraphic(?string $imagePath = null): bool
    {
        if (!$imagePath) {
            $imagePath = $this->imagePath;
        }

        // we need to do this check first, because ImageMagick using the inkscape delegate returns "PNG" when calling
        // getimageformat() onto SVG graphics, this is a workaround to avoid problems
        if (preg_match("@\.(svgz?|eps|pdf|ps|ai|indd)$@i", $imagePath)) {
            return true;
        }

        try {
            if ($this->resource) {
                $type = $this->resource->getimageformat();
                $vectorTypes = [
                    'EPT',
                    'EPDF',
                    'EPI',
                    'EPS',
                    'EPS2',
                    'EPS3',
                    'EPSF',
                    'EPSI',
                    'EPT',
                    'PDF',
                    'PFA',
                    'PFB',
                    'PFM',
                    'PS',
                    'PS2',
                    'PS3',
                    'SVG',
                    'SVGZ',
                    'MVG',
                ];

                if (in_array(strtoupper($type), $vectorTypes)) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            Logger::err((string) $e);
        }

        return false;
    }

    private function getDimensions(): array
    {
        if ($vectorDimensions = $this->getVectorFormatEmbeddedRasterDimensions()) {
            return $vectorDimensions;
        }

        return [
            'width' => $this->resource->getImageWidth(),
            'height' => $this->resource->getImageHeight(),
        ];
    }

    private function getVectorFormatEmbeddedRasterDimensions(): ?array
    {
        if (in_array($this->resource->getimageformat(), ['EPT', 'EPDF', 'EPI', 'EPS', 'EPS2', 'EPS3', 'EPSF', 'EPSI', 'EPT', 'PDF', 'PFA', 'PFB', 'PFM', 'PS', 'PS2', 'PS3'])) {
            // we need a special handling for PhotoShop EPS
            $i = 0;

            $epsFile = fopen($this->imagePath, 'r');
            while (($eps_line = fgets($epsFile)) && ($i < 100)) {
                if (preg_match('/%ImageData: ([0-9]+) ([0-9]+)/i', $eps_line, $matches)) {
                    return [
                        'width' => (int) $matches[1],
                        'height' => (int) $matches[2],
                    ];
                }
                $i++;
            }
        }

        return null;
    }

    protected function getVectorRasterDimensions(): array
    {
        if ($vectorDimensions = $this->getVectorFormatEmbeddedRasterDimensions()) {
            return $vectorDimensions;
        }

        return parent::getVectorRasterDimensions();
    }

    public function supportsFormat(string $format, bool $force = false): bool
    {
        if ($force) {
            return $this->checkFormatSupport($format);
        }

        if (!isset(self::$supportedFormatsCache[$format])) {
            // since determining if an image format is supported is quite expensive we use two-tiered caching
            // in-process caching (static variable) and the shared cache
            $cacheKey = 'imagick_format_' . $format;
            if (($cachedValue = Cache::load($cacheKey)) !== false) {
                self::$supportedFormatsCache[$format] = (bool) $cachedValue;
            } else {
                self::$supportedFormatsCache[$format] = $this->checkFormatSupport($format);

                // we cache the status as an int, so that we know if the status was cached or not, with bool that wouldn't be possible, since load() returns false if item doesn't exists
                Cache::save((int) self::$supportedFormatsCache[$format], $cacheKey, [], null, 999, true);
            }
        }

        return self::$supportedFormatsCache[$format];
    }

    private function checkFormatSupport(string $format): bool
    {
        try {
            // we can't use \Imagick::queryFormats() here, because this doesn't consider configured delegates
            $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/imagick-format-support-detection-' . uniqid() . '.' . $format;
            $image = new \Imagick();
            $image->newImage(1, 1, new \ImagickPixel('red'));
            $image->writeImage($format . ':' . $tmpFile);
            unlink($tmpFile);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
