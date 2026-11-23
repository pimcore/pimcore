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
use ImagickException;
use PHPUnit\Framework\TestCase;
use Pimcore\Image\Adapter;
use Pimcore\Image\Adapter\GD;
use Pimcore\Image\Adapter\Imagick;

/**
 * File-backed thumbnail transformations (background image, overlay, mask) take their path from the
 * thumbnail configuration. The file has to be located in the public web root, no matter how many
 * parent directory segments the path contains, while paths that stay inside it - including files
 * reached through a symlinked directory, as used for bundle assets - must keep working.
 */
final class ImagePathConfinementTest extends TestCase
{
    private const CANVAS_SIZE = 10;

    private const INSIDE_COLOR = [0, 200, 0];

    private const OUTSIDE_COLOR = [200, 0, 0];

    private const LINKED_COLOR = [0, 0, 200];

    private string $siblingDir = '';

    private string $siblingName = '';

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

        if (dirname(PIMCORE_WEB_ROOT) !== rtrim(PIMCORE_PROJECT_ROOT, '/')) {
            $this->markTestSkipped('The web root is not a direct child of the project root.');
        }

        // PIMCORE_WEB_ROOT (the "public" directory) is gitignored and only materialized by an
        // asset-install step that some CI jobs skip, so create it if it is missing.
        if (!is_dir(PIMCORE_WEB_ROOT)) {
            mkdir(PIMCORE_WEB_ROOT, 0775, true);
            $this->cleanupDirs[] = PIMCORE_WEB_ROOT;
        }

