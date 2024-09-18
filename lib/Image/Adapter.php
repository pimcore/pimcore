<?php

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

namespace Pimcore\Image;

use Pimcore\Logger;

abstract class Adapter
{
    protected int $width;

    protected int $height;

    protected bool $reinitializing = false;

    protected array $tmpFiles = [];

    protected bool $modified = false;

    protected bool $isAlphaPossible = false;

    protected bool $preserveColor = false;

    protected bool $preserveAnimation = false;

    protected bool $preserveMetaData = false;

    protected ?string $sourceImageFormat = null;

    protected mixed $resource = null;

    /**
     * @return $this
     */
    public function setHeight(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @return $this
     */
    public function setWidth(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @todo: duplication found? (pimcore/lib/Pimcore/Document/Adapter.php::removeTmpFiles)
     */
    protected function removeTmpFiles(): void
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

    public function colorhex2colorarray(string $colorhex): array
    {
        $r = hexdec(substr($colorhex, 1, 2));
        $g = hexdec(substr($colorhex, 3, 2));
        $b = hexdec(substr($colorhex, 5, 2));

        return [$r, $g, $b, 'type' => 'RGB'];
    }

    /**
     * @return $this
     */
    public function resize(int $width, int $height): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function scaleByWidth(int $width, bool $forceResize = false): static
    {
        if ($forceResize || $width <= $this->getWidth() || $this->isVectorGraphic()) {
            $height = floor(($width / $this->getWidth()) * $this->getHeight());
            $this->resize((int)max(1, $width), (int)max(1, $height));
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function scaleByHeight(int $height, bool $forceResize = false): static
    {
        if ($forceResize || $height < $this->getHeight() || $this->isVectorGraphic()) {
            $width = floor(($height / $this->getHeight()) * $this->getWidth());
            $this->resize(max(1, $width), max(1, $height));
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function contain(int $width, int $height, bool $forceResize = false): static
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
     * @return $this
     */
    public function cover(int $width, int $height, array|string|null $orientation = 'center', bool $forceResize = false): static
    {
        if (!$orientation) {
            $orientation = 'center'; // if not set (from GUI for instance) - default value in getByLegacyConfig method of Config object too
        }
        $ratio = $this->getWidth() / $this->getHeight();

        if (($width / $height) > $ratio) {
            $this->scaleByWidth($width, $forceResize);
        } else {
            $this->scaleByHeight($height, $forceResize);
        }

        if ($orientation === 'center') {
            $cropX = ($this->getWidth() - $width) / 2;
            $cropY = ($this->getHeight() - $height) / 2;
        } elseif ($orientation === 'topleft') {
            $cropX = 0;
            $cropY = 0;
        } elseif ($orientation === 'topright') {
            $cropX = $this->getWidth() - $width;
            $cropY = 0;
        } elseif ($orientation === 'bottomleft') {
            $cropX = 0;
            $cropY = $this->getHeight() - $height;
        } elseif ($orientation === 'bottomright') {
            $cropX = $this->getWidth() - $width;
            $cropY = $this->getHeight() - $height;
        } elseif ($orientation === 'centerleft') {
            $cropX = 0;
            $cropY = ($this->getHeight() - $height) / 2;
        } elseif ($orientation === 'centerright') {
            $cropX = $this->getWidth() - $width;
            $cropY = ($this->getHeight() - $height) / 2;
        } elseif ($orientation === 'topcenter') {
            $cropX = ($this->getWidth() - $width) / 2;
            $cropY = 0;
        } elseif ($orientation === 'bottomcenter') {
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
     * @return $this
     */
    public function frame(int $width, int $height, bool $forceResize = false): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function trim(int $tolerance): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function rotate(int $angle): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function crop(int $x, int $y, int $width, int $height): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function setBackgroundColor(string $color): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function setBackgroundImage(string $image): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function roundCorners(int $width, int $height): static
    {
        return $this;
    }

    /**
     * @param string $origin Origin of the X and Y coordinates (top-left, top-right, bottom-left, bottom-right or center)
     *
     * @return $this
     */
    public function addOverlay(mixed $image, int $x = 0, int $y = 0, int $alpha = 100, string $composite = 'COMPOSITE_DEFAULT', string $origin = 'top-left'): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function addOverlayFit(string $image, string $composite = 'COMPOSITE_DEFAULT'): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function applyMask(string $image): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function cropPercent(int $width, int $height, int $x, int $y): static
    {
        if ($this->isVectorGraphic()) {
            // rasterize before cropping
            $dimensions = $this->getVectorRasterDimensions();
            $this->resize($dimensions['width'], $dimensions['height']);
        }

        $originalWidth = $this->getWidth();
        $originalHeight = $this->getHeight();

        $widthPixel = (int) ceil($originalWidth * ($width / 100));
        $heightPixel = (int) ceil($originalHeight * ($height / 100));
        $xPixel = (int) ceil($originalWidth * ($x / 100));
        $yPixel = (int) ceil($originalHeight * ($y / 100));

        return $this->crop($xPixel, $yPixel, $widthPixel, $heightPixel);
    }

    /**
     * @return $this
     */
    public function grayscale(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function sepia(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function sharpen(): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function mirror(string $mode): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function gaussianBlur(int $radius = 0, float $sigma = 1.0): static
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function brightnessSaturation(int $brightness = 100, int $saturation = 100, int $hue = 100): static
    {
        return $this;
    }

    /**
     * @return $this|false
     */
    abstract public function load(string $imagePath, array $options = []): static|false;

    /**
     *
     * @return $this
     */
    abstract public function save(string $path, string $format = null, int $quality = null): static;

    abstract protected function destroy(): void;

    abstract public function getContentOptimizedFormat(): string;

    /**
     * @internal
     */
    abstract public function supportsFormat(string $format, bool $force = false): bool;

    public function preModify(): void
    {
        if ($this->getModified()) {
            $this->reinitializeImage();
        }
    }

    public function postModify(): void
    {
        $this->setModified(true);
    }

    protected function reinitializeImage(): void
    {
        $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . uniqid() . '_pimcore_image_tmp_file.png';
        $this->tmpFiles[] = $tmpFile;

        $format = 'png32';
        if ($this->isPreserveColor() || $this->isPreserveMetaData() || $this->isPreserveAnimation()) {
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

    public function isVectorGraphic(): bool
    {
        return false;
    }

    protected function getVectorRasterDimensions(): array
    {
        $targetWidth = 5000;
        $factor = $targetWidth / $this->getWidth();

        return [
            'width' => $this->getWidth() * $factor,
            'height' => $this->getHeight() * $factor,
        ];
    }

    /**
     * @return $this
     */
    public function setColorspace(string $type = 'RGB'): static
    {
        return $this;
    }

    public function setModified(bool $modified): void
    {
        $this->modified = $modified;
    }

    public function getModified(): bool
    {
        return $this->modified;
    }

    public function setIsAlphaPossible(bool $value): void
    {
        $this->isAlphaPossible = $value;
    }

    public function isPreserveColor(): bool
    {
        return $this->preserveColor;
    }

    public function setPreserveColor(bool $preserveColor): void
    {
        $this->preserveColor = $preserveColor;
    }

    public function isPreserveMetaData(): bool
    {
        return $this->preserveMetaData;
    }

    public function setPreserveMetaData(bool $preserveMetaData): void
    {
        $this->preserveMetaData = $preserveMetaData;
    }

    public function isPreserveAnimation(): bool
    {
        return $this->preserveAnimation;
    }

    public function setPreserveAnimation(bool $preserveAnimation): void
    {
        $this->preserveAnimation = $preserveAnimation;
    }

    public function getResource(): mixed
    {
        return $this->resource;
    }
}
