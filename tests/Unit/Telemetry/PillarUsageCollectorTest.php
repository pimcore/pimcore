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

use Doctrine\DBAL\Connection;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Telemetry\Snapshot\ActiveBundles;
use Pimcore\Telemetry\Snapshot\Bucketizer;
use Pimcore\Telemetry\Snapshot\ElementTypeCounts;
use Pimcore\Telemetry\Snapshot\PillarUsageCollector;
use Pimcore\Telemetry\Snapshot\SnapshotQueryRunner;
use Pimcore\Telemetry\Snapshot\Statistics\ElementKind;
use Pimcore\Telemetry\Snapshot\Statistics\ElementStatisticsProviderInterface;
use Pimcore\Tests\Support\Test\TestCase;
use function str_contains;

// Stub bundles whose short class name carries the needle the collector matches on.
class PillarStubEcommerceFrameworkBundle
{
}
class PillarStubDataHubBundle
{
}

class PillarUsageCollectorTest extends TestCase
{
    public function testNamespaceIsPillars(): void
    {
        $this->assertSame('pillars', $this->collector([])->getNamespace());
    }

    public function testDerivesPerTypeMetricsFromTheStatisticsProviderAndIsContentNever(): void
    {
        $metrics = $this->collector([])->collect();

        // assets: total 42 (30+5+4+0+3); each per-type count comes from the provider's type counts.
        $this->assertSame('11-50', $metrics['asset_count_bucket']);          // bucket(42)
        $this->assertSame('11-50', $metrics['asset_image_count_bucket']);    // bucket(30)
        $this->assertSame('1-10', $metrics['asset_video_count_bucket']);     // bucket(5)
        $this->assertSame('1-10', $metrics['asset_document_count_bucket']);  // bucket(4)
        $this->assertSame('0', $metrics['asset_audio_count_bucket']);        // bucket(0)
        $this->assertSame(5, $metrics['asset_type_variety']);                // distinct types incl. folder

        $this->assertSame('11-50', $metrics['object_count_bucket']);         // bucket(40)
        $this->assertSame('0', $metrics['object_variant_count_bucket']);     // bucket(0)
        $this->assertSame('51-200', $metrics['document_page_count_bucket']); // bucket(60)
        $this->assertSame('1-10', $metrics['document_email_count_bucket']);  // bucket(5)
        $this->assertSame('1-10', $metrics['document_link_count_bucket']);   // bucket(5)

        // Low-cardinality facts (classes/sites) still come from a direct count.
        $this->assertSame(1, $metrics['schema_version']);
        $this->assertSame('11-50', $metrics['class_count_bucket']);          // bucket(20)
        $this->assertSame(1, $metrics['site_count']);

        foreach ($metrics as $key => $value) {
            $this->assertIsScalar($value, "metric '$key' must be scalar");
        }
    }

    public function testBundleFlagsReflectActiveBundles(): void
    {
        $metrics = $this->collector([
            new PillarStubEcommerceFrameworkBundle(),
            new PillarStubDataHubBundle(),
        ])->collect();

        $this->assertTrue($metrics['commerce_bundle_active']);
        $this->assertTrue($metrics['datahub_bundle_active']);
        $this->assertFalse($metrics['seo_bundle_active']);
        $this->assertFalse($metrics['portal_engine_bundle_active']);
    }

    /**
     * @param list<object> $activeBundles
     */
    private function collector(array $activeBundles): PillarUsageCollector
    {
        $statistics = $this->createMock(ElementStatisticsProviderInterface::class);
        $statistics->method('typeCounts')->willReturnCallback(
            static fn (ElementKind $kind): ElementTypeCounts => match ($kind) {
                ElementKind::Asset => new ElementTypeCounts(['image' => 30, 'video' => 5, 'document' => 4, 'audio' => 0, 'folder' => 3]),
                ElementKind::DataObject => new ElementTypeCounts(['object' => 40, 'variant' => 0, 'folder' => 2]),
                ElementKind::Document => new ElementTypeCounts(['page' => 60, 'email' => 5, 'link' => 5]),
            }
        );

        // Runner still serves the tiny classes/sites COUNT(*)s.
        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnArgument(0);
        $connection->method('fetchOne')->willReturnCallback(
            static fn (string $sql): int => match (true) {
                str_contains($sql, 'classes') => 20,
                str_contains($sql, 'sites') => 1,
                default => 0,
            }
        );

        $bundleManager = $this->createMock(PimcoreBundleManager::class);
        $bundleManager->method('getActiveBundles')->willReturn($activeBundles);

        return new PillarUsageCollector(
            new ActiveBundles($bundleManager),
            new SnapshotQueryRunner($connection, 0),
            $statistics,
            new Bucketizer(),
        );
    }
}
