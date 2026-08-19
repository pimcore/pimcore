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

use ImagickPixel;
use Pimcore\Image\Adapter\Imagick;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression tests for the Imagick adapter.
 *
 * #14543: setBackgroundColor() composites the image onto a freshly created canvas,
 * and \Imagick::newImage() always produces an sRGB canvas. With preserveColor = true
 * the CMYK channel data of the source was therefore written into an sRGB image,
 * which made the thumbnail come out with inverted colors.
 *
 * #184 (platform-version): images carrying a plain Photoshop path were clipped as
 * if that path was a clipping path, which left the thumbnails empty.
 */
final class ImagickTest extends TestCase
{
    /**
     * The color the test fixtures are built from.
     */
    private const SOURCE_RGB = [220, 30, 40];

    private const WHITE_RGB = [255, 255, 255];

    /**
     * Tolerance per channel for a color that stayed in its colorspace. Only the
     * JPEG compression is lossy here.
     */
    private const DELTA = 12;

    /**
     * Tolerance per channel for a color that went through an ICC based CMYK to
     * sRGB conversion, which is considerably lossy - but nowhere near enough to
     * turn red into the cyan the regression produced.
     */
    private const DELTA_CONVERTED = 45;

    private const FIXTURE_SIZE = 40;

    /**
     * @var string[]
     */
    private array $tmpFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('The imagick extension is not available.');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $file) {
            @unlink($file);
        }
        $this->tmpFiles = [];

        parent::tearDown();
    }

    public function testSetBackgroundColorPreservesCmykColorspace(): void
    {
        $adapter = $this->adapter($this->createCmykImage(), true);
        $adapter->setBackgroundColor('#ffffff');

        $result = $this->saveAndReload($adapter);

        $this->assertSame(\Imagick::COLORSPACE_CMYK, $result->getImageColorspace());
        $this->assertArrayHasKey('icc', $result->getImageProfiles('icc', true));
        $this->assertPixelColor(self::SOURCE_RGB, $result, 20, 20, self::DELTA);
    }

    /**
     * Without preserveColor the image is converted to sRGB while loading, so the
     * canvas has to stay sRGB as well.
     */
    public function testSetBackgroundColorConvertsCmykToSrgbWithoutPreserveColor(): void
    {
        $adapter = $this->adapter($this->createCmykImage(), false);
        $adapter->setBackgroundColor('#ffffff');

        $result = $this->saveAndReload($adapter);

        $this->assertSame(\Imagick::COLORSPACE_SRGB, $result->getImageColorspace());
        $this->assertPixelColor(self::SOURCE_RGB, $result, 20, 20, self::DELTA_CONVERTED);
    }

    /**
     * frame() builds a transparent canvas, which has to be flattened onto the
     * background color when the JPEG is written - and that only works in an RGB
     * colorspace. The canvas therefore deliberately keeps its colorspace, and
     * the border around the image has to stay white instead of turning black.
     */
    public function testFrameKeepsAWhiteBorderForACmykImage(): void
    {
        $adapter = $this->adapter($this->createCmykImage(), true);
        // the fixture is smaller than the frame, so it is centered on the canvas
        // and the canvas stays visible as a border around it
        $adapter->frame(80, 80);

        $result = $this->saveAndReload($adapter);

        $this->assertPixelColor(self::WHITE_RGB, $result, 5, 5, self::DELTA);
    }

    /**
     * Photoshop keeps every saved path in an image resource of its own (8BIM 2000 - 2998),
     * no matter whether the path was designated as the clipping path (8BIM 2999) or not.
     * Clipping the image by such an arbitrary path removes all of its content, so an image
     * that has no clipping path must be loaded as it is.
     */
    public function testImageWithSavedPathButWithoutClippingPathIsNotClipped(): void
    {
        $adapter = $this->adapter($this->createImageWithPhotoshopPath(), false);

        $result = $this->saveAndReload($adapter, 'png32', 'png');

        // the whole image has to be left intact, both inside and outside of the saved path
        $this->assertPixelOpaque($result, 20, 20);
        $this->assertPixelOpaque($result, 2, 2);
        $this->assertPixelColor(self::SOURCE_RGB, $result, 20, 20, self::DELTA);
        $this->assertPixelColor(self::SOURCE_RGB, $result, 2, 2, self::DELTA);
    }

    /**
     * When the clipping itself fails - e.g. because the clipping path cannot be resolved or the
     * ImageMagick installation cannot render it - the image has to be delivered unclipped, which
     * includes the transparency it already had before the clipping was attempted.
     */
    public function testFailingClippingKeepsTheUnclippedImage(): void
    {
        $adapter = $this->adapter($this->createImageWithUnresolvableClippingPath(), false);

        $result = $this->saveAndReload($adapter, 'png32', 'png');

        $this->assertPixelOpaque($result, 20, 20);
        $this->assertPixelColor(self::SOURCE_RGB, $result, 20, 20, self::DELTA);
        $this->assertPixelTransparent($result, 2, 2);
    }

    private function adapter(string $path, bool $preserveColor): Imagick
    {
        $adapter = new Imagick();
        $adapter->load($path, ['preserveColor' => $preserveColor]);
        // mirrors the thumbnail configuration of #14543
        $adapter->setPreserveMetaData(true);

        return $adapter;
    }

    /**
     * Creates an opaque CMYK JPEG with an embedded CMYK ICC profile.
     */
    private function createCmykImage(): string
    {
        $image = new \Imagick();
        $image->newImage(
            self::FIXTURE_SIZE,
            self::FIXTURE_SIZE,
            new ImagickPixel(sprintf('rgb(%d,%d,%d)', ...self::SOURCE_RGB))
        );
        $image->setImageFormat('jpeg');
        $image->transformImageColorspace(\Imagick::COLORSPACE_CMYK);
        $image->profileImage('icc', Imagick::getCMYKColorProfile());

        $path = $this->tmpFile('jpg');
        $image->writeImage($path);

        $fixture = new \Imagick($path);
        $this->assertSame(
            \Imagick::COLORSPACE_CMYK,
            $fixture->getImageColorspace(),
            'The test fixture could not be created as a CMYK image.'
        );
        $this->assertArrayHasKey(
            'icc',
            $fixture->getImageProfiles('icc', true),
            'The test fixture was created without an ICC profile.'
        );

        return $path;
    }

    /**
     * Creates an image with a single saved Photoshop path (8BIM 2000) covering the center of
     * the image, but without the 'Name of clipping path' resource (8BIM 2999) - the same
     * metadata Photoshop writes for an image that has a path, but no clipping path.
     */
    private function createImageWithPhotoshopPath(): string
    {
        $image = new \Imagick();
        $image->newImage(
            self::FIXTURE_SIZE,
            self::FIXTURE_SIZE,
            new ImagickPixel(sprintf('rgb(%d,%d,%d)', ...self::SOURCE_RGB))
        );
        $image->setImageFormat('tiff');
        $image->profileImage('8bim', $this->pathImageResource());

        $path = $this->tmpFile('tif');
        $image->writeImage($path);

        $fixture = new \Imagick($path);
        $this->assertNotEmpty(
            $fixture->getImageProperty('8BIM:1999,2998:#1'),
            'The test fixture was created without a Photoshop path.'
        );

        return $path;
    }

    /**
     * Builds an 8BIM image resource block holding one closed path, see
     * https://www.adobe.com/devnet-apps/photoshop/fileformatashtml/#50577409_pgfId-1037504
     */
    private function pathImageResource(): string
    {
        // 'Closed subpath length record': selector 0, followed by the number of knots
        $data = pack('nn', 0, 4) . str_repeat("\x00", 22);

        // one 'Closed subpath Bezier knot' record per corner of a centered square, each holding
        // the preceding control point, the anchor point and the leaving control point as pairs of
        // 8.24 fixed point numbers relative to the image dimensions (vertical before horizontal)
        foreach ([[0.25, 0.25], [0.25, 0.75], [0.75, 0.75], [0.75, 0.25]] as [$vertical, $horizontal]) {
            $point = pack('NN', (int) ($vertical * (1 << 24)), (int) ($horizontal * (1 << 24)));
            $data .= pack('n', 2) . str_repeat($point, 3);
        }

        // the resource name is a pascal string padded to an even length
        $name = "\x06" . 'Path 1' . "\x00";

        return '8BIM' . pack('n', 2000) . $name . pack('N', strlen($data)) . $data;
    }

    /**
     * Creates a partly transparent image that carries the 'Name of clipping path' resource
     * (8BIM 2999) without any path information, so that \Imagick::clipImage() fails with
     * 'no clip path defined'.
     */
    private function createImageWithUnresolvableClippingPath(): string
    {
        $square = new \Imagick();
        $square->newImage(
            (int) (self::FIXTURE_SIZE / 2),
            (int) (self::FIXTURE_SIZE / 2),
            new ImagickPixel(sprintf('rgb(%d,%d,%d)', ...self::SOURCE_RGB))
        );

        $image = new \Imagick();
        $image->newImage(self::FIXTURE_SIZE, self::FIXTURE_SIZE, new ImagickPixel('transparent'));
        $image->setImageFormat('tiff');
        // leaves a transparent border around the opaque center of the image
        $image->compositeImage($square, \Imagick::COMPOSITE_OVER, (int) (self::FIXTURE_SIZE / 4), (int) (self::FIXTURE_SIZE / 4));
        $image->profileImage('8bim', $this->clippingPathNameImageResource());

        $path = $this->tmpFile('tif');
        $image->writeImage($path);

        $fixture = new \Imagick($path);
        $this->assertTrue(
            (bool) $fixture->getImageAlphaChannel(),
            'The test fixture was created without an alpha channel.'
        );
        $this->assertFalse(
            $fixture->getImageProperty('8BIM:1999,2998:#1'),
            'The test fixture was created with a resolvable clipping path.'
        );

        return $path;
    }

    /**
     * Builds an 8BIM image resource block naming the clipping path of the image, holding the
     * name of the path as a pascal string followed by the flatness as an 8.24 fixed point number.
     */
    private function clippingPathNameImageResource(): string
    {
        $data = "\x06" . 'Path 1' . pack('N', 1 << 24);

        return '8BIM' . pack('n', 2999) . "\x00\x00" . pack('N', strlen($data)) . $data;
    }

    private function saveAndReload(Imagick $adapter, string $format = 'jpeg', string $extension = 'jpg'): \Imagick
    {
        $path = $this->tmpFile($extension);
        $adapter->save($path, $format);

        return new \Imagick($path);
    }

    private function assertPixelOpaque(\Imagick $image, int $x, int $y): void
    {
        // 1 = normalized color values, so a fully opaque pixel has an alpha value of 1.0
        $this->assertEqualsWithDelta(
            1.0,
            $image->getImagePixelColor($x, $y)->getColor(1)['a'],
            0.001,
            sprintf('The pixel at %d,%d is not opaque.', $x, $y)
        );
    }

    private function assertPixelTransparent(\Imagick $image, int $x, int $y): void
    {
        $this->assertEqualsWithDelta(
            0.0,
            $image->getImagePixelColor($x, $y)->getColor(1)['a'],
            0.001,
            sprintf('The pixel at %d,%d is not transparent.', $x, $y)
        );
    }

    /**
     * @param int[] $expectedRgb
     */
    private function assertPixelColor(array $expectedRgb, \Imagick $image, int $x, int $y, float $delta): void
    {
        $probe = clone $image;
        $probe->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
        $color = $probe->getImagePixelColor($x, $y)->getColor();

        foreach (['r', 'g', 'b'] as $index => $channel) {
            $this->assertEqualsWithDelta(
                $expectedRgb[$index],
                $color[$channel],
                $delta,
                sprintf('Unexpected color at %d,%d: rgb(%d,%d,%d)', $x, $y, $color['r'], $color['g'], $color['b'])
            );
        }
    }

    private function tmpFile(string $extension): string
    {
        $path = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/imagick-adapter-test-' . uniqid() . '.' . $extension;
        $this->tmpFiles[] = $path;

        return $path;
    }
}
