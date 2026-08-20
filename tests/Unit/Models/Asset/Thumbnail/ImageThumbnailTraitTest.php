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

namespace Pimcore\Tests\Unit\Models\Asset\Thumbnail;

use Doctrine\Persistence\ConnectionRegistry;
use League\Flysystem\FilesystemOperator;
use Pimcore as PimcoreRuntime;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Http\RequestHelper;
use Pimcore\Image\Adapter\GD;
use Pimcore\Image\Adapter\Imagick as ImagickAdapter;
use Pimcore\Image\AdapterInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image\Thumbnail\Processor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Regression coverage for dimension estimation before thumbnail file access.
 *
 * The probe deliberately reports that a thumbnail exists.
 * Reliable estimates must therefore avoid both exists() and readDimensionsFromFile(), while configurations that cannot be reproduced exactly must use the file fallback exactly once.
 */
class ImageThumbnailTraitTest extends ImageThumbnailDimensionTestCase
{
    public function testStatusCacheHitSkipsEstimationAndFileAccess(): void
    {
        $this->setStatusCacheEnabled(true);

        // Deliberately omit stored source dimensions and use an unknown operation.
        // If the status-cache result did not win, estimation/file access would be required.
        $asset = $this->image(null, null);
        $dao = $this->createMock(Asset\Dao::class);
        $dao->expects(self::once())
            ->method('getCachedThumbnail')
            ->with('dimension-probe', 'dimension-probe.jpg')
            ->willReturn(['width' => 640, 'height' => 480]);
        $asset->setDao($dao);

        $thumbnail = $this->probe(
            $asset,
            $this->config([
                ['method' => 'projectSpecificTransformation', 'arguments' => []],
            ])
        );

        self::assertSame(['width' => 640, 'height' => 480], $thumbnail->getDimensions());
        self::assertSame(0, $thumbnail->existsCalls);
        self::assertSame(0, $thumbnail->readDimensionsCalls);
    }

    public function testUnreliableTransformationsFallBackToFileExactlyOnce(): void
    {
        $cases = [
            'rotate' => [
                $this->image(400, 300),
                [['method' => 'rotate', 'arguments' => ['angle' => 45]]],
            ],
            'trim' => [
                $this->image(400, 300),
                [['method' => 'trim', 'arguments' => ['tolerance' => 10]]],
            ],
            'unknown/custom operation' => [
                $this->image(400, 300),
                [['method' => 'projectSpecificTransformation', 'arguments' => []]],
            ],
            'malformed operation' => [
                $this->image(400, 300),
                [['method' => 'scaleByWidth', 'arguments' => []]],
            ],
            'fractional pixel argument' => [
                $this->image(400, 300),
                [['method' => 'scaleByWidth', 'arguments' => ['width' => 200.5]]],
            ],
            'non-boolean force resize' => [
                $this->image(400, 300),
                [['method' => 'scaleByWidth', 'arguments' => ['width' => 200, 'forceResize' => 1]]],
            ],
            'background color without color' => [
                $this->image(400, 300),
                [['method' => 'setBackgroundColor', 'arguments' => []]],
            ],
            'overlay without path' => [
                $this->image(400, 300),
                [['method' => 'addOverlay', 'arguments' => ['x' => 10, 'y' => 20]]],
            ],
            'mirror without mode' => [
                $this->image(400, 300),
                [['method' => 'mirror', 'arguments' => []]],
            ],
            'invalid cover positioning' => [
                $this->image(400, 300),
                [['method' => 'cover', 'arguments' => ['width' => 200, 'height' => 200, 'positioning' => 'invalid']]],
            ],
            'out-of-bounds percentage crop' => [
                $this->image(400, 300),
                [['method' => 'cropPercent', 'arguments' => ['width' => 60, 'height' => 50, 'x' => 50, 'y' => 0]]],
            ],
            'incomplete source dimensions' => [
                $this->image(400, null),
                [['method' => 'scaleByWidth', 'arguments' => ['width' => 200]]],
            ],
            'percentage crop on vector graphic' => [
                $this->image(400, 300, 'source.svg'),
                [['method' => 'cropPercent', 'arguments' => ['width' => 50, 'height' => 50, 'x' => 0, 'y' => 0]]],
            ],
        ];

        foreach ($cases as $name => [$asset, $items]) {
            $thumbnail = $this->probe($asset, $this->config($items), ['width' => 73, 'height' => 41]);

            self::assertSame(['width' => 73, 'height' => 41], $thumbnail->getDimensions(), $name);
            self::assertSame(1, $thumbnail->readDimensionsCalls, $name);
        }
    }

