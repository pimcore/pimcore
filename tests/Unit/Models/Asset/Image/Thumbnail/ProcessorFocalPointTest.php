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

use Pimcore\Image\Adapter;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Model\Asset\Image\Thumbnail\Processor;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionMethod;

/**
 * Regression tests for the focal point injection of cover transformations.
 *
 * `positioning` is an optional argument of the cover transformation - thumbnail
 * definitions written by hand (config files) frequently omit it. The focal point
 * used to be injected while iterating the configured arguments, so it was silently
 * dropped for those definitions and the image was cropped centered instead.
 */
class ProcessorFocalPointTest extends TestCase
{
    private function image(?float $focalPointX, ?float $focalPointY): Image
    {
        $image = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomSetting', 'getWidth', 'getHeight'])
            ->getMock();
        $image->method('getWidth')->willReturn(3000);
        $image->method('getHeight')->willReturn(2000);
        $image->method('getCustomSetting')->willReturnCallback(
            static fn (string $key) => match ($key) {
                'focalPointX' => $focalPointX,
                'focalPointY' => $focalPointY,
                default => null,
            },
        );

        return $image;
    }

    private function config(array $coverArguments): Config
    {
        $config = new Config();
        $config->setItems([
            ['method' => 'cover', 'arguments' => $coverArguments],
        ]);

        return $config;
    }

    private function adapter(): Adapter
    {
        return new class() extends Adapter {
            /** @var list<array{int, int, array|string|null, bool}> */
            public array $coverCalls = [];

            public function cover(
                int $width,
                int $height,
                array|string|null $orientation = 'center',
                bool $forceResize = false
            ): static {
                $this->coverCalls[] = [$width, $height, $orientation, $forceResize];

                return $this;
            }

            public function load(string $imagePath, array $options = []): static|false
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
                return 'jpeg';
            }

            public function supportsFormat(string $format, bool $force = false): bool
            {
                return true;
            }
        };
    }

    private function applyTransformations(Adapter $adapter, Image $asset, Config $config): void
    {
        $method = new ReflectionMethod(Processor::class, 'applyTransformations');
        $method->invoke(null, $adapter, $asset, $config, $config->getItems());
    }

    public function testFocalPointIsAppliedWithoutConfiguredPositioning(): void
    {
        $adapter = $this->adapter();
        $config = $this->config(['width' => 400, 'height' => 400]);

        $this->applyTransformations($adapter, $this->image(80.5, 20.25), $config);

        self::assertSame(
            [[400, 400, ['x' => 80.5, 'y' => 20.25], false]],
            $adapter->coverCalls
        );
    }

    public function testFocalPointOverrulesConfiguredPositioning(): void
    {
        $adapter = $this->adapter();
        $config = $this->config(['width' => 400, 'height' => 400, 'positioning' => 'topleft']);

        $this->applyTransformations($adapter, $this->image(80.5, 20.25), $config);

        self::assertSame(
            [[400, 400, ['x' => 80.5, 'y' => 20.25], false]],
            $adapter->coverCalls
        );
    }

    public function testFocalPointOnTheEdgeIsApplied(): void
    {
        // A focal point on the left/top edge has the coordinate 0 - it is a focal point like any
        // other and must not be mistaken for "no focal point" by a truthiness check.
        $adapter = $this->adapter();
        $config = $this->config(['width' => 400, 'height' => 400]);

        $this->applyTransformations($adapter, $this->image(0.0, 0.0), $config);

        self::assertSame(
            [[400, 400, ['x' => 0.0, 'y' => 0.0], false]],
            $adapter->coverCalls
        );
    }

    public function testIncompleteFocalPointIsIgnored(): void
    {
        // Half a focal point is no focal point - injecting it would crop from the top edge
        // instead of keeping the configured positioning.
        $adapter = $this->adapter();
        $config = $this->config(['width' => 400, 'height' => 400, 'positioning' => 'topright']);

        $this->applyTransformations($adapter, $this->image(80.5, null), $config);

        self::assertSame(
            [[400, 400, 'topright', false]],
            $adapter->coverCalls
        );
    }

    public function testConfiguredPositioningIsKeptWithoutFocalPoint(): void
    {
        $adapter = $this->adapter();
        $config = $this->config(['width' => 400, 'height' => 400, 'positioning' => 'topleft']);

        $this->applyTransformations($adapter, $this->image(null, null), $config);

        self::assertSame(
            [[400, 400, 'topleft', false]],
            $adapter->coverCalls
        );
    }

    public function testCoverWithoutPositioningIsCenteredWithoutFocalPoint(): void
    {
        $adapter = $this->adapter();
        $config = $this->config(['width' => 400, 'height' => 400]);

        $this->applyTransformations($adapter, $this->image(null, null), $config);

        self::assertSame(
            [[400, 400, 'center', false]],
            $adapter->coverCalls
        );
    }
}
