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

namespace Pimcore\Tests\Unit\Lib\Image\Adapter;

use Pimcore\Image\Adapter\Dimension as DimensionAdapter;
use Pimcore\Image\Adapter\GD;
use Pimcore\Image\Adapter\Imagick as ImagickAdapter;
use Pimcore\Model\Asset\Image\Thumbnail\Processor;
use Pimcore\Tests\Unit\Models\Asset\Thumbnail\ImageThumbnailDimensionTestCase;

/**
 * Regression coverage for dimension estimation before thumbnail file access.
 *
 * The probe deliberately reports that a thumbnail exists.
 * Reliable estimates must therefore avoid both exists() and readDimensionsFromFile(), while configurations that cannot be reproduced exactly must use the file fallback exactly once.
 */
class DimensionTest extends ImageThumbnailDimensionTestCase
{
    public function testPassThroughLogicalModeSeparatesLogicalAndGeneratedReliability(): void
    {
        $generatedScale = new DimensionAdapter(400, 300, true);
        $generatedScale->scaleByWidth(202);
        self::assertSame(202, $generatedScale->getWidth());
        self::assertSame(151, $generatedScale->getHeight());

        $passThroughScale = new DimensionAdapter(400, 300, true, true);
        $passThroughScale->scaleByWidth(202);
        self::assertSame(202, $passThroughScale->getWidth());
        self::assertSame(152, $passThroughScale->getHeight());

        $generatedVector = new DimensionAdapter(400, 300, true);
        $generatedVector->cropPercent(50, 50, 0, 0);
        self::assertFalse($generatedVector->isReliable());

        $passThroughVector = new DimensionAdapter(400, 300, true, true);
        $passThroughVector->cropPercent(50, 50, 0, 0);
        self::assertTrue($passThroughVector->isReliable());
        self::assertSame(200, $passThroughVector->getWidth());
        self::assertSame(150, $passThroughVector->getHeight());

        $generatedBackground = new DimensionAdapter(200, 150, true);
        $generatedBackground->setBackgroundImage('background.png', 'cropTopLeft');
        self::assertFalse($generatedBackground->isReliable());

        $passThroughBackground = new DimensionAdapter(200, 150, true, true);
        $passThroughBackground->setBackgroundImage('background.png', 'cropTopLeft');
        self::assertTrue($passThroughBackground->isReliable());
        self::assertSame(200, $passThroughBackground->getWidth());
        self::assertSame(150, $passThroughBackground->getHeight());

        $generatedCrop = new DimensionAdapter(400, 300, true);
        $generatedCrop->crop(390, 290, 200, 100);
        self::assertFalse($generatedCrop->isReliable());

        $passThroughCrop = new DimensionAdapter(400, 300, true, true);
        $passThroughCrop->crop(390, 290, 200, 100);
        self::assertTrue($passThroughCrop->isReliable());
        self::assertSame(200, $passThroughCrop->getWidth());
        self::assertSame(100, $passThroughCrop->getHeight());

        $generatedCover = new DimensionAdapter(400, 300, false);
        $generatedCover->cover(800, 600);
        self::assertFalse($generatedCover->isReliable());

        $passThroughCover = new DimensionAdapter(400, 300, false, true);
        $passThroughCover->cover(800, 600);
        self::assertTrue($passThroughCover->isReliable());
        self::assertSame(800, $passThroughCover->getWidth());
        self::assertSame(600, $passThroughCover->getHeight());
    }

    public function testDimensionAdapterSupportContractIsFailClosed(): void
    {
        foreach (['resize', 'cover', 'setBackgroundImage', 'rotate', 'trim'] as $method) {
            self::assertTrue(Processor::hasTransformationArgumentMapping($method), $method);
            self::assertTrue(DimensionAdapter::supportsReliableTransformation($method), $method);
        }

        foreach (['1x1_pixel', 'tifforiginal', 'futureProcessorTransformation'] as $method) {
            self::assertFalse(DimensionAdapter::supportsReliableTransformation($method), $method);
        }

        self::assertFalse(Processor::hasTransformationArgumentMapping('1x1_pixel'));
        self::assertFalse(Processor::hasTransformationArgumentMapping('tifforiginal'));
    }

    public function testBackgroundImageCropTopLeftFallsBackAndGdKeepsForegroundDimensions(): void
    {
        $foreground = $this->createRasterFixture(400, 300);
        $asset = $this->image(400, 300, 'source.png');

        foreach ([[100, 100], [800, 600]] as [$width, $height]) {
            $background = $this->createWebRootRasterFixture($width, $height);
            $config = $this->config([[
                'method' => 'setBackgroundImage',
                'arguments' => [
                    'path' => basename($background),
                    'mode' => 'cropTopLeft',
                ],
            ]]);

            self::assertSame([], $config->getEstimatedDimensions($asset));
        }

        $smallBackground = $this->createWebRootRasterFixture(100, 100);
        $config = $this->config([[
            'method' => 'setBackgroundImage',
            'arguments' => [
                'path' => basename($smallBackground),
                'mode' => 'cropTopLeft',
            ],
        ]]);

        self::assertSame(
            ['width' => 400, 'height' => 300],
            $this->renderedDimensions(GD::class, $foreground, $asset, $config)
        );
    }

