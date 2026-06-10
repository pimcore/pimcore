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
use Pimcore\Cdn\FastlyImageOptimizerAdapter;
use Pimcore\Cdn\ThumbnailTransform;
use Pimcore\Tests\Support\Test\TestCase;
use RuntimeException;

class FastlyImageOptimizerAdapterTest extends TestCase
{
    private function adapter(string $baseUrl = 'https://cdn.example.com'): FastlyImageOptimizerAdapter
    {
        return new FastlyImageOptimizerAdapter($baseUrl);
    }

    public function testBuildsAbsoluteUrlWithDimensionsFitFormatQuality(): void
    {
        $url = $this->adapter()->buildUrl('/var/assets/folder/image.jpg', new ThumbnailTransform(
            width: 400, height: 300, fit: 'cover', format: 'webp', quality: 82,
        ));

        self::assertSame(
            'https://cdn.example.com/var/assets/folder/image.jpg?width=400&height=300&fit=cover&format=webp&quality=82',
            $url,
        );
    }

    public function testEncodesPathSegments(): void
    {
        $url = $this->adapter()->buildUrl('/var/assets/Car Images/Mötley.jpg', new ThumbnailTransform(100));

        self::assertStringStartsWith('https://cdn.example.com/var/assets/Car%20Images/M', $url);
        self::assertStringContainsString('?width=100', $url);
    }

    public function testContainMapsToFitBounds(): void
    {
        $url = $this->adapter()->buildUrl('/var/assets/x.jpg', new ThumbnailTransform(
            50, 50, 'bounds',
        ));

        self::assertStringContainsString('fit=bounds', $url);
    }

    public function testAutoFormatMapsToAutoWebp(): void
    {
        $url = $this->adapter()->buildUrl('/var/assets/x.jpg', new ThumbnailTransform(width: 50, format: 'auto'));

        self::assertStringContainsString('auto=webp', $url);
        self::assertStringNotContainsString('format=auto', $url);
    }

    public function testDprEmitted(): void
    {
        $url = $this->adapter()->buildUrl('/var/assets/x.jpg', new ThumbnailTransform(width: 400, dpr: 2));

        self::assertStringContainsString('dpr=2', $url);
    }

    public function testCropEmittedAsWidthHeightXY(): void
    {
        $url = $this->adapter()->buildUrl('/var/assets/x.jpg', new ThumbnailTransform(
            crop: new CropRegion(10, 20, 100, 50),
        ));

        self::assertStringContainsString('crop=100,50,x10,y20', $url);
    }

    public function testEmptyBaseUrlThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/CDN_BASE_URL/');

        $this->adapter('')->buildUrl('/var/assets/x.jpg', new ThumbnailTransform(100));
    }
}
