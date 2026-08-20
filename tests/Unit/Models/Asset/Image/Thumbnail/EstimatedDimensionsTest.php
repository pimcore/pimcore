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

use Doctrine\Persistence\ConnectionRegistry;
use Pimcore as PimcoreRuntime;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Image\Adapter;
use Pimcore\Image\Adapter\GD;
use Pimcore\Image\AdapterInterface;
use Pimcore\Model\Asset\Image\Thumbnail;
use Pimcore\Tests\Unit\Models\Asset\Thumbnail\ImageThumbnailDimensionTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Regression coverage for dimension estimation before thumbnail file access.
 *
 * The probe deliberately reports that a thumbnail exists.
 * Reliable estimates must therefore avoid both exists() and readDimensionsFromFile(), while configurations that cannot be reproduced exactly must use the file fallback exactly once.
 */
class EstimatedDimensionsTest extends ImageThumbnailDimensionTestCase
{
    public function testProjectSpecificImageAdapterFailsClosedToFileInspection(): void
    {
        $this->installImageAdapter(new ProjectSpecificImageAdapter());

        $asset = $this->image(400, 300);
        $config = $this->config([
            ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
        ]);
        self::assertSame([], $config->getEstimatedDimensions($asset));

        $thumbnail = $this->probe($asset, $config, ['width' => 73, 'height' => 41]);
        self::assertSame(['width' => 73, 'height' => 41], $thumbnail->getDimensions());
        self::assertSame(1, $thumbnail->readDimensionsCalls);
    }

    public function testProjectSpecificAdapterDoesNotBlockOriginalAssetLogicalDimensions(): void
    {
        $this->installImageAdapter(new ProjectSpecificImageAdapter());

        foreach ([
            'SVG pass-through' => [
                'source.svg',
                [['method' => 'scaleByWidth', 'arguments' => ['width' => 200]]],
            ],
            'TIFF original marker' => [
                'source.tiff',
                [
                    ['method' => 'tifforiginal', 'arguments' => []],
                    ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
                ],
            ],
        ] as $name => [$filename, $items]) {
            $asset = $this->image(400, 300, $filename);
            $config = $this->config($items);
            $config->setFormat('PRINT');

            self::assertSame(
                ['width' => 200, 'height' => 150],
                $config->getEstimatedDimensions($asset),
                $name
            );

            $thumbnail = $this->probe($asset, $config, ['width' => 73, 'height' => 41]);
            self::assertSame(['width' => 200, 'height' => 150], $thumbnail->getDimensions(), $name);
            self::assertSame(0, $thumbnail->readDimensionsCalls, $name);
        }
    }

    public function testGdSubclassFailsClosedToFileInspection(): void
    {
        $this->installImageAdapter(new ProjectSpecificGdAdapter());

        $asset = $this->image(400, 300, 'source.png');
        $config = $this->config([
            ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
        ]);
        self::assertSame([], $config->getEstimatedDimensions($asset));

        $thumbnail = $this->probe($asset, $config, ['width' => 91, 'height' => 67]);
        self::assertSame(['width' => 91, 'height' => 67], $thumbnail->getDimensions());
        self::assertSame(1, $thumbnail->readDimensionsCalls);
    }

    public function testScaleByWidthUsesStoredSourceDimensionsWithoutFileAccess(): void
    {
        $thumbnail = $this->probe(
            $this->image(400, 300),
            $this->config([
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
            ])
        );

        self::assertSame(['width' => 200, 'height' => 150], $thumbnail->getDimensions());
        self::assertSame(0, $thumbnail->existsCalls);
        self::assertSame(0, $thumbnail->readDimensionsCalls);
    }

