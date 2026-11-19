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

namespace Pimcore\Tests\Unit\Image\Adapter;

use GdImage;
use PHPUnit\Framework\TestCase;
use Pimcore\Image\Adapter;
use Pimcore\Image\Adapter\GD;
use Pimcore\Image\Adapter\Imagick;

/**
 * Regression coverage for background-image path confinement in the raster adapters.
 *
 * setBackgroundImage() takes its path argument from thumbnail transformation configuration,
 * which can originate from a sandboxed template. The resolved file must stay below
 * PIMCORE_WEB_ROOT no matter how many '../' segments the argument contains, while a path that
 * genuinely stays inside the web root must keep working unchanged.
 */
final class BackgroundImagePathConfinementTest extends TestCase
{
    private const CANVAS_SIZE = 10;

    private const PUBLIC_COLOR = [0, 200, 0];

    private const OUTSIDE_COLOR = [200, 0, 0];

    /** @var list<string> */
    private array $cleanupFiles = [];

    /** @var list<string> */
    private array $cleanupDirs = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The gd extension is not available.');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        foreach (array_reverse($this->cleanupDirs) as $dir) {
            if (is_dir($dir)) {
                @rmdir($dir);
            }
        }

        $this->cleanupFiles = [];
        $this->cleanupDirs = [];

        parent::tearDown();
    }

    public function testGdDoesNotApplyABackgroundImageFromOutsideTheWebRoot(): void
    {
        $fixture = $this->createFixturePair();

        $adapter = new GD();
        $adapter->load($this->createCanvasFixture());
        $adapter->setBackgroundImage('../' . $fixture['outsideRelative'], 'cropTopLeft');

        $this->assertColorNotPresent(self::OUTSIDE_COLOR, $this->saveAsPng($adapter));
    }

    public function testGdStillAppliesABackgroundImageFromInsideTheWebRoot(): void
    {
        $fixture = $this->createFixturePair();

        $adapter = new GD();
        $adapter->load($this->createCanvasFixture());
        $adapter->setBackgroundImage($fixture['insideRelative'], 'cropTopLeft');

        $this->assertColorPresent(self::PUBLIC_COLOR, $this->saveAsPng($adapter));
    }

    public function testImagickDoesNotApplyABackgroundImageFromOutsideTheWebRoot(): void
    {
        $this->requireImagick();

        $fixture = $this->createFixturePair();

        $adapter = new Imagick();
        $adapter->load($this->createCanvasFixture());
        $adapter->setBackgroundImage('../' . $fixture['outsideRelative'], 'cropTopLeft');

        $this->assertColorNotPresent(self::OUTSIDE_COLOR, $this->saveAsPng($adapter));
    }

    public function testImagickStillAppliesABackgroundImageFromInsideTheWebRoot(): void
    {
        $this->requireImagick();

        $fixture = $this->createFixturePair();

        $adapter = new Imagick();
        $adapter->load($this->createCanvasFixture());
        $adapter->setBackgroundImage($fixture['insideRelative'], 'cropTopLeft');

        $this->assertColorPresent(self::PUBLIC_COLOR, $this->saveAsPng($adapter));
    }

    private function requireImagick(): void
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('The imagick extension is not available.');
        }
    }

    /**
     * Creates two same-named fixture images of distinct colors: one inside PIMCORE_WEB_ROOT
     * (reachable legitimately), and one in a sibling directory one level above it (reachable
     * only through a path that escapes the web root).
     *
     * @return array{insideRelative: string, outsideRelative: string}
     */
    private function createFixturePair(): array
    {
        $filename = 'confinement-fixture-' . uniqid() . '.png';

        $insidePath = PIMCORE_WEB_ROOT . '/' . $filename;
        $this->writeSolidPng($insidePath, self::PUBLIC_COLOR);
        $this->cleanupFiles[] = $insidePath;

        $outsideDirName = 'confinement-sibling-' . uniqid();
        $outsideDir = dirname(PIMCORE_WEB_ROOT) . '/' . $outsideDirName;
        mkdir($outsideDir);
        $this->cleanupDirs[] = $outsideDir;

        $outsidePath = $outsideDir . '/' . $filename;
        $this->writeSolidPng($outsidePath, self::OUTSIDE_COLOR);
        $this->cleanupFiles[] = $outsidePath;

        return [
            'insideRelative' => $filename,
            'outsideRelative' => $outsideDirName . '/' . $filename,
        ];
    }

    /**
     * @param int[] $rgb
     */
    private function writeSolidPng(string $path, array $rgb): void
    {
        $image = imagecreatetruecolor(self::CANVAS_SIZE, self::CANVAS_SIZE);
        $color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        imagefill($image, 0, 0, $color);
        imagepng($image, $path);
        imagedestroy($image);
    }

    private function createCanvasFixture(): string
    {
        $path = $this->temporaryPath('.png');
        $this->writeSolidPng($path, [255, 255, 255]);

        return $path;
    }

    private function saveAsPng(Adapter $adapter): string
    {
        $path = $this->temporaryPath('.png');
        $adapter->save($path, 'png');

        return $path;
    }

    private function temporaryPath(string $extension): string
    {
        $base = tempnam(sys_get_temp_dir(), 'confinement-');
        $this->assertNotFalse($base);
        $path = $base . $extension;
        rename($base, $path);
        $this->cleanupFiles[] = $path;

        return $path;
    }

    /**
     * @param int[] $rgb
     */
    private function assertColorPresent(array $rgb, string $path): void
    {
        $this->assertTrue(
            $this->imageContainsColor($rgb, $path),
            'Expected the configured background image to be visible in the result.'
        );
    }

    /**
     * @param int[] $rgb
     */
    private function assertColorNotPresent(array $rgb, string $path): void
    {
        $this->assertFalse(
            $this->imageContainsColor($rgb, $path),
            'A file outside of the web root must never be read into the result.'
        );
    }

    /**
     * @param int[] $expected
     */
    private function imageContainsColor(array $expected, string $path): bool
    {
        $content = file_get_contents($path);
        $this->assertNotFalse($content);

        $image = imagecreatefromstring($content);
        $this->assertInstanceOf(GdImage::class, $image);

        $width = imagesx($image);
        $height = imagesy($image);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                if (abs($r - $expected[0]) < 10 && abs($g - $expected[1]) < 10 && abs($b - $expected[2]) < 10) {
                    imagedestroy($image);

                    return true;
                }
            }
        }

        imagedestroy($image);

        return false;
    }
}
