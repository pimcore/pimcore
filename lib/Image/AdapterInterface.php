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

namespace Pimcore\Image;

interface AdapterInterface
{
    public function getHeight(): int;

    /**
     * @return $this
     */
    public function setHeight(int $height): static;

    public function getWidth(): int;

    /**
     * @return $this
     */
    public function setWidth(int $width): static;

    /**
     * @return $this
     */
    public function resize(int $width, int $height): static;

    /**
     * @return $this
     */
    public function scaleByWidth(int $width, bool $forceResize = false): static;

    /**
     * @return $this
     */
    public function scaleByHeight(int $height, bool $forceResize = false): static;

    /**
     * @return $this
     */
    public function contain(int $width, int $height, bool $forceResize = false): static;

    /**
     * @return $this
     */
    public function cover(int $width, int $height, array|string|null $orientation = 'center', bool $forceResize = false): static;

    /**
     * @return $this
     */
    public function frame(int $width, int $height, bool $forceResize = false): static;

    /**
     * @return $this
     */
    public function trim(int $tolerance): static;

    /**
     * @return $this
     */
    public function rotate(int $angle): static;

    /**
     * @return $this
     */
    public function crop(int $x, int $y, int $width, int $height): static;

    /**
     * @return $this
     */
    public function setBackgroundColor(string $color): static;

    /**
     * @return $this
     */
    public function setBackgroundImage(string $image): static;

    /**
     * @return $this
     */
    public function roundCorners(int $width, int $height): static;

    /**
     * @param string $origin Origin of the X and Y coordinates (top-left, top-right, bottom-left, bottom-right or center)
     *
     * @return $this
     */
    public function addOverlay(mixed $image, int $x = 0, int $y = 0, int $alpha = 100, string $composite = 'COMPOSITE_DEFAULT', string $origin = 'top-left'): static;

    /**
     * @return $this
     */
    public function addOverlayFit(string $image, string $composite = 'COMPOSITE_DEFAULT'): static;

    /**
     * @return $this
     */
    public function applyMask(string $image): static;

    /**
     * @return $this
     */
    public function cropPercent(int $width, int $height, int $x, int $y): static;

    /**
     * @return $this
     */
    public function grayscale(): static;

    /**
     * @return $this
     */
    public function sepia(): static;

    /**
     * @return $this
     */
    public function sharpen(): static;

    /**
     * @return $this
     */
    public function mirror(string $mode): static;

    /**
     * @return $this
     */
    public function gaussianBlur(int $radius = 0, float $sigma = 1.0): static;

    /**
     * @return $this
     */
    public function brightnessSaturation(int $brightness = 100, int $saturation = 100, int $hue = 100): static;

    /**
     * @return $this|false
     */
    public function load(string $imagePath, array $options = []): static|false;

    /**
     * @return $this
     */
    public function save(string $path, string $format = null, int $quality = null): static;

    public function getContentOptimizedFormat(): string;

    public function supportsFormat(string $format, bool $force = false): bool;

    public function isVectorGraphic(): bool;
}