    public function testFailedFileFallbackIsAttemptedOnlyOncePerDirectDimensionRead(): void
    {
        $thumbnail = $this->probe(
            $this->image(400, 300),
            $this->config([
                ['method' => 'projectSpecificTransformation', 'arguments' => []],
            ]),
            []
        );

        self::assertSame(['width' => null, 'height' => null], $thumbnail->getDimensions());
        self::assertSame(1, $thumbnail->readDimensionsCalls);
    }

    public function testAssetPathFallbackExtractsDimensionsWithoutPersistingOrRepeatingTheRead(): void
    {
        $source = $this->createRasterFixture(400, 300);
        $assetStorage = $this->createMock(FilesystemOperator::class);
        $assetStorage->expects(self::once())
            ->method('readStream')
            ->with('source.png')
            ->willReturnCallback(static function () use ($source) {
                $stream = fopen($source, 'rb');
                self::assertIsResource($stream);

                return $stream;
            });
        $assetStorage->expects(self::never())->method('fileExists');
        $this->installAssetStorage($assetStorage);

        $asset = $this->image(null, null, 'source.png');
        $config = $this->config([
            ['method' => 'resize', 'arguments' => ['width' => 200, 'height' => 100]],
        ]);
        $config->setFormat('ORIGINAL');
        $thumbnail = new RealAssetImageThumbnailTraitProbe($asset, $config);

        self::assertSame(400, $thumbnail->getWidth());
        self::assertSame(300, $thumbnail->getHeight());
        self::assertSame(400, $thumbnail->getRealWidth());
        self::assertSame(300, $thumbnail->getRealHeight());
        self::assertSame(1, $thumbnail->generationCalls);
        self::assertNull($asset->getCustomSetting('imageWidth'));
        self::assertNull($asset->getCustomSetting('imageHeight'));
    }

