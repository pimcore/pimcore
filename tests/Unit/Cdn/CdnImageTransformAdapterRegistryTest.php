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

namespace Pimcore\Tests\Unit\Cdn;

use Pimcore\Cdn\CdnImageTransformAdapterRegistry;
use Pimcore\Cdn\ImageTransformAdapterInterface;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class CdnImageTransformAdapterRegistryTest extends TestCase
{
    private function locator(array $adapters): ContainerInterface
    {
        return new class($adapters) implements ContainerInterface {
            /** @param array<string, ImageTransformAdapterInterface> $adapters */
            public function __construct(private readonly array $adapters)
            {
            }

            public function has(string $id): bool
            {
                return isset($this->adapters[$id]);
            }

            public function get(string $id): ImageTransformAdapterInterface
            {
                return $this->adapters[$id];
            }
        };
    }

    public function testResolvesConfiguredOptimizer(): void
    {
        $fastly = $this->createMock(ImageTransformAdapterInterface::class);
        $fastly->method('buildUrl')->willReturn('https://cdn.example/x.jpg?width=10');
        $null = $this->createMock(ImageTransformAdapterInterface::class);

        $registry = new CdnImageTransformAdapterRegistry(
            $this->locator(['fastly' => $fastly, 'null' => $null]),
            'fastly',
            $this->createMock(LoggerInterface::class),
        );

        self::assertSame('https://cdn.example/x.jpg?width=10', $registry->buildUrl('/var/assets/x.jpg', ['width' => 10]));
    }

    public function testEmptyOptimizerResolvesToNull(): void
    {
        $null = $this->createMock(ImageTransformAdapterInterface::class);
        $null->method('buildUrl')->willReturn('/var/assets/x.jpg');

        $registry = new CdnImageTransformAdapterRegistry(
            $this->locator(['null' => $null]),
            '',
            $this->createMock(LoggerInterface::class),
        );

        self::assertSame('/var/assets/x.jpg', $registry->buildUrl('/var/assets/x.jpg', []));
    }

    public function testUnknownOptimizerLogsWarningAndFallsBackToNull(): void
    {
        $null = $this->createMock(ImageTransformAdapterInterface::class);
        $null->method('buildUrl')->willReturn('/var/assets/x.jpg');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $registry = new CdnImageTransformAdapterRegistry(
            $this->locator(['null' => $null]),
            'does-not-exist',
            $logger,
        );

        self::assertSame('/var/assets/x.jpg', $registry->buildUrl('/var/assets/x.jpg', []));
    }
}
