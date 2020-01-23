<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Image\Adapter;

use Pimcore\Image\Adapter;

class GD extends Adapter
{
    /**
     * @var string
     */
    protected $path;

    /**
     * contains imageresource
     *
     * @var mixed
     */
    protected $resource;

    /**
     * @param string $imagePath
     * @param array $options
     *
     * @return $this|self
     */
    public function load($imagePath, $options = [])
    {
        $this->path = $imagePath;
        if (!$this->resource = @imagecreatefromstring(file_get_contents($this->path))) {
            return false;
        }

        // set dimensions
        list($width, $height) = getimagesize($this->path);
        $this->setWidth($width);
        $this->setHeight($height);

        if (!$this->sourceImageFormat) {
            $this->sourceImageFormat = \Pimcore\File::getFileExtension($imagePath);
        }

        if (in_array(\Pimcore\File::getFileExtension($imagePath), ['png', 'gif'])) {
            // in GD only gif and PNG can have an alphachannel
            $this->setIsAlphaPossible(true);
        }

        $this->setModified(false);

        return $this;
    }

    /**
     * @return string
     */
    public function getContentOptimizedFormat()
    {
        $format = 'pjpeg';
        if ($this->hasAlphaChannel()) {
            $format = 'png';
        }

        return $format;
    }

    /**
     * @param string $path
     * @param string|null $format
     * @param int|null $quality
     *
     * @return $this
     */
    public function save($path, $format = null, $quality = null)
    {
        if (!$format || $format == 'png32') {
            $format = 'png';
        }

        if ($format == 'original') {
            $format = $this->sourceImageFormat;
        }

        $format = strtolower($format);

        // progressive jpeg
        if ($format == 'pjpeg') {
            imageinterlace($this->resource, true);
            $format = 'jpeg';
        }

        if ($format == 'jpg') {
            $format = 'jpeg';
        }

        $functionName = 'image' . $format;
        if (!function_exists($functionName)) {
            $functionName = 'imagepng';
        }

        // always create a PNG24
        if ($format == 'png') {
            imagesavealpha($this->resource, true);
        }

        if ($functionName === 'imagejpeg' || $functionName === 'imagewebp') {
            $functionName($this->resource, $path, $quality);
        } else {
            $functionName($this->resource, $path);
        }

        return $this;
    }

    /**
     * @return bool
     */
    protected function hasAlphaChannel()
    {
        if ($this->isAlphaPossible) {
            $width = imagesx($this->resource); // Get the width of the image
            $height = imagesy($this->resource); // Get the height of the image

            // We run the image pixel by pixel and as soon as we find a transparent pixel we stop and return true.
            for ($i = 0; $i < $width; $i++) {
                for ($j = 0; $j < $height; $j++) {
                    $rgba = imagecolorat($this->resource, $i, $j);
                    if (($rgba & 0x7F000000) >> 24) {
                        return true;
                    }
                }
            }
        }

        // If we dont find any pixel the function will return false.
        return false;
    }