    public function testMissingPassThroughSourceDimensionsRetainPersistedLogicalOutput(): void
    {
        $source = $this->createRasterFixture(400, 300);
        $cases = [
            'SVG scale by width' => [
                'source.svg',
                [['method' => 'scaleByWidth', 'arguments' => ['width' => 202]]],
            ],
            'SVG scale by height' => [
                'source.svg',
                [['method' => 'scaleByHeight', 'arguments' => ['height' => 151]]],
            ],
            'SVG resize' => [
                'source.svg',
                [['method' => 'resize', 'arguments' => ['width' => 200, 'height' => 100]]],
            ],
            'SVG percentage crop' => [
                'source.svg',
                [['method' => 'cropPercent', 'arguments' => ['width' => 50, 'height' => 50, 'x' => 0, 'y' => 0]]],
            ],
            'SVG absolute crop outside source canvas' => [
                'source.svg',
                [['method' => 'crop', 'arguments' => ['x' => 390, 'y' => 290, 'width' => 200, 'height' => 100]]],
            ],
            'SVG scale and background crop' => [
                'source.svg',
                [
                    ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
                    [
                        'method' => 'setBackgroundImage',
                        'arguments' => ['path' => 'background.png', 'mode' => 'cropTopLeft'],
                    ],
                ],
            ],
            'SVG scale and project operation' => [
                'source.svg',
                [
                    ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
                    ['method' => 'projectSpecificTransformation', 'arguments' => []],
                ],
            ],
            'SVG scale and malformed visual operation' => [
                'source.svg',
                [
                    ['method' => 'scaleByWidth', 'arguments' => ['width' => 202]],
                    ['method' => 'setBackgroundColor', 'arguments' => []],
                ],
            ],
            'TIFF original marker' => [
                'source.tiff',
                [['method' => 'tifforiginal', 'arguments' => []]],
            ],
            'TIFF original marker and percentage crop' => [
                'source.tiff',
                [
                    ['method' => 'tifforiginal', 'arguments' => []],
                    ['method' => 'cropPercent', 'arguments' => ['width' => 50, 'height' => 50, 'x' => 0, 'y' => 0]],
                ],
            ],
            'TIFF original marker and scale' => [
                'source.tiff',
                [
                    ['method' => 'tifforiginal', 'arguments' => []],
                    ['method' => 'scaleByWidth', 'arguments' => ['width' => 202]],
                ],
            ],
            'TIFF original marker and cover upscale' => [
                'source.tiff',
                [
                    ['method' => 'tifforiginal', 'arguments' => []],
                    ['method' => 'cover', 'arguments' => ['width' => 800, 'height' => 600]],
                ],
            ],
        ];

        foreach ($cases as $name => [$filename, $items]) {
            foreach ([null, 1.5, 2.0] as $highResolution) {
                $config = $this->config($items, $highResolution);
                $config->setFormat('PRINT');

                $persistedThumbnail = $this->probe($this->image(400, 300, $filename), $config);
                $expectedDisplay = $persistedThumbnail->getDimensions();
                $expectedReal = [
                    'width' => $persistedThumbnail->getRealWidth(),
                    'height' => $persistedThumbnail->getRealHeight(),
                ];

                $missingAsset = $this->countingImage(
                    null,
                    null,
                    $filename,
                    $source,
                    ['width' => 400, 'height' => 300]
                );
                self::assertTrue(Processor::usesOriginalAssetOutput($missingAsset, $config), $name);
                self::assertSame(
                    $expectedReal,
                    $config->getEstimatedDimensionsForSource($missingAsset, 400, 300),
                    $name
                );
                $missingThumbnail = new RealAssetImageThumbnailTraitProbe($missingAsset, $config);

                self::assertSame($expectedDisplay, $missingThumbnail->getDimensions(), $name);
                self::assertSame($expectedReal['width'], $missingThumbnail->getRealWidth(), $name);
                self::assertSame($expectedReal['height'], $missingThumbnail->getRealHeight(), $name);
                self::assertSame(1, $missingAsset->streamCalls, $name);
                self::assertSame(1, $missingAsset->dimensionsFromFileCalls, $name);
                self::assertNull($missingAsset->getCustomSetting('imageWidth'), $name);
                self::assertNull($missingAsset->getCustomSetting('imageHeight'), $name);
            }
        }
    }

