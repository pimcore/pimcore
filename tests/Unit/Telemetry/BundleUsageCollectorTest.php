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

namespace Pimcore\Tests\Unit\Telemetry;

use Pimcore\Telemetry\Snapshot\SnapshotCollectorInterface;
use Pimcore\Telemetry\Usage\BundleUsageCollector;
use Pimcore\Telemetry\Usage\BundleUsageProviderInterface;
use Pimcore\Tests\Support\Test\TestCase;
use RuntimeException;
use Throwable;

class BundleUsageCollectorTest extends TestCase
{
    private function provider(string $key, bool|Throwable $used): BundleUsageProviderInterface
    {
        return new class($key, $used) implements BundleUsageProviderInterface {
            public function __construct(private readonly string $key, private readonly bool|Throwable $used)
            {
            }

            public function getBundleKey(): string
            {
                return $this->key;
            }

            public function isUsed(): bool
            {
                if ($this->used instanceof Throwable) {
                    throw $this->used;
                }

                return $this->used;
            }
        };
    }

    public function testIsASnapshotCollectorInTheUsageNamespace(): void
    {
        $collector = new BundleUsageCollector([]);

        $this->assertInstanceOf(SnapshotCollectorInterface::class, $collector);
        $this->assertSame('usage', $collector->getNamespace());
    }

    public function testAggregatesProvidersToBundleKeyUsedMap(): void
    {
        $collector = new BundleUsageCollector([
            $this->provider('datahub', true),
            $this->provider('portal_engine', false),
        ]);

        $this->assertSame(['datahub' => true, 'portal_engine' => false], $collector->collect());
    }

    public function testThrowingProviderDegradesToFalse(): void
    {
        $collector = new BundleUsageCollector([
            $this->provider('ok', true),
            $this->provider('boom', new RuntimeException('kaboom')),
        ]);

        $this->assertSame(['ok' => true, 'boom' => false], $collector->collect());
    }

    public function testNoProvidersYieldsEmptyMap(): void
    {
        $this->assertSame([], (new BundleUsageCollector([]))->collect());
    }
}
