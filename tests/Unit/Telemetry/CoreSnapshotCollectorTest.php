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
use Pimcore\Telemetry\Snapshot\CoreSnapshotCollector;
use Pimcore\Telemetry\Snapshot\SnapshotQueryRunner;
use Pimcore\Tests\Support\Test\TestCase;
use function str_contains;

class CoreSnapshotCollectorTest extends TestCase
{
    public function testNamespaceIsCore(): void
    {
        $this->assertSame('core', $this->collector()->getNamespace());
    }

    public function testLargeTablesTrustTheEstimateAndSmallTablesFallBackToAnExactCount(): void
    {
        $metrics = $this->collector()->collect();

        // objects: estimate 250000 (>= threshold) -> trusted, no COUNT(*) -> top bucket.
        $this->assertSame('1000+', $metrics['object_count_bucket']);
        // classes: estimate 8000 (>= threshold) -> trusted -> top bucket.
        $this->assertSame('1000+', $metrics['dataobject_class_count_bucket']);
        // assets: estimate 3 (< threshold) -> exact COUNT(*) = 7 -> precise bucket.
        $this->assertSame('1-10', $metrics['asset_count_bucket']);
        // documents: estimate 0 / unknown -> exact COUNT(*) = 120 -> precise bucket.
        $this->assertSame('51-200', $metrics['document_count_bucket']);
    }

    /**
     * Version and language facts come from Pimcore's own statics (as in Tool\StatisticsManager), so
     * assert their shape rather than pinning values that change with the release.
     */
    public function testReportsEnvironmentFacts(): void
    {
        $metrics = $this->collector()->collect();

        $this->assertIsString($metrics['pimcore_version']);
        $this->assertNotSame('', $metrics['pimcore_version']);
        $this->assertIsInt($metrics['pimcore_major_version']);
        $this->assertIsInt($metrics['language_count']);
        $this->assertGreaterThanOrEqual(0, $metrics['language_count']);

        // this one is injected, so it can still be pinned exactly
        $this->assertSame('10.11-MariaDB', $metrics['mysql_version']);
    }

    /**
     * The raw APP_ENV must never reach the payload: it is customer-authored text, so anything
     * outside the known set collapses to 'other'.
     */
    public function testModeIsReducedToAFixedVocabulary(): void
    {
        $this->assertSame('prod', $this->collector('prod')->collect()['mode']);
        $this->assertSame('dev', $this->collector('dev')->collect()['mode']);
        $this->assertSame('test', $this->collector('test')->collect()['mode']);

        // case and stray whitespace still resolve
        $this->assertSame('prod', $this->collector('  PROD ')->collect()['mode']);

        // customer-authored values never pass through verbatim
        $this->assertSame('other', $this->collector('prod_acme')->collect()['mode']);
        $this->assertSame('other', $this->collector('staging')->collect()['mode']);
        $this->assertSame('other', $this->collector('')->collect()['mode']);
    }

    private function collector(string $environment = 'prod'): CoreSnapshotCollector
    {
        $bundleManager = $this->createMock(PimcoreBundleManager::class);
        $bundleManager->method('getActiveBundles')->willReturn([]);

        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnArgument(0);
        $connection->method('fetchOne')->willReturnCallback(
            static function (string $sql, array $params = []): int|string {
                if (str_contains($sql, 'information_schema')) {
                    return match ($params[0] ?? null) {
                        'objects' => 250000,
                        'classes' => 8000,
                        'assets' => 3,
                        default => 0, // documents -> optimizer has no estimate
                    };
                }

                if (str_contains($sql, 'VERSION()')) {
                    return '10.11-MariaDB';
                }

                // exact COUNT(*) fallback for the small/unknown tables
                return match (true) {
                    str_contains($sql, 'assets') => 7,
                    str_contains($sql, 'documents') => 120,
                    default => 0,
                };
            }
        );

        return new CoreSnapshotCollector(
            new ActiveBundles($bundleManager),
            new SnapshotQueryRunner($connection, 0),
            new Bucketizer(),
            $environment,
        );
    }
}
