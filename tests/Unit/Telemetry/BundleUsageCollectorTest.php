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
    private function provider(string $key, bool|Throwable|null $used): BundleUsageProviderInterface
    {
        return new class($key, $used) implements BundleUsageProviderInterface {
            public function __construct(
                private readonly string $key,
                private readonly bool|Throwable|null $used,
            ) {
            }

            public function getBundleKey(): string
            {
                return $this->key;
            }

            public function isUsed(): ?bool
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

    /**
     * A provider that blew up knows nothing about adoption. Reporting `false` would be
     * indistinguishable from a genuine "installed but not used" - the one signal this namespace
     * exists to produce - so the key is omitted and stays unknown.
     */
    public function testAThrowingProviderIsOmittedRatherThanReportedUnused(): void
    {
        $collector = new BundleUsageCollector([
            $this->provider('ok', true),
            $this->provider('boom', new RuntimeException('kaboom')),
        ]);

        $this->assertSame(['ok' => true], $collector->collect());
    }

    /**
     * The case the null return exists for: several bundles keep their configuration in a
     * deployment-configurable location, so a provider that cannot reach its own repository must say
     * "cannot tell" rather than invent an adoption gap on every customer using the other target.
     */
    public function testANullAnsweringProviderIsOmittedRatherThanReportedUnused(): void
    {
        $collector = new BundleUsageCollector([
            $this->provider('resolvable', false),
            $this->provider('unresolvable', null),
        ]);

        $collected = $collector->collect();

        $this->assertSame(['resolvable' => false], $collected);
        $this->assertArrayNotHasKey('unresolvable', $collected, 'unknown must not become false');
    }

    public function testNoProvidersYieldsEmptyMap(): void
    {
        $this->assertSame([], (new BundleUsageCollector([]))->collect());
    }
}