    public function testStoredSourceAndSourceIndependentDimensionsAvoidFileAccess(): void
    {
        $cases = [
            'untransformed source' => [
                $this->image(400, 300),
                [],
                null,
                ['width' => 400, 'height' => 300],
            ],
            '1x1 without stored source dimensions' => [
                $this->image(null, null),
                [['method' => '1x1_pixel', 'arguments' => []]],
                null,
                ['width' => 1, 'height' => 1],
            ],
            'scale by height' => [
                $this->image(400, 300),
                [['method' => 'scaleByHeight', 'arguments' => ['height' => 150]]],
                null,
                ['width' => 200, 'height' => 150],
            ],
            'frame' => [
                $this->image(400, 300),
                [['method' => 'frame', 'arguments' => ['width' => 500, 'height' => 400]]],
                null,
                ['width' => 500, 'height' => 400],
            ],
        ];

        foreach ($cases as $name => [$asset, $items, $highResolution, $expected]) {
            $thumbnail = $this->probe($asset, $this->config($items, $highResolution));

            self::assertSame($expected, $thumbnail->getDimensions(), $name);
            self::assertSame(0, $thumbnail->existsCalls, $name);
            self::assertSame(0, $thumbnail->readDimensionsCalls, $name);
        }
    }

    public function testReliableTransformationPipelinesAvoidFileAccess(): void
    {
        $cases = [
            'contain' => [
                [['method' => 'contain', 'arguments' => ['width' => 100, 'height' => 100]]],
                ['width' => 100, 'height' => 75],
            ],
            'cover' => [
                [['method' => 'cover', 'arguments' => ['width' => 100, 'height' => 100]]],
                ['width' => 100, 'height' => 100],
            ],
            'safe crop' => [
                [['method' => 'crop', 'arguments' => ['x' => 10, 'y' => 20, 'width' => 100, 'height' => 50]]],
                ['width' => 100, 'height' => 50],
            ],
            'crop percent' => [
                [['method' => 'cropPercent', 'arguments' => ['width' => 50, 'height' => 25, 'x' => 10, 'y' => 20]]],
                ['width' => 200, 'height' => 75],
            ],
            'dimension-neutral mixed pipeline' => [
                [
                    ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
                    ['method' => 'setBackgroundColor', 'arguments' => ['color' => '#ffffff']],
                    ['method' => 'grayscale', 'arguments' => []],
                    ['method' => 'sepia', 'arguments' => []],
                    ['method' => 'sharpen', 'arguments' => ['radius' => 0, 'sigma' => 1, 'amount' => 1, 'threshold' => 0]],
                    ['method' => 'mirror', 'arguments' => ['mode' => 'horizontal']],
                ],
                ['width' => 200, 'height' => 150],
            ],
        ];

        foreach ($cases as $name => [$items, $expected]) {
            $thumbnail = $this->probe($this->image(400, 300), $this->config($items));

            self::assertSame($expected, $thumbnail->getDimensions(), $name);
            self::assertSame(0, $thumbnail->existsCalls, $name);
            self::assertSame(0, $thumbnail->readDimensionsCalls, $name);
        }
    }

    public function testAttainableHighResolutionUsesEstimatedNominalAndRealDimensions(): void
    {
        $thumbnail = $this->probe(
            $this->image(800, 600),
            $this->config([
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
            ], 2.0)
        );

        self::assertSame(['width' => 200, 'height' => 150], $thumbnail->getDimensions());
        self::assertSame(400, $thumbnail->getRealWidth());
        self::assertSame(300, $thumbnail->getRealHeight());
        self::assertSame(0, $thumbnail->existsCalls);
        self::assertSame(0, $thumbnail->readDimensionsCalls);
    }

