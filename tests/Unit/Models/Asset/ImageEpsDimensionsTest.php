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

namespace Pimcore\Tests\Unit\Models\Asset;

use Codeception\Test\Unit;
use Pimcore\Model\Asset\Image;
use ReflectionMethod;

/**
 * Covers reading an EPS file's size from its DSC comments in Asset\Image::getDimensionsFromFile(),
 * so that it does not depend on ImageMagick being allowed to run a PostScript interpreter.
 */
class ImageEpsDimensionsTest extends Unit
{
    /** @var string[] */
    private array $files = [];

    protected function _after(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    public function testReadsTheBoundingBoxInPoints(): void
    {
        $file = $this->eps("%!PS-Adobe-3.0 EPSF-3.0\n%%BoundingBox: 0 0 612 792\n%%EndComments\nshowpage\n");

        self::assertSame(['width' => 612, 'height' => 792], $this->dimensions($file));
    }

    public function testPrefersTheHiResBoundingBoxAndRoundsIt(): void
    {
        $file = $this->eps(
            "%!PS-Adobe-3.0 EPSF-3.0\n%%BoundingBox: 10 20 111 71\n%%HiResBoundingBox: 10 20 110.4 70.6\n%%EndComments\n"
        );

        self::assertSame(['width' => 100, 'height' => 51], $this->dimensions($file));
    }

    public function testPrefersPhotoshopImageDataLikeTheImagickAdapter(): void
    {
        $file = $this->eps(
            "%!PS-Adobe-3.0 EPSF-3.0\n%%BoundingBox: 0 0 576 384\n%%EndComments\n%ImageData: 2400 1600 8 3 1 2400 1 \"beginimage\"\n"
        );

        self::assertSame(['width' => 2400, 'height' => 1600], $this->dimensions($file));
    }

    public function testReadsABoundingBoxDeferredToTheTrailer(): void
    {
        $file = $this->eps(
            "%!PS-Adobe-3.0 EPSF-3.0\n%%BoundingBox: (atend)\n%%EndComments\n"
            . str_repeat("0 0 moveto\n", 20000)
            . "%%Trailer\n%%BoundingBox: 0 0 300 200\n%%EOF\n"
        );

        self::assertSame(['width' => 300, 'height' => 200], $this->dimensions($file));
    }

    public function testIgnoresTheBoundingBoxOfAnEmbeddedDocument(): void
    {
        $file = $this->eps(
            "%!PS-Adobe-3.0 EPSF-3.0\n%%BoundingBox: 0 0 100 80\n%%EndComments\n%%BeginDocument: logo.eps\n%%BoundingBox: 0 0 5 5\n"
        );

        self::assertSame(['width' => 100, 'height' => 80], $this->dimensions($file));
    }

    public function testReadsTheBoxFromADosEpsBinaryHeader(): void
    {
        $postScript = "%!PS-Adobe-3.0 EPSF-3.0\r\n%%BoundingBox: 0 0 144 72\r\n%%EndComments\r\n";
        $preview = "II*\0" . str_repeat("\0", 64);
        $header = "\xC5\xD0\xD3\xC6" . pack('VVVVVV', 30, strlen($postScript), 0, 0, 30 + strlen($postScript), strlen($preview)) . "\xFF\xFF";

        $file = $this->eps($header . $postScript . $preview);

        self::assertSame(['width' => 144, 'height' => 72], $this->dimensions($file));
    }

    public function testEndsTheHeaderAtTheFirstNonCommentLineWithoutEndComments(): void
    {
        $file = $this->eps(
            "%!PS-Adobe-3.0 EPSF-3.0\n%%BoundingBox: 0 0 100 80\n/logo { 0 0 moveto } def\n%%HiResBoundingBox: 0 0 5 5\n"
        );

        self::assertSame(['width' => 100, 'height' => 80], $this->dimensions($file));
    }

    public function testReadsTheTrailerBoxAfterTheLastTrailerComment(): void
    {
        $file = $this->eps(
            "%!PS-Adobe-3.0 EPSF-3.0\n%%BoundingBox: (atend)\n%%EndComments\n"
            . "%%BeginDocument: logo.eps\n%%BoundingBox: 0 0 5 5\n%%EndDocument\n"
            . "%%Trailer\n%%BoundingBox: 0 0 300 200\n%%EOF\n"
        );

        self::assertSame(['width' => 300, 'height' => 200], $this->dimensions($file));
    }

    public function testDoesNotReadTheTrailerOutOfADosEpsPreview(): void
    {
        $postScript = "%!PS-Adobe-3.0 EPSF-3.0\r\n%%BoundingBox: (atend)\r\n%%EndComments\r\n%%Trailer\r\n%%BoundingBox: 0 0 144 72\r\n";
        // a preview that happens to contain DSC-like text after the PostScript section
        $preview = "II*\0\n%%Trailer\n%%BoundingBox: 0 0 9 9\n";
        $header = "\xC5\xD0\xD3\xC6" . pack('VVVVVV', 30, strlen($postScript), 0, 0, 30 + strlen($postScript), strlen($preview)) . "\xFF\xFF";

        $file = $this->eps($header . $postScript . $preview);

        self::assertSame(['width' => 144, 'height' => 72], $this->dimensions($file));
    }

    public function testIgnoresABoxCutOffByTheBoundedRead(): void
    {
        // the header box straddles the end of the first 64 KiB: "...640 4" | "800"
        $prefix = "%!PS-Adobe-3.0 EPSF-3.0\n";
        $box = '%%BoundingBox: 0 0 640 4800';
        $cut = strpos($box, '640 4') + 5;
        $filler = '';
        while (strlen($prefix . $filler) + $cut < 65536 - 40) {
            $filler .= "%%Comment: padding\n";
        }
        $filler .= '%%Comment: ' . str_repeat('x', 65536 - strlen($prefix . $filler) - $cut - 12) . "\n";
        $file = $this->eps($prefix . $filler . $box . "\n%%EndComments\n" . str_repeat("0 0 moveto\n", 100));

        self::assertSame(65536, strlen($prefix . $filler) + $cut);
        // the parser alone: its null hands the file to the image adapter
        self::assertNull((new ReflectionMethod(Image::class, 'getEpsDimensionsFromHeader'))->invoke(null, $file));
    }

    public function testReadsOnlyTheCommentsOfALargeFileWithoutLineFeeds(): void
    {
        // classic Mac line endings: no "\n" anywhere in the file
        $file = $this->eps("%!PS-Adobe-3.0 EPSF-3.0\r%%BoundingBox: 0 0 640 480\r%%EndComments\r");
        $handle = fopen($file, 'ab');
        $chunk = str_repeat('0123456789abcdef', 65536);
        for ($written = 0; $written < 16 * 1024 * 1024; $written += strlen($chunk)) {
            fwrite($handle, $chunk);
        }
        fclose($handle);
        unset($chunk);

        // the parser alone: before it, getimagesize() reads such a file whole (pimcore/pimcore#19645)
        $parse = new ReflectionMethod(Image::class, 'getEpsDimensionsFromHeader');

        memory_reset_peak_usage();
        $before = memory_get_peak_usage();

        self::assertSame(['width' => 640, 'height' => 480], $parse->invoke(null, $file));
        self::assertLessThan(4 * 1024 * 1024, memory_get_peak_usage() - $before);
    }

    public function testDeclinesAFileThatIsNotEpsOrDeclaresNoSize(): void
    {
        $parse = new ReflectionMethod(Image::class, 'getEpsDimensionsFromHeader');

        $png = $this->eps("\x89PNG\r\n\x1a\n" . str_repeat("\0", 32));
        $noBox = $this->eps("%!PS-Adobe-3.0 EPSF-3.0\n%%Title: no size\n%%EndComments\n");
        $emptyBox = $this->eps("%!PS-Adobe-3.0 EPSF-3.0\n%%BoundingBox: 0 0 0 0\n%%EndComments\n");
        $notEps = $this->eps("%!PS-Adobe-3.0\n%%BoundingBox: 0 0 612 792\n%%EndComments\n");

        self::assertNull($parse->invoke(null, $png));
        self::assertNull($parse->invoke(null, $noBox));
        self::assertNull($parse->invoke(null, $emptyBox));
        self::assertNull($parse->invoke(null, $notEps));
        self::assertNull($parse->invoke(null, sys_get_temp_dir() . '/pimcore-eps-test-missing-' . uniqid()));
    }

    /**
     * @return array{width: int, height: int}|null
     */
    private function dimensions(string $file): ?array
    {
        $asset = new Image();
        $asset->setFilename(basename($file));

        return $asset->getDimensionsFromFile($file);
    }

    private function eps(string $contents): string
    {
        $file = sys_get_temp_dir() . '/pimcore-eps-test-' . uniqid() . '.eps';
        file_put_contents($file, $contents);
        $this->files[] = $file;

        return $file;
    }
}