    public function testPassThroughMimeConflictsFollowProcessorFilenameRouting(): void
    {
        $source = $this->createRasterFixture(400, 300);
        $cases = [
            'SVG filename with raster MIME' => [
                'source.svg',
                'image/png',
                100,
                100,
                [['method' => 'scaleByWidth', 'arguments' => ['width' => 200]]],
                ['width' => 200, 'height' => 200],
            ],
            'TIFF filename with vector MIME' => [
                'source.tiff',
                'image/svg+xml',
                400,
                300,
                [
                    ['method' => 'tifforiginal', 'arguments' => []],
                    ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
                ],
                ['width' => 200, 'height' => 150],
            ],
        ];

        foreach ($cases as $name => [$filename, $mimeType, $sourceWidth, $sourceHeight, $items, $expected]) {
            $config = $this->config($items);
            $config->setFormat('PRINT');

            $persistedAsset = $this->countingImage($sourceWidth, $sourceHeight, $filename, $source);
            $persistedAsset->setMimeType($mimeType);
            $persistedThumbnail = new RealAssetImageThumbnailTraitProbe($persistedAsset, $config);

            self::assertSame($expected, $persistedThumbnail->getDimensions(), $name);
            self::assertSame(0, $persistedAsset->streamCalls, $name);
            self::assertSame(0, $persistedAsset->dimensionsFromFileCalls, $name);

            $missingAsset = $this->countingImage(
                null,
                null,
                $filename,
                $source,
                ['width' => $sourceWidth, 'height' => $sourceHeight]
            );
            $missingAsset->setMimeType($mimeType);
            $missingThumbnail = new RealAssetImageThumbnailTraitProbe($missingAsset, $config);

            self::assertSame($expected, $missingThumbnail->getDimensions(), $name);
            self::assertSame(1, $missingAsset->streamCalls, $name);
            self::assertSame(1, $missingAsset->dimensionsFromFileCalls, $name);
            self::assertNull($missingAsset->getCustomSetting('imageWidth'), $name);
            self::assertNull($missingAsset->getCustomSetting('imageHeight'), $name);
        }
    }

    public function testOriginalThumbnailStorageFallbackUsesPhysicalBytesWithoutAssetRead(): void
    {
        $source = $this->createRasterFixture(400, 300);
        $storagePath = 'asset/1/image-thumb__1__dimension-probe/source.png';
        $assetStorage = $this->createMock(FilesystemOperator::class);
        $assetStorage->expects(self::never())->method('readStream');
        $assetStorage->expects(self::never())->method('fileExists');
        $thumbnailStorage = $this->createMock(FilesystemOperator::class);
        $thumbnailStorage->expects(self::once())
            ->method('readStream')
            ->with($storagePath)
            ->willReturnCallback(static function () use ($source) {
                $stream = fopen($source, 'rb');
                self::assertIsResource($stream);

                return $stream;
            });
        $thumbnailStorage->expects(self::never())->method('fileExists');
        $this->installStorages($assetStorage, $thumbnailStorage);

        $asset = $this->image(null, null, 'source.png');
        $dao = $this->createMock(Asset\Dao::class);
        $dao->expects(self::once())
            ->method('addToThumbnailCache')
            ->with('dimension-probe', 'source.png', filesize($source), 400, 300);
        $dao->expects(self::once())
            ->method('getCachedThumbnail')
            ->with('dimension-probe', 'source.png')
            ->willReturn(['width' => 400, 'height' => 300]);
        $asset->setDao($dao);

        $config = $this->config([
            ['method' => 'resize', 'arguments' => ['width' => 200, 'height' => 100]],
        ]);
        $config->setFormat('ORIGINAL');
        $thumbnail = new RealThumbnailImageThumbnailTraitProbe($asset, $config, $storagePath);

        self::assertSame(400, $thumbnail->getWidth());
        self::assertSame(300, $thumbnail->getHeight());
        self::assertSame(400, $thumbnail->getRealWidth());
        self::assertSame(300, $thumbnail->getRealHeight());
        self::assertSame(1, $thumbnail->generationCalls);
        self::assertNull($asset->getCustomSetting('imageWidth'));
        self::assertNull($asset->getCustomSetting('imageHeight'));
    }