    protected function destroy()
    {
        if ($this->resource) {
            imagedestroy($this->resource);
        }
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return resource
     */
    protected function createImage($width, $height)
    {
        $newImg = imagecreatetruecolor($width, $height);

        imagesavealpha($newImg, true);
        imagealphablending($newImg, false);
        $trans_colour = imagecolorallocatealpha($newImg, 0, 0, 0, 127);
        imagefill($newImg, 0, 0, $trans_colour);

        return $newImg;
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return self
     */
    public function resize($width, $height)
    {
        $this->preModify();

        $newImg = $this->createImage($width, $height);
        imagecopyresampled($newImg, $this->resource, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->resource = $newImg;

        $this->setWidth($width);
        $this->setHeight($height);

        $this->postModify();

        return $this;
    }

    /**
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     *
     * @return self
     */
    public function crop($x, $y, $width, $height)
    {
        $this->preModify();

        $x = min($this->getWidth(), max(0, $x));
        $y = min($this->getHeight(), max(0, $y));
        $width = min($width, $this->getWidth() - $x);
        $height = min($height, $this->getHeight() - $y);
        $new_img = $this->createImage($width, $height);

        imagecopy($new_img, $this->resource, 0, 0, $x, $y, $width, $height);

        $this->resource = $new_img;

        $this->setWidth($width);
        $this->setHeight($height);

        $this->postModify();

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param bool $forceResize
     *
     * @return self
     */
    public function frame($width, $height, $forceResize = false)
    {
        $this->preModify();

        $this->contain($width, $height, $forceResize);

        $x = ($width - $this->getWidth()) / 2;
        $y = ($height - $this->getHeight()) / 2;

        $newImage = $this->createImage($width, $height);
        imagecopy($newImage, $this->resource, $x, $y, 0, 0, $this->getWidth(), $this->getHeight());
        $this->resource = $newImage;

        $this->setWidth($width);
        $this->setHeight($height);

        $this->postModify();

        $this->setIsAlphaPossible(true);

        return $this;
    }

    /**
     * @param string $color
     *
     * @return Adapter
     */
    public function setBackgroundColor($color)
    {
        $this->preModify();

        list($r, $g, $b) = $this->colorhex2colorarray($color);

        // just imagefill() on the existing image doesn't work, so we have to create a new image, fill it and then merge
        // the source image with the background-image together
        $newImg = imagecreatetruecolor($this->getWidth(), $this->getHeight());
        $color = imagecolorallocate($newImg, $r, $g, $b);
        imagefill($newImg, 0, 0, $color);

        imagecopy($newImg, $this->resource, 0, 0, 0, 0, $this->getWidth(), $this->getHeight());
        $this->resource = $newImg;

        $this->postModify();

        $this->setIsAlphaPossible(false);

        return $this;
    }

    /**
     * @param string $image
     * @param null|string $mode
     *
     * @return $this
     */
    public function setBackgroundImage($image, $mode = null)
    {
        $this->preModify();

        $image = ltrim($image, '/');
        $image = PIMCORE_WEB_ROOT . '/' . $image;

        if (is_file($image)) {
            $backgroundImage = imagecreatefromstring(file_get_contents($image));
            list($backgroundImageWidth, $backgroundImageHeight) = getimagesize($image);

            $newImg = $this->createImage($this->getWidth(), $this->getHeight());

            if ($mode == 'cropTopLeft') {
                imagecopyresampled($newImg, $backgroundImage, 0, 0, 0, 0, $this->getWidth(), $this->getHeight(), $this->getWidth(), $this->getHeight());
            } elseif ($mode == 'asTexture') {
                imagesettile($newImg, $backgroundImage);
                imagefilledrectangle($newImg, 0, 0, $this->getWidth(), $this->getHeight(), IMG_COLOR_TILED);
            } else {
                // default behavior (fit)
                imagecopyresampled($newImg, $backgroundImage, 0, 0, 0, 0, $this->getWidth(), $this->getHeight(), $backgroundImageWidth, $backgroundImageHeight);
            }

            imagealphablending($newImg, true);
            imagecopyresampled($newImg, $this->resource, 0, 0, 0, 0, $this->getWidth(), $this->getHeight(), $this->getWidth(), $this->getHeight());

            $this->resource = $newImg;
        }

        $this->postModify();

        return $this;
    }

    /**
     * @return self
     */
    public function grayscale()
    {
        $this->preModify();

        imagefilter($this->resource, IMG_FILTER_GRAYSCALE);

        $this->postModify();

        return $this;
    }

    /**
     * @return self
     */
    public function sepia()
    {
        $this->preModify();

        imagefilter($this->resource, IMG_FILTER_GRAYSCALE);
        imagefilter($this->resource, IMG_FILTER_COLORIZE, 100, 50, 0);

        $this->postModify();

        return $this;
    }

    /**
     * @param string $image
     * @param int $x
     * @param int $y
     * @param int $alpha
     * @param string $composite
     * @param string $origin
     *
     * @return $this
     */
    public function addOverlay($image, $x = 0, $y = 0, $alpha = 100, $composite = 'COMPOSITE_DEFAULT', $origin = 'top-left')
    {
        $this->preModify();

        $image = ltrim($image, '/');
        $image = PIMCORE_PROJECT_ROOT . '/' . $image;

        // 100 alpha is default
        if (empty($alpha)) {
            $alpha = 100;
        }
        $alpha = round($alpha / 100, 1);

        if (is_file($image)) {
            list($oWidth, $oHeight) = getimagesize($image);

            if ($origin == 'top-right') {
                $x = $this->getWidth() - $oWidth - $x;
            } elseif ($origin == 'bottom-left') {
                $y = $this->getHeight() - $oHeight - $y;
            } elseif ($origin == 'bottom-right') {
                $x = $this->getWidth() - $oWidth - $x;
                $y = $this->getHeight() - $oHeight - $y;
            } elseif ($origin == 'center') {
                $x = round($this->getWidth() / 2) - round($oWidth / 2) + $x;
                $y = round($this->getHeight() / 2) - round($oHeight / 2) + $y;
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
     *
     * @return $this|self
     */
    public function mirror($mode)
    {
        $this->preModify();

        if ($mode == 'vertical') {
            imageflip($this->resource, IMG_FLIP_VERTICAL);
        } elseif ($mode == 'horizontal') {
            imageflip($this->resource, IMG_FLIP_HORIZONTAL);
        }

        $this->postModify();

        return $this;
    }

    /**
     * @param int $angle
     *
     * @return $this|self
     */
    public function rotate($angle)
    {
        $this->preModify();
        $angle = 360 - $angle;
        $this->resource = imagerotate($this->resource, $angle, imagecolorallocatealpha($this->resource, 0, 0, 0, 127));

        $this->setWidth(imagesx($this->resource));
        $this->setHeight(imagesy($this->resource));

        $this->postModify();

        $this->setIsAlphaPossible(true);

        return $this;
    }

    protected static $supportedFormatsCache = [];

    /**
     * @inheritdoc
     */
    public function supportsFormat(string $format, bool $force = false)
    {
        if (!isset(self::$supportedFormatsCache[$format]) || $force) {
            $info = gd_info();
            $mappings = [
                'jpg' => 'JPEG Support',
                'jpeg' => 'JPEG Support',
                'pjpeg' => 'JPEG Support',
                'webp' => 'WebP Support',
                'gif' => 'GIF Create Support',
                'png' => 'PNG Support',
            ];

            if (isset($info[$mappings[$format]]) && $info[$mappings[$format]]) {
                self::$supportedFormatsCache[$format] = true;
            } else {
                self::$supportedFormatsCache[$format] = false;
            }
        }

        return self::$supportedFormatsCache[$format];
    }
}
