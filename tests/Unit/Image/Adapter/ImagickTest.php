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
 * Transformations that composite the image onto a freshly created canvas
 * (setBackgroundColor(), frame()) used to drop the colorspace of the source
 * image, because \Imagick::newImage() always produces an sRGB canvas. With
 * preserveColor = true the CMYK channel data of the source was therefore
 * written into an sRGB image, which made the thumbnail come out with inverted
 * colors.
 */
class ImagickTest extends TestCase
{
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
        $source = $this->createCmykImage();

        $adapter = new Imagick();
        $adapter->load($source, ['preserveColor' => true]);
        $adapter->setBackgroundColor('#ffffff');

        $this->assertSame(\Imagick::COLORSPACE_CMYK, $this->saveAndGetColorspace($adapter));
    }

    public function testFramePreservesCmykColorspace(): void
    {
        $source = $this->createCmykImage();

        $adapter = new Imagick();
        $adapter->load($source, ['preserveColor' => true]);
        $adapter->frame(80, 80);

        $this->assertSame(\Imagick::COLORSPACE_CMYK, $this->saveAndGetColorspace($adapter));
    }

    /**
     * Without preserveColor the image is converted to sRGB while loading, so the
     * canvas must stay sRGB as well.
     */
    public function testSetBackgroundColorConvertsCmykToSrgbWithoutPreserveColor(): void
    {
        $source = $this->createCmykImage();

        $adapter = new Imagick();
        $adapter->load($source, ['preserveColor' => false]);
        $adapter->setBackgroundColor('#ffffff');

        $this->assertSame(\Imagick::COLORSPACE_SRGB, $this->saveAndGetColorspace($adapter));
    }

    private function createCmykImage(): string
    {
        $image = new \Imagick();
        $image->newImage(40, 40, new ImagickPixel('rgb(220,30,40)'));
        $image->setImageFormat('jpeg');
        $image->transformImageColorspace(\Imagick::COLORSPACE_CMYK);

        $path = $this->tmpFile('jpg');
        $image->writeImage($path);

        $written = new \Imagick($path);
        $this->assertSame(
            \Imagick::COLORSPACE_CMYK,
            $written->getImageColorspace(),
            'The test fixture could not be created as a CMYK image.'
        );

        return $path;
    }

    private function saveAndGetColorspace(Imagick $adapter): int
    {
        $path = $this->tmpFile('jpg');
        $adapter->save($path, 'jpeg');

        return (new \Imagick($path))->getImageColorspace();
    }

    private function tmpFile(string $extension): string
    {
        $path = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/imagick-adapter-test-' . uniqid() . '.' . $extension;
        $this->tmpFiles[] = $path;

        return $path;
    }
}
