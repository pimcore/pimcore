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
        $this->assertSame(42, $metrics['asset_count']);
        $this->assertSame(30, $metrics['asset_image_count']);
        $this->assertSame(5, $metrics['asset_video_count']);
        $this->assertSame(4, $metrics['asset_document_count']);
        $this->assertSame(0, $metrics['asset_audio_count']);
        $this->assertSame(5, $metrics['asset_type_variety']);                // distinct types incl. folder

        $this->assertSame(40, $metrics['object_count']);
        $this->assertSame(0, $metrics['object_variant_count']);
        $this->assertSame(42, $metrics['object_total_count']);   // 40 objects + 0 variants + 2 folders
        $this->assertSame(60, $metrics['document_page_count']);
        $this->assertSame(5, $metrics['document_email_count']);
        $this->assertSame(5, $metrics['document_link_count']);
        $this->assertSame(70, $metrics['document_total_count']); // 60 pages + 5 emails + 5 links

        // Low-cardinality facts (classes/sites) still come from a direct count.
        $this->assertSame(1, $metrics['schema_version']);
        $this->assertSame(20, $metrics['class_count']);
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
    /**
     * Raw counts, not ranges. Management concluded a count is not personal data, and bucketing was
     * throwing away exactly the precision that makes segment sizing possible.
     */
    public function testEveryCountIsARawInteger(): void
    {
        $metrics = $this->collector([])->collect();

        foreach ($metrics as $key => $value) {
            $this->assertStringNotContainsString('_bucket', (string)$key);
        }

        foreach ([
            'asset_count', 'asset_image_count', 'asset_video_count', 'asset_document_count',
            'asset_audio_count', 'class_count', 'object_count', 'object_variant_count',
            'document_page_count', 'document_email_count', 'document_link_count',
            'object_total_count', 'document_total_count',
        ] as $key) {
            $this->assertIsInt($metrics[$key], "metric '$key' must be an int");
        }
    }

    /**
     * core.* used to report table-wide totals separately, which duplicated pillars.* (core.asset_count
     * was byte-identical to pillars.asset_count) and made the element aggregation run twice. The
     * totals moved here, where the per-kind counts are already in hand, so they cost no extra query.
     */
    public function testTableWideTotalsCoverWhatCoreUsedToReport(): void
    {
        $metrics = $this->collector([])->collect();

        // assets: `asset_count` is already the table-wide total, so there is no asset_total_count
        $this->assertSame(42, $metrics['asset_count']);
        $this->assertArrayNotHasKey('asset_total_count', $metrics);

        // objects/documents: the per-type counts do not add up to the table, so totals are explicit
        $this->assertGreaterThanOrEqual(
            $metrics['object_count'] + $metrics['object_variant_count'],
            $metrics['object_total_count']
        );
        $this->assertGreaterThanOrEqual(
            $metrics['document_page_count'] + $metrics['document_email_count'] + $metrics['document_link_count'],
            $metrics['document_total_count']
        );
    }

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
        );
    }
}