        // a sibling of the web root, i.e. inside the project but outside of the web root
        $this->siblingName = 'confinement-sibling-' . uniqid();
        $this->siblingDir = PIMCORE_PROJECT_ROOT . '/' . $this->siblingName;
        mkdir($this->siblingDir);
        $this->cleanupDirs[] = $this->siblingDir;
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanupFiles as $file) {
            if (is_link($file) || is_file($file)) {
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

    public function testBackgroundImageOutsideTheWebRootIsNotApplied(): void
    {
        $filename = $this->createFixture($this->siblingDir, self::OUTSIDE_COLOR);
        $path = '../' . $this->siblingName . '/' . $filename;
        $this->assertFileExists(PIMCORE_WEB_ROOT . '/' . $path);

        foreach ($this->adapters() as $name => $class) {
            $adapter = $this->createAdapter($class);
            $adapter->setBackgroundImage($path, 'cropTopLeft');

            $this->assertColorNotPresent(self::OUTSIDE_COLOR, $this->saveAsPng($adapter), $name);
        }
    }

    public function testBackgroundImageInsideTheWebRootIsApplied(): void
    {
        $filename = $this->createFixture(PIMCORE_WEB_ROOT, self::INSIDE_COLOR);

        foreach ($this->adapters() as $name => $class) {
            $adapter = $this->createAdapter($class);
            $adapter->setBackgroundImage('/' . $filename, 'cropTopLeft');

            $this->assertColorPresent(self::INSIDE_COLOR, $this->saveAsPng($adapter), $name);
        }
    }

    public function testBackgroundImageBelowASymlinkedWebRootDirectoryIsApplied(): void
    {
        $filename = $this->createFixture($this->siblingDir, self::LINKED_COLOR);

        $link = 'confinement-link-' . uniqid();
        if (!@symlink('../' . $this->siblingName, PIMCORE_WEB_ROOT . '/' . $link)) {
            $this->markTestSkipped('Symlinks are not available.');
        }
        $this->cleanupFiles[] = PIMCORE_WEB_ROOT . '/' . $link;

        foreach ($this->adapters() as $name => $class) {
            $adapter = $this->createAdapter($class);
            $adapter->setBackgroundImage('/' . $link . '/' . $filename, 'cropTopLeft');

            $this->assertColorPresent(self::LINKED_COLOR, $this->saveAsPng($adapter), $name);
        }
    }

    public function testOverlayOutsideTheWebRootIsNotApplied(): void
    {
        $filename = $this->createFixture($this->siblingDir, self::OUTSIDE_COLOR);

        foreach ($this->pathsOutsideTheWebRoot($filename) as $path) {
            foreach ($this->adapters() as $name => $class) {
                $adapter = $this->createAdapter($class);
                $adapter->addOverlay($path);

                $this->assertColorNotPresent(self::OUTSIDE_COLOR, $this->saveAsPng($adapter), $name);
            }
        }
    }

    public function testOverlayInsideTheWebRootIsApplied(): void
    {
        $filename = $this->createFixture(PIMCORE_WEB_ROOT, self::INSIDE_COLOR);
        $path = basename(PIMCORE_WEB_ROOT) . '/' . $filename;

        foreach ($this->adapters() as $name => $class) {
            $adapter = $this->createAdapter($class);
            $adapter->addOverlay($path);

            $this->assertColorPresent(self::INSIDE_COLOR, $this->saveAsPng($adapter), $name);
        }
    }

    public function testFittedOverlayOutsideTheWebRootIsNotApplied(): void
    {
        $this->requireImagick();

        $filename = $this->createFixture($this->siblingDir, self::OUTSIDE_COLOR);

        foreach ($this->pathsOutsideTheWebRoot($filename) as $path) {
            $adapter = $this->createAdapter(Imagick::class);
            $adapter->addOverlayFit($path);

            $this->assertColorNotPresent(self::OUTSIDE_COLOR, $this->saveAsPng($adapter), 'Imagick');
        }
    }

    public function testFittedOverlayInsideTheWebRootIsApplied(): void
    {
        $this->requireImagick();

        $filename = $this->createFixture(PIMCORE_WEB_ROOT, self::INSIDE_COLOR);

        $adapter = $this->createAdapter(Imagick::class);
        $adapter->addOverlayFit(basename(PIMCORE_WEB_ROOT) . '/' . $filename);

        $this->assertColorPresent(self::INSIDE_COLOR, $this->saveAsPng($adapter), 'Imagick');
    }

    public function testMaskOutsideTheWebRootIsNotRead(): void
    {
        $this->requireImagick();

        // a file that is not an image makes Imagick throw as soon as it is read
        $filename = 'confinement-mask-' . uniqid() . '.dat';
        file_put_contents($this->siblingDir . '/' . $filename, 'this is not an image');
        $this->cleanupFiles[] = $this->siblingDir . '/' . $filename;

        foreach ($this->pathsOutsideTheWebRoot($filename) as $path) {
            $adapter = $this->createAdapter(Imagick::class);

            try {
                $adapter->applyMask($path);
            } catch (ImagickException) {
                $this->fail('A mask file outside of the web root must not be read.');
            }
        }

        $this->addToAssertionCount(1);
    }

    public function testMaskInsideTheWebRootIsRead(): void
    {
        $this->requireImagick();

        $filename = 'confinement-mask-' . uniqid() . '.dat';
        file_put_contents(PIMCORE_WEB_ROOT . '/' . $filename, 'this is not an image');
        $this->cleanupFiles[] = PIMCORE_WEB_ROOT . '/' . $filename;

        $adapter = $this->createAdapter(Imagick::class);

        $this->expectException(ImagickException::class);
        $adapter->applyMask(basename(PIMCORE_WEB_ROOT) . '/' . $filename);
    }

    /**
     * @return array<string, class-string<GD|Imagick>>
     */
    private function adapters(): array
    {
        $adapters = ['GD' => GD::class];

        if (extension_loaded('imagick')) {
            $adapters['Imagick'] = Imagick::class;
        }

        return $adapters;
    }

    private function requireImagick(): void
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('The imagick extension is not available.');
        }
    }

    /**
     * Project-relative paths of a file in the sibling directory of the web root: the plain one, and
     * one that leaves the project directory and re-enters it. Both point at the very same file.
     *
     * @return list<string>
     */
    private function pathsOutsideTheWebRoot(string $filename): array
    {
        $projectRoot = realpath(PIMCORE_PROJECT_ROOT);
        $this->assertNotFalse($projectRoot);

        $paths = [
            $this->siblingName . '/' . $filename,
            '../' . basename($projectRoot) . '/' . $this->siblingName . '/' . $filename,
        ];

        foreach ($paths as $path) {
            $this->assertFileExists(PIMCORE_PROJECT_ROOT . '/' . $path);
        }

        return $paths;
    }

    /**
     * @param class-string<GD|Imagick> $class
     */
    private function createAdapter(string $class): GD|Imagick
    {
        $adapter = new $class();
        $adapter->load($this->createCanvasFixture());

        return $adapter;
    }

    /**
     * Writes a solid PNG of the given color into the directory and returns its file name.
     *
     * @param int[] $rgb
     */
    private function createFixture(string $directory, array $rgb): string
    {
        $filename = 'confinement-fixture-' . uniqid() . '.png';
        $path = $directory . '/' . $filename;

        $image = imagecreatetruecolor(self::CANVAS_SIZE, self::CANVAS_SIZE);
        imagefill($image, 0, 0, imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]));
        imagepng($image, $path);
        imagedestroy($image);

        $this->cleanupFiles[] = $path;

        return $filename;
    }

    /**
     * The canvas is fully transparent, so whatever a transformation draws behind or on top of it
     * is visible in the result.
     */
    private function createCanvasFixture(): string
    {
        $path = $this->temporaryPath('.png');

        $image = imagecreatetruecolor(self::CANVAS_SIZE, self::CANVAS_SIZE);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 255, 255, 127));
        imagepng($image, $path);
        imagedestroy($image);

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
    private function assertColorPresent(array $rgb, string $path, string $adapter): void
    {
        $this->assertTrue(
            $this->imageContainsColor($rgb, $path),
            $adapter . ': expected the configured image to be visible in the result.'
        );
    }

    /**
     * @param int[] $rgb
     */
    private function assertColorNotPresent(array $rgb, string $path, string $adapter): void
    {
        $this->assertFalse(
            $this->imageContainsColor($rgb, $path),
            $adapter . ': a file outside of its root directory must never be read into the result.'
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
                $color = imagecolorat($image, $x, $y);
                $alpha = ($color >> 24) & 0x7F;
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;

                if ($alpha < 64
                    && abs($r - $expected[0]) < 10
                    && abs($g - $expected[1]) < 10
                    && abs($b - $expected[2]) < 10
                ) {
                    imagedestroy($image);

                    return true;
                }
            }
        }

        imagedestroy($image);

        return false;
    }
}