    public function testOriginalThumbnailCacheUsesPhysicalDimensionsWhileAssetFallbackRemainsExifAware(): void
    {
        $source = $this->createExifOrientedJpegFixture(400, 300, 6);
        $physicalSize = getimagesize($source);
        self::assertIsArray($physicalSize);
        self::assertSame([400, 300], array_slice($physicalSize, 0, 2));
        self::assertSame(6, exif_read_data($source)['Orientation'] ?? null);

        $storagePath = 'asset/1/image-thumb__1__dimension-probe/oriented.jpg';
        $assetStorage = $this->createMock(FilesystemOperator::class);
        $assetStorage->expects(self::never())->method('readStream');
        $thumbnailStorage = $this->createMock(FilesystemOperator::class);
        $thumbnailStorage->expects(self::once())
            ->method('readStream')
            ->with($storagePath)
            ->willReturnCallback(static function () use ($source) {
                $stream = fopen($source, 'rb');
                self::assertIsResource($stream);

                return $stream;
            });
        $this->installStorages($assetStorage, $thumbnailStorage);

        $asset = $this->image(300, 400, 'oriented.jpg');
        self::assertSame(['width' => 300, 'height' => 400], $asset->getDimensionsFromFile($source));
        $dao = $this->createMock(Asset\Dao::class);
        $dao->expects(self::once())
            ->method('addToThumbnailCache')
            ->with('dimension-probe', 'oriented.jpg', filesize($source), 400, 300);
        $dao->expects(self::once())
            ->method('getCachedThumbnail')
            ->willReturn(['width' => 400, 'height' => 300]);
        $asset->setDao($dao);

        $config = $this->config([]);
        $config->setFormat('ORIGINAL');
        $thumbnail = new RealThumbnailImageThumbnailTraitProbe($asset, $config, $storagePath);

        self::assertSame(['width' => 400, 'height' => 300], $thumbnail->getDimensions());
        self::assertSame(400, $thumbnail->getRealWidth());
        self::assertSame(300, $thumbnail->getRealHeight());
    }

    public function testGeneratedExifThumbnailCacheDoesNotApplyOrientationTwice(): void
    {
        $this->requireImagickExtension();

        $source = $this->createExifOrientedJpegFixture(400, 300, 6);
        $this->requireImagickFixtureSupport($source);

        foreach ([true, false] as $preserveMetaData) {
            $adapter = new ImagickAdapter();
            $adapter->setPreserveMetaData($preserveMetaData);
            self::assertNotFalse($adapter->load($source, ['preserveColor' => true]));

            $asset = $this->image(300, 400, 'oriented.jpg');
            // Processor prepends this exact transformation for EXIF orientation 6.
            $config = $this->config([
                ['method' => 'rotate', 'arguments' => ['angle' => 90]],
            ]);
            $config->setPreserveMetaData($preserveMetaData);
            Processor::applyTransformations($adapter, $asset, $config, $config->getItems());

            $generatedThumbnail = $this->temporaryPath('.jpg');
            $adapter->save($generatedThumbnail, 'jpeg');
            $physicalSize = getimagesize($generatedThumbnail);
            self::assertIsArray($physicalSize);
            self::assertSame([300, 400], array_slice($physicalSize, 0, 2));

            $generatedExif = @exif_read_data($generatedThumbnail);
            $generatedOrientation = is_array($generatedExif) ? ($generatedExif['Orientation'] ?? null) : null;
            if ($preserveMetaData) {
                self::assertSame(6, $generatedOrientation);
            } else {
                self::assertNotSame(6, $generatedOrientation);
            }

            $dao = $this->createMock(Asset\Dao::class);
            $dao->expects(self::once())
                ->method('addToThumbnailCache')
                ->with(
                    'dimension-probe',
                    'generated.jpg',
                    filesize($generatedThumbnail),
                    300,
                    400
                );
            $asset->setDao($dao);
            $asset->addThumbnailFileToCache($generatedThumbnail, 'generated.jpg', $config);
        }
    }

    public function testReliableEstimateUsesRealTraitWithoutGenerationOrAssetStreamAccess(): void
    {
        $assetStorage = $this->createMock(FilesystemOperator::class);
        $assetStorage->expects(self::never())->method('readStream');
        $assetStorage->expects(self::never())->method('fileExists');
        $this->installAssetStorage($assetStorage);

        $asset = $this->image(400, 300, 'source.png');
        $config = $this->config([
            ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
        ]);
        $thumbnail = new RealAssetImageThumbnailTraitProbe($asset, $config);

        self::assertSame(['width' => 200, 'height' => 150], $thumbnail->getDimensions());
        self::assertSame(0, $thumbnail->generationCalls);
    }

