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

namespace Pimcore\Tests\Unit\Models\Asset\Image\Thumbnail;

use Pimcore\Model\Asset\Image\Thumbnail\Processor;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionMethod;

/**
 * Regression tests for the source-format detection of SOURCE/AUTO and PRINT thumbnails.
 *
 * The format is derived from the asset's file extension, which is not normalized:
 * photo.JPG used to miss the lowercase mapping/allow-list and silently fell back to PNG
 * (a .GIF lost its animation the same way).
 */
class ProcessorAllowedFormatTest extends TestCase
{
    private const SOURCE_ALLOWED = ['pjpeg', 'jpeg', 'gif', 'png'];

    private const PRINT_ALLOWED = ['svg', 'jpeg', 'png', 'tiff'];

    private function getAllowedFormat(string $format, array $allowed): string
    {
        $method = new ReflectionMethod(Processor::class, 'getAllowedFormat');

        return $method->invoke(null, $format, $allowed, 'png');
    }

    /**
     * @return array<string, array{string, array<string>, string}>
     */
    public static function extensionProvider(): array
    {
        return [
            'source jpg' => ['jpg', self::SOURCE_ALLOWED, 'jpeg'],
            'source JPG' => ['JPG', self::SOURCE_ALLOWED, 'jpeg'],
            'source Jpeg' => ['Jpeg', self::SOURCE_ALLOWED, 'jpeg'],
            'source GIF' => ['GIF', self::SOURCE_ALLOWED, 'gif'],
            'source PNG' => ['PNG', self::SOURCE_ALLOWED, 'png'],
            'source TIF falls back' => ['TIF', self::SOURCE_ALLOWED, 'png'],
            'print TIF' => ['TIF', self::PRINT_ALLOWED, 'tiff'],
            'print TIFF' => ['TIFF', self::PRINT_ALLOWED, 'tiff'],
            'print JPG' => ['JPG', self::PRINT_ALLOWED, 'jpeg'],
            'print SVG' => ['SVG', self::PRINT_ALLOWED, 'svg'],
            'print GIF falls back' => ['GIF', self::PRINT_ALLOWED, 'png'],
        ];
    }

    /**
     * @dataProvider extensionProvider
     */
    public function testFormatDetectionIsCaseInsensitive(string $extension, array $allowed, string $expected): void
    {
        self::assertSame($expected, $this->getAllowedFormat($extension, $allowed));
    }
}