    public function testBackgroundImageCropTopLeftImagickOutputDemonstratesWhyFallbackIsRequired(): void
    {
        $this->requireImagickExtension();

        $foreground = $this->createRasterFixture(400, 300);
        $smallBackground = $this->createWebRootRasterFixture(100, 100);
        $asset = $this->image(400, 300, 'source.png');
        $config = $this->config([[
            'method' => 'setBackgroundImage',
            'arguments' => [
                'path' => basename($smallBackground),
                'mode' => 'cropTopLeft',
            ],
        ]]);

        self::assertSame(
            ['width' => 100, 'height' => 100],
            $this->renderedDimensions(ImagickAdapter::class, $foreground, $asset, $config)
        );
    }

    public function testBackgroundImageFitAndTextureMatchGdDimensions(): void
    {
        $foreground = $this->createRasterFixture(400, 300);
        $background = $this->createWebRootRasterFixture(100, 100);
        $asset = $this->image(400, 300, 'source.png');

        foreach ([null, 'asTexture'] as $mode) {
            $arguments = ['path' => basename($background)];
            if ($mode !== null) {
                $arguments['mode'] = $mode;
            }
            $config = $this->config([[
                'method' => 'setBackgroundImage',
                'arguments' => $arguments,
            ]]);

            $estimated = $config->getEstimatedDimensions($asset);
            self::assertSame(['width' => 400, 'height' => 300], $estimated);
            self::assertSame($estimated, $this->renderedDimensions(GD::class, $foreground, $asset, $config));
        }
    }

    public function testBackgroundImageFitAndTextureMatchImagickDimensions(): void
    {
        $this->requireImagickExtension();

        $foreground = $this->createRasterFixture(400, 300);
        $background = $this->createWebRootRasterFixture(100, 100);
        $asset = $this->image(400, 300, 'source.png');

        foreach ([null, 'asTexture'] as $mode) {
            $arguments = ['path' => basename($background)];
            if ($mode !== null) {
                $arguments['mode'] = $mode;
            }
            $config = $this->config([[
                'method' => 'setBackgroundImage',
                'arguments' => $arguments,
            ]]);

            $estimated = $config->getEstimatedDimensions($asset);
            self::assertSame(
                $estimated,
                $this->renderedDimensions(ImagickAdapter::class, $foreground, $asset, $config)
            );
        }
    }

    public function testRasterBytesWithConflictingVectorMimeFallBackToActualImagickOutput(): void
    {
        $this->requireImagickExtension();

        $source = $this->createRasterFixture(100, 100);
        $asset = $this->image(100, 100, 'source.png');
        $asset->setMimeType('image/svg+xml');
        $config = $this->config([
            ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
        ]);

        self::assertSame([], $config->getEstimatedDimensions($asset));
        self::assertSame(
            ['width' => 100, 'height' => 100],
            $this->renderedDimensions(ImagickAdapter::class, $source, $asset, $config)
        );
    }

    public function testVectorBytesWithConflictingRasterMimeFallBackToActualImagickOutput(): void
    {
        $this->requireImagickExtension();

        $source = $this->createSvgFixture(100, 100);
        $this->requireImagickFixtureSupport($source);
        $asset = $this->image(100, 100, 'source.svg');
        $asset->setMimeType('image/png');
        $config = $this->config([
            ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
        ]);
        $config->setRasterizeSVG(true);

        self::assertSame([], $config->getEstimatedDimensions($asset));
        $actual = $this->renderedDimensions(ImagickAdapter::class, $source, $asset, $config);
        self::assertGreaterThan(0, $actual['width']);
        self::assertGreaterThan(0, $actual['height']);

        $thumbnail = $this->probe($asset, $config, $actual);
        self::assertSame($actual, $thumbnail->getDimensions());
        self::assertSame(1, $thumbnail->readDimensionsCalls);
    }

    public function testEstimatorMatchesPhysicalGdOutputForSupportedRasterPipelines(): void
    {
        $source = $this->createRasterFixture(400, 300);
        $asset = $this->image(400, 300, 'source.png');

        foreach ($this->supportedRasterPipelineCases() as $name => [$items, $highResolution]) {
            $config = $this->config($items, $highResolution);
            $estimated = $config->getEstimatedDimensions($asset);
            self::assertNotSame([], $estimated, $name);
            $actual = $this->renderedDimensions(GD::class, $source, $asset, $config);
            self::assertSame($estimated, $actual, $name);
        }
    }

    public function testEstimatorMatchesPhysicalImagickOutputForSupportedRasterPipelines(): void
    {
        $this->requireImagickExtension();

        $source = $this->createRasterFixture(400, 300);
        $this->requireImagickFixtureSupport($source);
        $asset = $this->image(400, 300, 'source.png');

        foreach ($this->supportedRasterPipelineCases() as $name => [$items, $highResolution]) {
            $config = $this->config($items, $highResolution);
            $estimated = $config->getEstimatedDimensions($asset);
            self::assertNotSame([], $estimated, $name);

            $actual = $this->renderedDimensions(ImagickAdapter::class, $source, $asset, $config);
            self::assertSame($estimated, $actual, $name);
        }
    }

    public function testGeneratedUppercaseSvgFallsBackToPhysicalImagickOutput(): void
    {
        $source = $this->createSvgFixture(100, 100, '.SVG');
        $asset = $this->image(100, 100, 'source.SVG');
        $config = $this->config([
            ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
        ]);

        $this->requireImagickFixtureSupport($source);
        $actual = $this->renderedDimensions(ImagickAdapter::class, $source, $asset, $config);

        self::assertSame([], $config->getEstimatedDimensions($asset));
        self::assertGreaterThan(0, $actual['width']);
        self::assertGreaterThan(0, $actual['height']);

        $thumbnail = $this->probe($asset, $config, $actual);
        self::assertSame($actual, $thumbnail->getDimensions());
        self::assertSame(1, $thumbnail->readDimensionsCalls);
    }
}
