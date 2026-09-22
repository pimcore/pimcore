<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Image\Adapter;

use Pimcore\Image\Adapter;

/**
 * Dimension-only adapter used to run thumbnail transformations without image I/O.
 *
 * @internal
 */
final class Dimension extends Adapter
{
    /**
     * Every Processor mapping must be explicitly listed before it can be evaluated without image I/O.
     * New transformations therefore fail closed.
     *
     * @var list<string>
     */
    private const RELIABLY_MODELLED_TRANSFORMATIONS = [
        'resize',
        'scaleByWidth',
        'scaleByHeight',
        'contain',
        'cover',
        'frame',
        'trim',
        'rotate',
        'crop',
        'setBackgroundColor',
        'roundCorners',
        'setBackgroundImage',
        'addOverlay',
        'addOverlayFit',
        'applyMask',
        'cropPercent',
        'grayscale',
        'sepia',
        'sharpen',
        'gaussianBlur',
        'brightnessSaturation',
        'mirror',
    ];

    private bool $reliable = true;

    public function __construct(
        int $width,
        int $height,
        private readonly bool $vectorGraphic,
        private readonly bool $passThroughLogical = false
    ) {
        $this->setWidth($width);
        $this->setHeight($height);
    }

    public function isReliable(): bool
    {
        return $this->reliable;
    }

    public static function supportsReliableTransformation(string $method): bool
    {
        return in_array($method, self::RELIABLY_MODELLED_TRANSFORMATIONS, true);
    }

    private function markUnreliable(): void
    {
        $this->reliable = false;
    }

    public function resize(int $width, int $height): static
    {
        if ($width <= 0 || $height <= 0) {
            $this->markUnreliable();

            return $this;
        }

        $this->setWidth($width);
        $this->setHeight($height);

        return $this;
    }

    public function scaleByWidth(int $width, bool $forceResize = false): static
    {
        if (!$this->passThroughLogical) {
            return parent::scaleByWidth($width, $forceResize);
        }

        if ($width <= 0) {
            $this->markUnreliable();

            return $this;
        }

        // Pass-through output never invokes an image adapter.
        // Retain the legacy estimator's logical HTML dimensions instead of generated-output floor().
        if ($forceResize || $width <= $this->getWidth() || $this->isVectorGraphic()) {
            $height = round(($width / $this->getWidth()) * $this->getHeight(), 0);
            $this->resize($width, (int) max(1, $height));
        }

        return $this;
    }

    public function scaleByHeight(int $height, bool $forceResize = false): static
    {
        if (!$this->passThroughLogical) {
            return parent::scaleByHeight($height, $forceResize);
        }

        if ($height <= 0) {
            $this->markUnreliable();

            return $this;
        }

        // Match Config's legacy logical estimator.
        // Generated thumbnails continue to use Adapter's floor-based physical-output mathematics.
        if ($forceResize || $height < $this->getHeight() || $this->isVectorGraphic()) {
            $width = round(($height / $this->getHeight()) * $this->getWidth(), 0);
            $this->resize((int) max(1, $width), $height);
        }

        return $this;
    }

    public function crop(int $x, int $y, int $width, int $height): static
    {
        if ($this->passThroughLogical) {
            // Processor returns the source before executing the crop.
            // Preserve Config's established logical target for HTML attributes.
            return $this->resize($width, $height);
        }

        // GD clamps out-of-bounds crops while Imagick retains the requested canvas.
        // Only an in-bounds crop has adapter-independent dimensions.
        if ($x < 0 || $y < 0 || $width <= 0 || $height <= 0
            || $x + $width > $this->getWidth()
            || $y + $height > $this->getHeight()) {
            $this->markUnreliable();

            return $this;
        }

        $this->resize($width, $height);

        return $this;
    }

    public function cover(
        int $width,
        int $height,
        array|string|null $orientation = 'center',
        bool $forceResize = false
    ): static {
        if ($this->passThroughLogical) {
            // Legacy Config estimation exposed the configured cover target.
            // The output route returns the original asset, so crop geometry, force resize and positioning never reach a runtime adapter.
            return $this->resize($width, $height);
        }

        return parent::cover($width, $height, $orientation, $forceResize);
    }

    public function frame(int $width, int $height, bool $forceResize = false): static
    {
        if ($width <= 0 || $height <= 0) {
            $this->markUnreliable();

            return $this;
        }

        $this->contain($width, $height, $forceResize);
        $this->resize($width, $height);

        return $this;
    }

    public function trim(int $tolerance): static
    {
        $this->markUnreliable();

        return $this;
    }

    public function rotate(int $angle): static
    {
        $this->markUnreliable();

        return $this;
    }

    public function cropPercent(int $width, int $height, int $x, int $y): static
    {
        // Runtime vector rasterization dimensions depend on the loaded adapter.
        // Pass-through output performs no rasterization and retains Pimcore's configured logical crop dimensions for HTML.
        if ($this->passThroughLogical) {
            $originalWidth = $this->getWidth();
            $originalHeight = $this->getHeight();

            return $this->resize(
                (int) ceil($originalWidth * ($width / 100)),
                (int) ceil($originalHeight * ($height / 100))
            );
        }

        if ($this->isVectorGraphic()) {
            $this->markUnreliable();

            return $this;
        }

        parent::cropPercent($width, $height, $x, $y);

        return $this;
    }

    public function setBackgroundImage(string $image, ?string $mode = null): static
    {
        // GD keeps the foreground canvas size for cropTopLeft, while Imagick replaces it with the cropped background resource.
        // Without loading the background, no adapter-independent dimension can be guaranteed.
        if ($mode === 'cropTopLeft' && !$this->passThroughLogical) {
            $this->markUnreliable();
        }

        return $this;
    }

    public function isVectorGraphic(): bool
    {
        return $this->vectorGraphic;
    }

    public function load(string $imagePath, array $options = []): static|false
    {
        return false;
    }

    public function save(string $path, ?string $format = null, ?int $quality = null): static
    {
        return $this;
    }

    protected function destroy(): void
    {
    }

    public function getContentOptimizedFormat(): string
    {
        return '';
    }

    public function supportsFormat(string $format, bool $force = false): bool
    {
        return false;
    }
}
