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

namespace Pimcore\Tests\Unit\Cdn;

use Pimcore\Cdn\CropRegion;
use Pimcore\Cdn\ThumbnailTransformResolver;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * NOTE: Config is a final class and cannot be mocked with PHPUnit's mock builder.
 * Real Config objects are constructed via setters instead.
 */
class ThumbnailTransformResolverTest extends TestCase
{
    private function config(array $items, string $format = 'SOURCE', ?int $quality = null, ?float $highRes = null): Config
    {
        $config = new Config();
        $config->setItems($items);
        $config->setFormat($format);
        if ($quality !== null) {
            $config->setQuality($quality);
        }
        $config->setHighResolution($highRes);

        return $config;
    }

    public function testResizeMapsToWidthHeightExact(): void
    {
        $resolver = new ThumbnailTransformResolver();
        $t = $resolver->resolve($this->config([
            ['method' => 'resize', 'arguments' => ['width' => 400, 'height' => 300]],
        ], 'jpeg', 82));

        self::assertSame(400, $t->width);
        self::assertSame(300, $t->height);
        self::assertNull($t->fit);
        self::assertSame('jpeg', $t->format);
        self::assertSame(82, $t->quality);
    }

    public function testContainMapsToFitBounds(): void
    {
        $resolver = new ThumbnailTransformResolver();
        $t = $resolver->resolve($this->config([
            ['method' => 'contain', 'arguments' => ['width' => 400, 'height' => 300]],
        ]));

        self::assertSame('bounds', $t->fit);
        self::assertSame(400, $t->width);
        self::assertSame(300, $t->height);
    }

    public function testCoverMapsToFitCover(): void
    {
        $resolver = new ThumbnailTransformResolver();
        $t = $resolver->resolve($this->config([
            ['method' => 'cover', 'arguments' => ['width' => 200, 'height' => 200, 'positioning' => 'center']],
        ]));

        self::assertSame('cover', $t->fit);
        self::assertSame(200, $t->width);
        self::assertSame(200, $t->height);
    }

    public function testScaleByWidthSetsWidthOnly(): void
    {
        $resolver = new ThumbnailTransformResolver();
        $t = $resolver->resolve($this->config([
            ['method' => 'scaleByWidth', 'arguments' => ['width' => 800]],
        ]));

        self::assertSame(800, $t->width);
        self::assertNull($t->height);
    }

    public function testCropMapsToCropBox(): void
    {
        $resolver = new ThumbnailTransformResolver();
        $t = $resolver->resolve($this->config([
            ['method' => 'crop', 'arguments' => ['x' => 10, 'y' => 20, 'width' => 100, 'height' => 50]],
        ]));

        self::assertEquals(new CropRegion(10, 20, 100, 50), $t->crop);
    }

    public function testHighResolutionSetsDpr(): void
    {
        $resolver = new ThumbnailTransformResolver();
        $t = $resolver->resolve($this->config([
            ['method' => 'resize', 'arguments' => ['width' => 400, 'height' => 300]],
        ], 'jpeg', null, 2.0));

        self::assertSame(2, $t->dpr);
    }

    public function testHighResolutionOneXSetsNoDpr(): void
    {
        // A 1x high-resolution factor is a no-op and must translate cleanly (dpr omitted).
        $resolver = new ThumbnailTransformResolver();
        $t = $resolver->resolve($this->config([
            ['method' => 'resize', 'arguments' => ['width' => 400, 'height' => 300]],
        ], 'jpeg', null, 1.0));

        self::assertNotNull($t);
        self::assertNull($t->dpr);
    }

    public function testHighResolutionThreeXReturnsNull(): void
    {
        // The transform only carries an integer 2x dpr; a 3x factor cannot be reproduced
        // faithfully, so the config must fall back to Pimcore generation.
        $resolver = new ThumbnailTransformResolver();

        self::assertNull($resolver->resolve($this->config([
            ['method' => 'resize', 'arguments' => ['width' => 400, 'height' => 300]],
        ], 'jpeg', null, 3.0)));
    }

    public function testHighResolutionFractionalReturnsNull(): void
    {
        // A fractional factor (1.5x) would otherwise be silently dropped to 1x — bail instead.
        $resolver = new ThumbnailTransformResolver();

        self::assertNull($resolver->resolve($this->config([
            ['method' => 'resize', 'arguments' => ['width' => 400, 'height' => 300]],
        ], 'jpeg', null, 1.5)));
    }

    public function testCoverWithoutPositioningMapsToFitCover(): void
    {
        // No positioning argument means Pimcore's default (center), which maps cleanly to cover.
        $resolver = new ThumbnailTransformResolver();
        $t = $resolver->resolve($this->config([
            ['method' => 'cover', 'arguments' => ['width' => 200, 'height' => 200]],
        ]));

        self::assertNotNull($t);
        self::assertSame('cover', $t->fit);
    }

    public function testCoverWithNonCenterPositioningReturnsNull(): void
    {
        // Pimcore's cover crop honors `positioning` (topleft/topright/...); the CDN cover fit
        // is center-only, so any non-center positioning must fall back to Pimcore.
        $resolver = new ThumbnailTransformResolver();

        self::assertNull($resolver->resolve($this->config([
            ['method' => 'cover', 'arguments' => ['width' => 200, 'height' => 200, 'positioning' => 'topleft']],
        ])));
    }

    public function testUnsupportedTransformReturnsNull(): void
    {
        $resolver = new ThumbnailTransformResolver();

        self::assertNull($resolver->resolve($this->config([
            ['method' => 'rotate', 'arguments' => ['angle' => 90]],
        ])));
    }

    public function testCropPercentReturnsNullInV1(): void
    {
        $resolver = new ThumbnailTransformResolver();

        self::assertNull($resolver->resolve($this->config([
            ['method' => 'cropPercent', 'arguments' => ['width' => 50, 'height' => 50, 'x' => 0, 'y' => 0]],
        ])));
    }

    public function testFormatSourceMapsToAuto(): void
    {
        $resolver = new ThumbnailTransformResolver();
        $t = $resolver->resolve($this->config([
            ['method' => 'resize', 'arguments' => ['width' => 400, 'height' => 300]],
        ], 'SOURCE'));

        self::assertSame('auto', $t->format);
    }

    public function testMixedSupportedAndUnsupportedMethodReturnsNull(): void
    {
        $resolver = new ThumbnailTransformResolver();

        self::assertNull($resolver->resolve($this->config([
            ['method' => 'resize', 'arguments' => ['width' => 400, 'height' => 300]],
            ['method' => 'rotate', 'arguments' => ['angle' => 90]],
        ])));
    }

    public function testScaleByHeightSetsHeightOnly(): void
    {
        $resolver = new ThumbnailTransformResolver();
        $t = $resolver->resolve($this->config([
            ['method' => 'scaleByHeight', 'arguments' => ['height' => 600]],
        ]));

        self::assertSame(600, $t->height);
        self::assertNull($t->width);
    }
}