    public function testIntegralNumericStringArgumentsRetainZeroIoEstimation(): void
    {
        $assetStorage = $this->createMock(FilesystemOperator::class);
        $assetStorage->expects(self::never())->method('readStream');
        $assetStorage->expects(self::never())->method('fileExists');
        $this->installAssetStorage($assetStorage);

        $cases = [
            'scale by width' => [
                [['method' => 'scaleByWidth', 'arguments' => ['width' => '200']]],
                ['width' => 200, 'height' => 150],
            ],
            'resize' => [
                [['method' => 'resize', 'arguments' => ['width' => '200', 'height' => '100']]],
                ['width' => 200, 'height' => 100],
            ],
            'percentage crop' => [
                [[
                    'method' => 'cropPercent',
                    'arguments' => ['width' => '50', 'height' => '50', 'x' => '0', 'y' => '0'],
                ]],
                ['width' => 200, 'height' => 150],
            ],
        ];

        foreach ($cases as $name => [$items, $expected]) {
            $config = $this->config($items);
            $thumbnail = new RealAssetImageThumbnailTraitProbe(
                $this->image(400, 300, 'source.png'),
                $config
            );

            self::assertSame($expected, $thumbnail->getDimensions(), $name);
            self::assertSame(0, $thumbnail->generationCalls, $name);
            self::assertSame($items, $config->getItems(), $name . ' config mutation');
        }
    }

    public function testInvalidNumericStringArgumentsFailClosed(): void
    {
        foreach ([
            'fractional numeric string' => '200.5',
            'out-of-range numeric string' => '9223372036854775808',
            'non-numeric string' => 'not-a-number',
        ] as $name => $width) {
            $thumbnail = $this->probe(
                $this->image(400, 300, 'source.png'),
                $this->config([
                    ['method' => 'scaleByWidth', 'arguments' => ['width' => $width]],
                ]),
                ['width' => 91, 'height' => 73]
            );

            self::assertSame(['width' => 91, 'height' => 73], $thumbnail->getDimensions(), $name);
            self::assertSame(1, $thumbnail->readDimensionsCalls, $name);
        }
    }