    public function testFractionalHighResolutionMatchesProcessorRounding(): void
    {
        $thumbnail = $this->probe(
            $this->image(800, 600),
            $this->config([
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 201]],
                ['method' => 'grayscale', 'arguments' => []],
            ], 1.5)
        );

        self::assertSame(['width' => 201, 'height' => 150], $thumbnail->getDimensions());
        self::assertSame(302, $thumbnail->getRealWidth());
        self::assertSame(226, $thumbnail->getRealHeight());
        self::assertSame(0, $thumbnail->readDimensionsCalls);
    }

    public function testCappedHighResolutionUsesProcessorNormalizedEstimate(): void
    {
        $thumbnail = $this->probe(
            $this->image(300, 225),
            $this->config([
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
            ], 2.0),
            ['width' => 300, 'height' => 225]
        );

        self::assertSame(['width' => 150, 'height' => 112], $thumbnail->getDimensions());

        self::assertSame(300, $thumbnail->getRealWidth());
        self::assertSame(225, $thumbnail->getRealHeight());
        self::assertSame(0, $thumbnail->readDimensionsCalls);
    }

    public function testScaleRoundingMatchesImageAdapter(): void
    {
        $thumbnail = $this->probe(
            $this->image(4, 3),
            $this->config([
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 2]],
            ])
        );

        self::assertSame(['width' => 2, 'height' => 1], $thumbnail->getDimensions());
        self::assertSame(0, $thumbnail->readDimensionsCalls);
    }

    public function testOutOfBoundsCropFallsBackToFile(): void
    {
        $thumbnail = $this->probe(
            $this->image(100, 80),
            $this->config([
                ['method' => 'crop', 'arguments' => ['x' => 90, 'y' => 70, 'width' => 50, 'height' => 40]],
            ]),
            ['width' => 10, 'height' => 10]
        );

        self::assertSame(['width' => 10, 'height' => 10], $thumbnail->getDimensions());
        self::assertSame(1, $thumbnail->readDimensionsCalls);
    }

    public function testOriginalFormatDeclinesTransformationEstimate(): void
    {
        $asset = $this->image(400, 300);
        $config = $this->config([
            ['method' => 'resize', 'arguments' => ['width' => 200, 'height' => 100]],
        ]);
        $config->setFormat('ORIGINAL');

        self::assertSame([], $config->getEstimatedDimensions($asset));
    }

    public function testSourceSvgPassThroughRetainsLogicalTransformations(): void
    {
        $cases = [
            'scale by width' => [
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 202]],
                null,
                ['width' => 202, 'height' => 152],
                ['width' => 202, 'height' => 152],
            ],
            'scale by height' => [
                ['method' => 'scaleByHeight', 'arguments' => ['height' => 151]],
                null,
                ['width' => 201, 'height' => 151],
                ['width' => 201, 'height' => 151],
            ],
            'resize' => [
                ['method' => 'resize', 'arguments' => ['width' => 200, 'height' => 100]],
                null,
                ['width' => 200, 'height' => 100],
                ['width' => 200, 'height' => 100],
            ],
            'fractional high resolution' => [
                ['method' => 'scaleByHeight', 'arguments' => ['height' => 151]],
                1.5,
                ['width' => 200, 'height' => 150],
                ['width' => 301, 'height' => 226],
            ],
        ];

        foreach ($cases as $name => [$transformation, $highResolution, $displayDimensions, $realDimensions]) {
            $asset = $this->image(400, 300, 'source.svg');
            $config = $this->config([$transformation], $highResolution);
            $config->setFormat('SOURCE');

            self::assertTrue($config->usesOriginalSvgOutput($asset), $name);
            self::assertSame($realDimensions, $config->getEstimatedDimensions($asset), $name);

            $thumbnail = $this->probe($asset, $config);
            self::assertSame($displayDimensions, $thumbnail->getDimensions(), $name);
            self::assertSame($realDimensions['width'], $thumbnail->getRealWidth(), $name);
            self::assertSame($realDimensions['height'], $thumbnail->getRealHeight(), $name);
            self::assertSame(0, $thumbnail->readDimensionsCalls, $name);
        }
    }

    public function testSourceSvgShortcutDeclinesWhenRouteDoesNotReturnSource(): void
    {
        $cases = [
            'rasterization enabled' => ['source.svg', true, [
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
            ]],
            'unsupported transformation' => ['source.svg', false, [
                ['method' => 'crop', 'arguments' => ['x' => 0, 'y' => 0, 'width' => 50, 'height' => 50]],
            ]],
            'upper-case extension' => ['source.SVG', false, [
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
            ]],
        ];

        foreach ($cases as $name => [$filename, $rasterizeSvg, $items]) {
            $asset = $this->image(400, 300, $filename);
            $config = $this->config($items);
            $config->setFormat('SOURCE');
            $config->setRasterizeSVG($rasterizeSvg);

            self::assertFalse($config->usesOriginalSvgOutput($asset), $name);
            self::assertSame([], $config->getEstimatedDimensions($asset), $name);

            $thumbnail = $this->probe($asset, $config, ['width' => 73, 'height' => 59]);
            self::assertSame(['width' => 73, 'height' => 59], $thumbnail->getDimensions(), $name);
            self::assertSame(1, $thumbnail->readDimensionsCalls, $name);
        }
    }

    public function testPrintSvgPassThroughRetainsLogicalTransformationsAndHighResolutionDimensions(): void
    {
        $cases = [
            'scale by width' => [
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
                ['width' => 200, 'height' => 150],
            ],
            'scale by height' => [
                ['method' => 'scaleByHeight', 'arguments' => ['height' => 150]],
                ['width' => 200, 'height' => 150],
            ],
            'resize' => [
                ['method' => 'resize', 'arguments' => ['width' => 200, 'height' => 100]],
                ['width' => 200, 'height' => 100],
            ],
        ];

        foreach ($cases as $name => [$transformation, $displayDimensions]) {
            foreach ([null, 2.0] as $highResolution) {
                $asset = $this->image(400, 300, 'source.svg');
                $config = $this->config([$transformation], $highResolution);
                $config->setFormat('PRINT');
                $realDimensions = $highResolution === null
                    ? $displayDimensions
                    : [
                        'width' => $displayDimensions['width'] * 2,
                        'height' => $displayDimensions['height'] * 2,
                    ];

                self::assertSame($realDimensions, $config->getEstimatedDimensions($asset), $name);

                $thumbnail = $this->probe($asset, $config);
                self::assertSame($displayDimensions, $thumbnail->getDimensions(), $name);
                self::assertSame($realDimensions['width'], $thumbnail->getRealWidth(), $name);
                self::assertSame($realDimensions['height'], $thumbnail->getRealHeight(), $name);
                self::assertSame(0, $thumbnail->readDimensionsCalls, $name);
            }
        }
    }

    public function testFractionalPassThroughHighResolutionMatchesLegacyTruncation(): void
    {
        foreach ([
            'scale by width' => [
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 201]],
                ['width' => 200, 'height' => 150],
                ['width' => 301, 'height' => 226],
            ],
            'scale by height' => [
                ['method' => 'scaleByHeight', 'arguments' => ['height' => 151]],
                ['width' => 200, 'height' => 150],
                ['width' => 301, 'height' => 226],
            ],
        ] as $name => [$transformation, $displayDimensions, $realDimensions]) {
            $asset = $this->image(400, 300, 'source.svg');
            $config = $this->config([$transformation], 1.5);
            $config->setFormat('PRINT');

            self::assertSame($realDimensions, $config->getEstimatedDimensions($asset), $name);

            $thumbnail = $this->probe($asset, $config);
            self::assertSame($displayDimensions, $thumbnail->getDimensions(), $name);
            self::assertSame($realDimensions['width'], $thumbnail->getRealWidth(), $name);
            self::assertSame($realDimensions['height'], $thumbnail->getRealHeight(), $name);
            self::assertSame(0, $thumbnail->readDimensionsCalls, $name);
        }
    }

    public function testPassThroughUsesLegacyLogicalHtmlDimensionRules(): void
    {
        $cases = [
            'rounded proportional scale' => [
                'source.svg',
                [['method' => 'scaleByWidth', 'arguments' => ['width' => 202]]],
                ['width' => 202, 'height' => 152],
            ],
            'absolute crop target outside source canvas' => [
                'source.svg',
                [['method' => 'crop', 'arguments' => ['x' => 390, 'y' => 290, 'width' => 200, 'height' => 100]]],
                ['width' => 200, 'height' => 100],
            ],
            'TIFF original marker and cover upscale' => [
                'source.tiff',
                [
                    ['method' => 'tifforiginal', 'arguments' => []],
                    ['method' => 'cover', 'arguments' => ['width' => 800, 'height' => 600]],
                ],
                ['width' => 800, 'height' => 600],
            ],
            'malformed visual-only operation after scale' => [
                'source.svg',
                [
                    ['method' => 'scaleByWidth', 'arguments' => ['width' => 202]],
                    ['method' => 'setBackgroundColor', 'arguments' => []],
                ],
                ['width' => 202, 'height' => 152],
            ],
        ];

        foreach ($cases as $name => [$filename, $items, $expected]) {
            $asset = $this->image(400, 300, $filename);
            $config = $this->config($items);
            $config->setFormat('PRINT');

            self::assertSame($expected, $config->getEstimatedDimensions($asset), $name);

            $thumbnail = $this->probe($asset, $config);
            self::assertSame($expected, $thumbnail->getDimensions(), $name);
            self::assertSame(0, $thumbnail->readDimensionsCalls, $name);
        }
    }

    public function testGeneratedRasterCoverUpscaleWithoutForceStillFallsBack(): void
    {
        $thumbnail = $this->probe(
            $this->image(400, 300, 'source.png'),
            $this->config([
                ['method' => 'cover', 'arguments' => ['width' => 800, 'height' => 600]],
            ]),
            ['width' => 400, 'height' => 300]
        );

        self::assertSame(['width' => 400, 'height' => 300], $thumbnail->getDimensions());
        self::assertSame(1, $thumbnail->readDimensionsCalls);
    }

    public function testSvgSourceOutputHtmlUsesLogicalDimensions(): void
    {
        $eventDispatcher = new EventDispatcher();
        $requestStack = new RequestStack();
        $longRunningHelper = new LongRunningHelper($this->createStub(ConnectionRegistry::class));
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(static fn (string $id) => match ($id) {
                'event_dispatcher' => $eventDispatcher,
                'request_stack' => $requestStack,
                AdapterInterface::class => new GD(),
                LongRunningHelper::class => $longRunningHelper,
                default => null,
            });
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getContainer')->willReturn($container);
        PimcoreRuntime::setKernel($kernel);

        $asset = $this->countingImage(400, 300, 'source.svg');
        $asset->setMimeType('image/png');
        $config = $this->config([
            ['method' => 'scaleByWidth', 'arguments' => ['width' => 202]],
        ]);
        $config->setFormat('SOURCE');
        self::assertTrue($config->usesOriginalSvgOutput($asset));
        $sourceThumbnail = new Thumbnail($asset, $config, false);
        $sourceHtml = $sourceThumbnail->getImageTag([
            'alt' => 'source',
            'title' => 'source',
            'disableAutoCopyright' => true,
        ]);

        self::assertStringContainsString('src="source.svg"', $sourceHtml);
        self::assertStringContainsString('width="202"', $sourceHtml);
        self::assertStringContainsString('height="152"', $sourceHtml);
        self::assertSame('asset', $sourceThumbnail->getPathReference()['type']);
        self::assertSame(0, $asset->streamCalls);
        self::assertSame(0, $asset->dimensionsFromFileCalls);

        $config->setFormat('PRINT');
        $thumbnail = new Thumbnail($asset, $config, false);

        $html = $thumbnail->getImageTag([
            'alt' => 'source',
            'title' => 'source',
            'disableAutoCopyright' => true,
        ]);

        self::assertStringContainsString('src="source.svg"', $html);
        self::assertStringContainsString('width="202"', $html);
        self::assertStringContainsString('height="152"', $html);

        $source = $this->createRasterFixture(400, 300);
        $config->setFormat('SOURCE');
        $sourceMissingAsset = $this->countingImage(
            null,
            null,
            'source.svg',
            $source,
            ['width' => 400, 'height' => 300]
        );
        $sourceMissingThumbnail = new Thumbnail($sourceMissingAsset, $config, false);
        $sourceMissingHtml = $sourceMissingThumbnail->getImageTag([
            'alt' => 'source',
            'title' => 'source',
            'disableAutoCopyright' => true,
        ]);

        self::assertStringContainsString('src="source.svg"', $sourceMissingHtml);
        self::assertStringContainsString('width="202"', $sourceMissingHtml);
        self::assertStringContainsString('height="152"', $sourceMissingHtml);
        self::assertSame('asset', $sourceMissingThumbnail->getPathReference()['type']);
        self::assertSame(1, $sourceMissingAsset->streamCalls);
        self::assertSame(1, $sourceMissingAsset->dimensionsFromFileCalls);
        self::assertNull($sourceMissingAsset->getCustomSetting('imageWidth'));
        self::assertNull($sourceMissingAsset->getCustomSetting('imageHeight'));

        $config->setFormat('PRINT');
        $missingAsset = $this->countingImage(
            null,
            null,
            'source.svg',
            $source,
            ['width' => 400, 'height' => 300]
        );
        $missingAsset->setMimeType('image/png');
        $missingThumbnail = new Thumbnail($missingAsset, $config, false);
        $missingHtml = $missingThumbnail->getImageTag([
            'alt' => 'source',
            'title' => 'source',
            'disableAutoCopyright' => true,
        ]);

        self::assertStringContainsString('src="source.svg"', $missingHtml);
        self::assertStringContainsString('width="202"', $missingHtml);
        self::assertStringContainsString('height="152"', $missingHtml);
        self::assertSame(1, $missingAsset->streamCalls);
        self::assertSame(1, $missingAsset->dimensionsFromFileCalls);
        self::assertNull($missingAsset->getCustomSetting('imageWidth'));
        self::assertNull($missingAsset->getCustomSetting('imageHeight'));

        $cropConfig = $this->config([[
            'method' => 'cropPercent',
            'arguments' => ['width' => 50, 'height' => 50, 'x' => 0, 'y' => 0],
        ]]);
        $cropConfig->setFormat('PRINT');

        $cropHtml = (new Thumbnail($this->image(400, 300, 'source.svg'), $cropConfig, false))->getImageTag([
            'alt' => 'source',
            'title' => 'source',
            'disableAutoCopyright' => true,
        ]);
        self::assertStringContainsString('width="200"', $cropHtml);
        self::assertStringContainsString('height="150"', $cropHtml);

        $missingCropAsset = $this->countingImage(
            null,
            null,
            'source.svg',
            $source,
            ['width' => 400, 'height' => 300]
        );
        $missingCropHtml = (new Thumbnail($missingCropAsset, $cropConfig, false))->getImageTag([
            'alt' => 'source',
            'title' => 'source',
            'disableAutoCopyright' => true,
        ]);
        self::assertStringContainsString('width="200"', $missingCropHtml);
        self::assertStringContainsString('height="150"', $missingCropHtml);
        self::assertSame(1, $missingCropAsset->streamCalls);
        self::assertSame(1, $missingCropAsset->dimensionsFromFileCalls);
    }

    public function testDimensionEstimationVectorClassificationIsCaseInsensitiveWithoutChangingPublicContract(): void
    {
        foreach (['source.svg', 'source.pdf'] as $filename) {
            $asset = $this->image(100, 100, $filename);
            self::assertTrue($asset->isVectorGraphic(), $filename);
            self::assertTrue($asset->getVectorGraphicStateForDimensionEstimation(), $filename);
        }

        foreach (['source.SVG', 'source.PDF'] as $filename) {
            $asset = $this->image(100, 100, $filename);
            self::assertFalse($asset->isVectorGraphic(), $filename);
            self::assertTrue($asset->getVectorGraphicStateForDimensionEstimation(), $filename);
        }

        $vectorThumbnail = $this->probe(
            $this->image(100, 100, 'source.SVG'),
            $this->config([
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
            ]),
            ['width' => 100, 'height' => 100]
        );
        self::assertSame(['width' => 100, 'height' => 100], $vectorThumbnail->getDimensions());
        self::assertSame(1, $vectorThumbnail->readDimensionsCalls);

        $rasterThumbnail = $this->probe(
            $this->image(100, 100, 'source.png'),
            $this->config([
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
            ])
        );
        self::assertSame(['width' => 100, 'height' => 100], $rasterThumbnail->getDimensions());

        $misnamedVector = $this->image(100, 100, 'source.bin');
        $misnamedVector->setMimeType('image/svg+xml');
        self::assertFalse($misnamedVector->isVectorGraphic());
        self::assertNull($misnamedVector->getVectorGraphicStateForDimensionEstimation());
        self::assertSame(
            [],
            $this->config([
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
            ])->getEstimatedDimensions($misnamedVector)
        );

        $unknownAsset = $this->image(100, 100, 'source.bin');
        self::assertNull($unknownAsset->getVectorGraphicStateForDimensionEstimation());
        self::assertSame(
            [],
            $this->config([
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
            ])->getEstimatedDimensions($unknownAsset)
        );

        $misnamedRaster = $this->image(100, 100, 'source.bin');
        $misnamedRaster->setMimeType('image/png');
        self::assertFalse($misnamedRaster->isVectorGraphic());
        self::assertNull($misnamedRaster->getVectorGraphicStateForDimensionEstimation());
        self::assertSame(
            [],
            $this->config([
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
            ])->getEstimatedDimensions($misnamedRaster)
        );
    }

    public function testConflictingVectorExtensionAndMimeEvidenceFallsBack(): void
    {
        foreach ([
            ['source.png', 'image/svg+xml'],
            ['source.svg', 'image/png'],
        ] as [$filename, $mimeType]) {
            $asset = $this->image(100, 100, $filename);
            $asset->setMimeType($mimeType);
            self::assertNull($asset->getVectorGraphicStateForDimensionEstimation(), $filename . ' + ' . $mimeType);

            $config = $this->config([
                ['method' => 'scaleByWidth', 'arguments' => ['width' => 200]],
            ]);
            $config->setRasterizeSVG(true);
            self::assertSame([], $config->getEstimatedDimensions($asset), $filename . ' + ' . $mimeType);

            $thumbnail = $this->probe($asset, $config, ['width' => 73, 'height' => 59]);
            self::assertSame(['width' => 73, 'height' => 59], $thumbnail->getDimensions());
            self::assertSame(1, $thumbnail->readDimensionsCalls);
        }
    }

    public function testGeneratedResizeRequiresDefinitivelyRasterSource(): void
    {
        $config = $this->config([
            ['method' => 'resize', 'arguments' => ['width' => 200, 'height' => 200]],
        ]);
        $config->setRasterizeSVG(true);

        $knownVector = $this->image(100, 100, 'source.svg');
        $knownVector->setMimeType('image/svg+xml');
        self::assertTrue($knownVector->getVectorGraphicStateForDimensionEstimation());
        self::assertSame([], $config->getEstimatedDimensions($knownVector));

        $knownVectorThumbnail = $this->probe($knownVector, $config, ['width' => 73, 'height' => 59]);
        self::assertSame(['width' => 73, 'height' => 59], $knownVectorThumbnail->getDimensions());
        self::assertSame(1, $knownVectorThumbnail->readDimensionsCalls);

        foreach ([
            'conflicting SVG extension' => ['source.svg', 'image/png'],
            'unknown extension with vector MIME' => ['source.bin', 'image/svg+xml'],
            'unknown extension with raster MIME' => ['source.bin', 'image/png'],
        ] as $name => [$filename, $mimeType]) {
            $asset = $this->image(100, 100, $filename);
            $asset->setMimeType($mimeType);

            self::assertNull($asset->getVectorGraphicStateForDimensionEstimation(), $name);
            self::assertSame([], $config->getEstimatedDimensions($asset), $name);

            $thumbnail = $this->probe($asset, $config, ['width' => 73, 'height' => 59]);
            self::assertSame(['width' => 73, 'height' => 59], $thumbnail->getDimensions(), $name);
            self::assertSame(1, $thumbnail->readDimensionsCalls, $name);
        }

        $rasterAsset = $this->image(100, 100, 'source.png');
        $rasterAsset->setMimeType('image/png');
        self::assertFalse($rasterAsset->getVectorGraphicStateForDimensionEstimation());
        self::assertSame(['width' => 200, 'height' => 200], $config->getEstimatedDimensions($rasterAsset));

        $rasterThumbnail = $this->probe($rasterAsset, $config, ['width' => 73, 'height' => 59]);
        self::assertSame(['width' => 200, 'height' => 200], $rasterThumbnail->getDimensions());
        self::assertSame(0, $rasterThumbnail->readDimensionsCalls);
    }
}

final class ProjectSpecificImageAdapter extends Adapter
{
    public function load(string $imagePath, array $options = []): static
    {
        return $this;
    }

    public function save(string $path, ?string $format = null, ?int $quality = null): static
    {
        return $this;
    }

    protected function destroy(): void
    {
    }

    public function getContentOptimizedFormat(): string
    {
        return 'png';
    }

    public function supportsFormat(string $format, bool $force = false): bool
    {
        return true;
    }
}

final class ProjectSpecificGdAdapter extends GD
{
    public function resize(int $width, int $height): static
    {
        return $this;
    }
}
