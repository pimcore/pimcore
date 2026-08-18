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
 * Regression tests for the Imagick adapter (#14543).
 *
 * setBackgroundColor() composites the image onto a freshly created canvas, and
 * \Imagick::newImage() always produces an sRGB canvas. With preserveColor = true
 * the CMYK channel data of the source was therefore written into an sRGB image,
 * which made the thumbnail come out with inverted colors.
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

    private function saveAndReload(Imagick $adapter): \Imagick
    {
        $path = $this->tmpFile('jpg');
        $adapter->save($path, 'jpeg');

        return new \Imagick($path);
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
