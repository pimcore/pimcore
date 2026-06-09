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
        $params = $resolver->resolve($this->config([
            ['method' => 'resize', 'arguments' => ['width' => 400, 'height' => 300]],
        ], 'jpeg', 82));

        self::assertSame(400, $params['width']);
        self::assertSame(300, $params['height']);
        self::assertNull($params['fit']);
        self::assertSame('jpeg', $params['format']);
        self::assertSame(82, $params['quality']);
    }

    public function testContainMapsToFitBounds(): void
    {
        $resolver = new ThumbnailTransformResolver();
        $params = $resolver->resolve($this->config([
            ['method' => 'contain', 'arguments' => ['width' => 400, 'height' => 300]],
        ]));

        self::assertSame('bounds', $params['fit']);
        self::assertSame(400, $params['width']);
        self::assertSame(300, $params['height']);
    }

    public function testCoverMapsToFitCover(): void
    {
        $resolver = new ThumbnailTransformResolver();
        $params = $resolver->resolve($this->config([
            ['method' => 'cover', 'arguments' => ['width' => 200, 'height' => 200, 'positioning' => 'center']],
        ]));

        self::assertSame('cover', $params['fit']);
        self::assertSame(200, $params['width']);
        self::assertSame(200, $params['height']);
    }

    public function testScaleByWidthSetsWidthOnly(): void
    {
        $resolver = new ThumbnailTransformResolver();
        $params = $resolver->resolve($this->config([
            ['method' => 'scaleByWidth', 'arguments' => ['width' => 800]],
        ]));

        self::assertSame(800, $params['width']);
        self::assertNull($params['height']);
    }

    public function testCropMapsToCropBox(): void
    {
        $resolver = new ThumbnailTransformResolver();
        $params = $resolver->resolve($this->config([
            ['method' => 'crop', 'arguments' => ['x' => 10, 'y' => 20, 'width' => 100, 'height' => 50]],
        ]));

        self::assertSame(['x' => 10, 'y' => 20, 'width' => 100, 'height' => 50], $params['crop']);
    }

    public function testHighResolutionSetsDpr(): void
    {
        $resolver = new ThumbnailTransformResolver();
        $params = $resolver->resolve($this->config([
            ['method' => 'resize', 'arguments' => ['width' => 400, 'height' => 300]],
        ], 'jpeg', null, 2.0));

        self::assertSame(2, $params['dpr']);
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
        $params = $resolver->resolve($this->config([
            ['method' => 'resize', 'arguments' => ['width' => 400, 'height' => 300]],
        ], 'SOURCE'));

        self::assertSame('auto', $params['format']);
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
        $params = $resolver->resolve($this->config([
            ['method' => 'scaleByHeight', 'arguments' => ['height' => 600]],
        ]));

        self::assertSame(600, $params['height']);
        self::assertNull($params['width']);
    }
}
