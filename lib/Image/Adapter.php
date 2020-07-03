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

namespace Pimcore\Image;

use Pimcore\Logger;

abstract class Adapter
{
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
    protected $tmpFiles = [];

    /**
     * @var bool
     */
    protected $modified = false;

    /**
     * @var bool
     */
    protected $isAlphaPossible = false;

    /**
     * @var bool
     */
    protected $preserveColor = false;

    /**
     * @var bool
     */
    protected $preserveMetaData = false;

    /**
     * @var string
     */
    protected $sourceImageFormat;

    /**
     * @var mixed
     */
    protected $resource;

    /**
     * @param int $height
     *
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
     * @param int $width
     *
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
     * @todo: duplication found? (pimcore/lib/Pimcore/Document/Adapter.php::removeTmpFiles)
     */
    protected function removeTmpFiles()
    {
        // remove tmp files
        if (!empty($this->tmpFiles)) {
            foreach ($this->tmpFiles as $tmpFile) {
                if (file_exists($tmpFile)) {
                    unlink($tmpFile);
                }
            }
        }
    }

    /**
     * @param string $colorhex
     *
     * @return array
     */
    public function colorhex2colorarray($colorhex)
    {
        $r = hexdec(substr($colorhex, 1, 2));
        $g = hexdec(substr($colorhex, 3, 2));
        $b = hexdec(substr($colorhex, 5, 2));

        return [$r, $g, $b, 'type' => 'RGB'];
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return self
     */
    public function resize($width, $height)
    {
        return $this;
    }

    /**
     * @param int $width
     * @param bool $forceResize
     *
     * @return self
     */
    public function scaleByWidth($width, $forceResize = false)
    {
        if ($forceResize || $width <= $this->getWidth() || $this->isVectorGraphic()) {
            $height = round(($width / $this->getWidth()) * $this->getHeight(), 0);
            $this->resize(max(1, $width), max(1, $height));
        }

        return $this;
    }

    /**
     * @param int $height
     * @param bool $forceResize
     *
     * @return self
     */
    public function scaleByHeight($height, $forceResize = false)
    {
        if ($forceResize || $height < $this->getHeight() || $this->isVectorGraphic()) {
            $width = round(($height / $this->getHeight()) * $this->getWidth(), 0);
            $this->resize(max(1, $width), max(1, $height));
        }

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param bool $forceResize
     *
     * @return self
     */
    public function contain($width, $height, $forceResize = false)
    {
        $x = $this->getWidth() / $width;
        $y = $this->getHeight() / $height;
        if ((!$forceResize) && $x <= 1 && $y <= 1 && !$this->isVectorGraphic()) {
            return $this;
        } elseif ($x > $y) {
            $this->scaleByWidth($width, $forceResize);
        } else {
            $this->scaleByHeight($height, $forceResize);
        }

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param string $orientation
     * @param bool $forceResize
     *
     * @return self
     */
    public function cover($width, $height, $orientation = 'center', $forceResize = false)
    {
        if (empty($orientation)) {
            $orientation = 'center'; // if not set (from GUI for instance) - default value in getByLegacyConfig method of Config object too
        }
        $ratio = $this->getWidth() / $this->getHeight();

        if (($width / $height) > $ratio) {
            $this->scaleByWidth($width, $forceResize);
        } else {
            $this->scaleByHeight($height, $forceResize);
        }

        if ($orientation == 'center') {
            $cropX = ($this->getWidth() - $width) / 2;
            $cropY = ($this->getHeight() - $height) / 2;
        } elseif ($orientation == 'topleft') {
            $cropX = 0;
            $cropY = 0;
        } elseif ($orientation == 'topright') {
            $cropX = $this->getWidth() - $width;
            $cropY = 0;
        } elseif ($orientation == 'bottomleft') {
            $cropX = 0;
            $cropY = $this->getHeight() - $height;
        } elseif ($orientation == 'bottomright') {
            $cropX = $this->getWidth() - $width;
            $cropY = $this->getHeight() - $height;
        } elseif ($orientation == 'centerleft') {
            $cropX = 0;
            $cropY = ($this->getHeight() - $height) / 2;
        } elseif ($orientation == 'centerright') {
            $cropX = $this->getWidth() - $width;
            $cropY = ($this->getHeight() - $height) / 2;
        } elseif ($orientation == 'topcenter') {
            $cropX = ($this->getWidth() - $width) / 2;
            $cropY = 0;
        } elseif ($orientation == 'bottomcenter') {
            $cropX = ($this->getWidth() - $width) / 2;
            $cropY = $this->getHeight() - $height;
        } elseif (is_array($orientation) && isset($orientation['x'])) {
            // focal point given in percentage values
            $focalPointXCoordinate = $orientation['x'] / 100 * $this->getWidth();
            $focalPointYCoordinate = $orientation['y'] / 100 * $this->getHeight();

            $cropY = $focalPointYCoordinate - ($height / 2);
            $cropY = min($cropY, $this->getHeight() - $height);
            $cropY = max($cropY, 0);

            $cropX = $focalPointXCoordinate - ($width / 2);
            $cropX = min($cropX, $this->getWidth() - $width);
            $cropX = max($cropX, 0);
        } else {
            $cropX = null;
            $cropY = null;
        }

        if ($cropX !== null && $cropY !== null) {
            $this->crop($cropX, $cropY, $width, $height);
        } else {
            Logger::error('Cropping not processed, because X or Y is not defined or null, proceeding with next step');
        }

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param bool $forceResize
     *
     * @return $this
     */
    public function frame($width, $height, $forceResize = false)
    {
        return $this;
    }

    /**
     * @param int $tolerance
     *
     * @return self
     */
    public function trim($tolerance)
    {
        return $this;
    }

    /**
     * @param int $angle
     *
     * @return $this
     */
    public function rotate($angle)
    {
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
        return $this;
    }

    /**
     * @param string $color
     *
     * @return self
     */
    public function setBackgroundColor($color)
    {
        return $this;
    }

    /**
     * @param string $image
     *
     * @return self
     */
    public function setBackgroundImage($image)
    {
        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return $this
     */
    public function roundCorners($width, $height)
    {
        return $this;
    }

    /**
     * @param string $image
     * @param int $x
     * @param int $y
     * @param int $alpha
     * @param string $composite
     * @param string $origin Origin of the X and Y coordinates (top-left, top-right, bottom-left, bottom-right or center)
     *
     * @return self
     */
    public function addOverlay($image, $x = 0, $y = 0, $alpha = 100, $composite = 'COMPOSITE_DEFAULT', $origin = 'top-left')
    {
        return $this;
    }

    /**
     * @param string $image
     * @param string $composite
     *
     * @return $this
     */
    public function addOverlayFit($image, $composite = 'COMPOSITE_DEFAULT')
    {
        return $this;
    }

    /**
     * @param string $image
     *
     * @return self
     */
    public function applyMask($image)
    {
        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param int $x
     * @param int $y
     *
     * @return self
     */
    public function cropPercent($width, $height, $x, $y)
    {
        if ($this->isVectorGraphic()) {
            // rasterize before cropping
            $dimensions = $this->getVectorRasterDimensions();
            $this->resize($dimensions['width'], $dimensions['height']);
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
    public function grayscale()
    {
        return $this;
    }

    /**
     * @return self
     */
    public function sepia()
    {
        return $this;
    }

    /**
     * @return self
     */
    public function sharpen()
    {
        return $this;
    }

    /**
     * @param string $mode
     *
     * @return self
     */
    public function mirror($mode)
    {
        return $this;
    }

    /**
     * @param int $radius
     * @param float $sigma
     *
     * @return $this|Adapter
     */
    public function gaussianBlur($radius = 0, $sigma = 1.0)
    {
        return $this;
    }

    /**
     * @param int $brightness
     * @param int $saturation
     * @param int $hue
     *
     * @return $this
     */
    public function brightnessSaturation($brightness = 100, $saturation = 100, $hue = 100)
    {
        return $this;
    }

    /**
     * @abstract
     *
     * @param string $imagePath
     * @param array $options
     *
     * @return self
     */
    abstract public function load($imagePath, $options = []);

    /**
     * @param string $path
     * @param string|null $format
     * @param int|null $quality
     *
     * @return $this
     */
    abstract public function save($path, $format = null, $quality = null);

    /**
     * @abstract
     */
    abstract protected function destroy();

    /**
     * @return string
     */
    abstract public function getContentOptimizedFormat();

    /**
     * @internal
     *
     * @param string $format
     * @param bool $force
     *
     * @return mixed
     */
    abstract public function supportsFormat(string $format, bool $force = false);

    public function preModify()
    {
        if ($this->getModified()) {
            $this->reinitializeImage();
        }
    }

    public function postModify()
    {
        $this->setModified(true);
    }

    protected function reinitializeImage()
    {
        $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . uniqid() . '_pimcore_image_tmp_file.png';
        $this->tmpFiles[] = $tmpFile;

        $format = 'png32';
        if ($this->isPreserveColor() || $this->isPreserveMetaData()) {
            $format = 'original';
        }

        $this->reinitializing = true;
        $this->save($tmpFile, $format);
        $this->destroy();
        $this->load($tmpFile);
        $this->reinitializing = false;

        $this->modified = false;
    }

    public function __destruct()
    {
        $this->destroy();
        $this->removeTmpFiles();
    }

    /**
     * @return bool
     */
    public function isVectorGraphic()
    {
        return false;
    }

    /**
     * @return array
     */
    public function getVectorRasterDimensions()
    {
        $targetWidth = 5000;
        $factor = $targetWidth / $this->getWidth();

        return [
            'width' => $this->getWidth() * $factor,
            'height' => $this->getHeight() * $factor,
        ];
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setColorspace($type = 'RGB')
    {
        return $this;
    }

    /**
     * @param bool $modified
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    }

    /**
     * @return bool
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @param bool $value
     */
    public function setIsAlphaPossible($value)
    {
        $this->isAlphaPossible = $value;
    }

    /**
     * @return bool
     */
    public function isPreserveColor()
    {
        return $this->preserveColor;
    }

    /**
     * @param bool $preserveColor
     */
    public function setPreserveColor($preserveColor)
    {
        $this->preserveColor = $preserveColor;
    }

    /**
     * @return bool
     */
    public function isPreserveMetaData()
    {
        return $this->preserveMetaData;
    }

    /**
     * @param bool $preserveMetaData
     */
    public function setPreserveMetaData($preserveMetaData)
    {
        $this->preserveMetaData = $preserveMetaData;
    }

    /**
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }
}
