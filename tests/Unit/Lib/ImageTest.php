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

namespace Pimcore\Tests\Unit\Lib;

use Codeception\Test\Unit;
use Pimcore\Image;

/**
 * Covers Image::getImageSize(): getimagesize() for files of any type. For a format it
 * does not recognise, getimagesize() falls through to its XBM check, which reads the file
 * a line at a time, so a large file without line feeds was read into memory whole - a
 * 1.1 GB EPS with CR line endings exhausted a 1 GB memory_limit.
 */
class ImageTest extends Unit
{
    private const NO_LINE_FEED_SIZE = 16 * 1024 * 1024;

    /** @var string[] */
    private array $files = [];

    protected function _after(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    public function testReadsTheDimensionsOfASupportedFormat(): void
    {
        $file = $this->createFile('png');
        imagepng(imagecreatetruecolor(3, 2), $file);

        $size = Image::getImageSize($file);

        self::assertIsArray($size);
        self::assertSame(3, $size[0]);
        self::assertSame(2, $size[1]);
        self::assertSame(IMAGETYPE_PNG, $size[2]);
    }

    public function testPassesTheImageInfoThrough(): void
    {
        $file = $this->createFile('jpg');
        imagejpeg(imagecreatetruecolor(4, 4), $file);

        $size = Image::getImageSize($file, $info);

        self::assertIsArray($size);
        self::assertIsArray($info);
        self::assertArrayHasKey('APP0', $info);
    }

    public function testDoesNotReadAnUnsupportedFileWithoutLineFeedsIntoMemory(): void
    {
        // PostScript with classic Mac line endings: no "\n" anywhere in the file
        $file = $this->createFile('eps');
        $handle = fopen($file, 'wb');
        fwrite($handle, "%!PS-Adobe-3.0 EPSF-3.0\r%%BoundingBox: 0 0 100 100\r");
        $chunk = str_repeat('0123456789abcdef', 65536);
        for ($written = 0; $written < self::NO_LINE_FEED_SIZE; $written += strlen($chunk)) {
            fwrite($handle, $chunk);
        }
        fclose($handle);
        unset($chunk);

        memory_reset_peak_usage();
        $before = memory_get_peak_usage();

        self::assertFalse(Image::getImageSize($file));
        self::assertLessThan(
            self::NO_LINE_FEED_SIZE / 4,
            memory_get_peak_usage() - $before,
            'The unsupported file must not be read into memory'
        );
    }

    public function testReturnsFalseForAMissingFile(): void
    {
        self::assertFalse(Image::getImageSize(sys_get_temp_dir() . '/pimcore-image-test-missing-' . uniqid()));
    }

    private function createFile(string $extension): string
    {
        $file = sys_get_temp_dir() . '/pimcore-image-test-' . uniqid() . '.' . $extension;
        $this->files[] = $file;

        return $file;
    }
}