    public function testFailedAssetExtractionReadsAtMostOncePerDimensionsInvocation(): void
    {
        $source = $this->temporaryPath('.invalid');
        file_put_contents($source, 'not an image');
        $asset = $this->countingImage(null, null, 'source.invalid', $source);
        $config = $this->config([]);
        $config->setFormat('ORIGINAL');
        $thumbnail = new RealAssetImageThumbnailTraitProbe($asset, $config);

        $adapterCalls = 0;
        $longRunningHelper = new LongRunningHelper($this->createStub(ConnectionRegistry::class));
        $container = $this->createStub(\Symfony\Component\DependencyInjection\ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(static function (string $id) use (&$adapterCalls, $longRunningHelper): mixed {
                if ($id === AdapterInterface::class) {
                    ++$adapterCalls;

                    return new GD();
                }

                return $id === LongRunningHelper::class ? $longRunningHelper : null;
            });
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getContainer')->willReturn($container);
        PimcoreRuntime::setKernel($kernel);

        self::assertSame(['width' => null, 'height' => null], $thumbnail->getDimensions());
        self::assertSame(1, $asset->streamCalls);

        self::assertSame(['width' => null, 'height' => null], $thumbnail->getDimensions());
        self::assertSame(2, $asset->streamCalls);
        self::assertSame(2, $adapterCalls);
    }

    public function testPrintTiffOriginalUsesSourceDimensionsWithoutAssetRead(): void
    {
        foreach ([null, 2.0] as $highResolution) {
            $asset = $this->countingImage(400, 300, 'source.tiff');
            $config = $this->config([
                ['method' => 'tifforiginal', 'arguments' => []],
            ], $highResolution);
            $config->setFormat('PRINT');
            $thumbnail = new RealAssetImageThumbnailTraitProbe($asset, $config);

            self::assertSame(['width' => 400, 'height' => 300], $thumbnail->getDimensions());
            self::assertSame($highResolution === null ? 400 : 800, $thumbnail->getRealWidth());
            self::assertSame($highResolution === null ? 300 : 600, $thumbnail->getRealHeight());
            self::assertSame(0, $thumbnail->generationCalls);
            self::assertSame(0, $asset->streamCalls);
        }
    }

    public function testPrintTiffOriginalAppliesLogicalScalingWithCompatibleHighResolutionDimensions(): void
    {
        foreach ([null, 2.0] as $highResolution) {
            $asset = $this->countingImage(400, 300, 'source.tiff');
            $config = $this->config([
                ['method' => 'tifforiginal', 'arguments' => []],
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
            ], $highResolution);
            $config->setFormat('PRINT');
            $thumbnail = new RealAssetImageThumbnailTraitProbe($asset, $config);

            self::assertSame(['width' => 200, 'height' => 150], $thumbnail->getDimensions());
            self::assertSame($highResolution === null ? 200 : 400, $thumbnail->getRealWidth());
            self::assertSame($highResolution === null ? 150 : 300, $thumbnail->getRealHeight());
            self::assertSame(0, $thumbnail->generationCalls);
            self::assertSame(0, $asset->streamCalls);
        }
    }

    public function testProcessorOwnsOriginalAssetOutputRouting(): void
    {
        $printConfig = $this->config([
            ['method' => 'tifforiginal', 'arguments' => []],
        ]);
        $printConfig->setFormat('PRINT');

        self::assertTrue(Processor::usesOriginalAssetOutput($this->image(400, 300, 'source.tif'), $printConfig));
        self::assertTrue(Processor::usesOriginalAssetOutput($this->image(400, 300, 'source.tiff'), $printConfig));
        self::assertTrue(Processor::usesOriginalAssetOutput($this->image(400, 300, 'source.svg'), $printConfig));
        self::assertFalse(Processor::usesOriginalAssetOutput($this->image(400, 300, 'source.TIFF'), $printConfig));
        self::assertFalse(Processor::usesOriginalAssetOutput($this->image(400, 300, 'source.SVG'), $printConfig));
        self::assertFalse(Processor::usesOriginalAssetOutput($this->image(400, 300, 'source.png'), $printConfig));

        $printConfig->setFormat('ORIGINAL');
        self::assertFalse(Processor::usesOriginalAssetOutput($this->image(400, 300, 'source.tiff'), $printConfig));
    }

    public function testAdminPrintTiffDoesNotUseOriginalAssetOutput(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['pimcore_admin' => '1']));
        $requestHelper = new RequestHelper($requestStack, new RequestContext());
        $container = $this->createStub(\Symfony\Component\DependencyInjection\ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(static fn (string $id) => match ($id) {
                RequestHelper::class => $requestHelper,
                'request_stack' => $requestStack,
                default => null,
            });
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getContainer')->willReturn($container);
        PimcoreRuntime::setKernel($kernel);

        $config = $this->config([
            ['method' => 'tifforiginal', 'arguments' => []],
        ]);
        $config->setFormat('PRINT');

        self::assertFalse(Processor::usesOriginalAssetOutput($this->image(400, 300, 'source.tiff'), $config));
    }

    public function testPrintTiffOriginalWithoutStoredDimensionsUsesRealAssetFallback(): void
    {
        $source = $this->createTiffFixture(400, 300);
        $assetStorage = $this->createMock(FilesystemOperator::class);
        $assetStorage->expects(self::once())
            ->method('readStream')
            ->with('source.tiff')
            ->willReturnCallback(static function () use ($source) {
                $stream = fopen($source, 'rb');
                self::assertIsResource($stream);

                return $stream;
            });
        $this->installAssetStorage($assetStorage);

        $asset = $this->image(null, null, 'source.tiff');
        $config = $this->config([
            ['method' => 'tifforiginal', 'arguments' => []],
        ], 2.0);
        $config->setFormat('PRINT');
        $thumbnail = new RealAssetImageThumbnailTraitProbe($asset, $config);

        self::assertSame(['width' => 400, 'height' => 300], $thumbnail->getDimensions());
        self::assertSame(800, $thumbnail->getRealWidth());
        self::assertSame(600, $thumbnail->getRealHeight());
        self::assertSame(1, $thumbnail->generationCalls);
        self::assertNull($asset->getCustomSetting('imageWidth'));
        self::assertNull($asset->getCustomSetting('imageHeight'));
    }

    public function testSourceSvgWithoutStoredDimensionsReadsSourceOnceWithoutGeneration(): void
    {
        $source = $this->createRasterFixture(400, 300);
        $asset = $this->countingImage(
            null,
            null,
            'source.svg',
            $source,
            ['width' => 400, 'height' => 300]
        );
        $config = $this->config([
            ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
        ]);
        $config->setFormat('SOURCE');
        $thumbnail = new RealAssetImageThumbnailTraitProbe($asset, $config);

        self::assertSame(['width' => 200, 'height' => 150], $thumbnail->getDimensions());
        self::assertSame(0, $thumbnail->generationCalls);
        self::assertSame(1, $asset->streamCalls);
        self::assertSame(1, $asset->dimensionsFromFileCalls);
        self::assertNull($asset->getCustomSetting('imageWidth'));
        self::assertNull($asset->getCustomSetting('imageHeight'));
    }

    public function testPrintSvgWithoutStoredDimensionsPreservesExtensionForRealAssetFallback(): void
    {
        $this->requireImagickExtension();

        $source = $this->createSvgFixture(192, 336);
        $this->requireImagickFixtureSupport($source);
        $this->installImageAdapter(new ImagickAdapter());

        $assetStorage = $this->createMock(FilesystemOperator::class);
        $assetStorage->expects(self::once())
            ->method('readStream')
            ->with('source.svg')
            ->willReturnCallback(static function () use ($source) {
                $sourceStream = fopen($source, 'rb');
                self::assertIsResource($sourceStream);
                // Flysystem can return a tmpfile()-backed stream whose URI is extensionless and disappears as soon as the stream is closed.
                $stream = tmpfile();
                self::assertIsResource($stream);
                stream_copy_to_stream($sourceStream, $stream);
                fclose($sourceStream);

                return $stream;
            });
        $this->installAssetStorage($assetStorage);

        $asset = $this->image(null, null, 'source.svg');
        $config = $this->config([
            ['method' => 'scaleByWidth', 'arguments' => ['width' => 100]],
        ]);
        $config->setFormat('PRINT');
        $thumbnail = new RealAssetImageThumbnailTraitProbe($asset, $config);

        self::assertSame(['width' => 100, 'height' => 175], $thumbnail->getDimensions());
        self::assertSame(0, $thumbnail->generationCalls);
        self::assertNull($asset->getCustomSetting('imageWidth'));
        self::assertNull($asset->getCustomSetting('imageHeight'));
    }

    public function testTiffOriginalOutsideProcessorOriginalAssetBranchFallsBack(): void
    {
        $asset = $this->image(400, 300, 'source.tiff');
        $config = $this->config([
            ['method' => 'tifforiginal', 'arguments' => []],
        ]);
        $config->setFormat('PNG');
        $thumbnail = $this->probe($asset, $config, ['width' => 91, 'height' => 73]);

        self::assertSame(['width' => 91, 'height' => 73], $thumbnail->getDimensions());
        self::assertSame(1, $thumbnail->readDimensionsCalls);
    }
}
