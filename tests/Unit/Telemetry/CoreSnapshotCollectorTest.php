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
use Pimcore\Telemetry\Snapshot\CoreSnapshotCollector;
use Pimcore\Telemetry\Snapshot\SnapshotQueryRunner;
use Pimcore\Tests\Support\Test\TestCase;
use function count;
use function is_string;
use function sprintf;
use function str_contains;

class CoreSnapshotCollectorTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $executedSql = [];

    public function testNamespaceIsCore(): void
    {
        $this->assertSame('core', $this->collector()->getNamespace());
    }

    /**
     * The bucket scale is gone; nothing may emit a range string any more.
     */
    public function testNoMetricIsABucketString(): void
    {
        foreach ($this->collector()->collect() as $key => $value) {
            $this->assertStringNotContainsString('_bucket', (string)$key);

            if (is_string($value)) {
                $this->assertDoesNotMatchRegularExpression('/^\d+-\d+$|^\d+\+$/', $value);
            }
        }
    }

    /**
     * The estimate shortcut is gone: it read information_schema, and nothing may any more.
     */
    public function testTheRowEstimateShortcutIsGone(): void
    {
        $this->collector()->collect();

        foreach ($this->executedSql as $sql) {
            $this->assertStringNotContainsString('information_schema', $sql);
            $this->assertStringNotContainsString('TABLE_ROWS', $sql);
        }
    }

    /**
     * core.* reports no volume at all: pillars.* owns it and derives every count from one shared
     * aggregation. Counting anything here would either duplicate that number under a second name or
     * run the snapshot's most expensive query twice.
     */
    public function testCoreCountsNothing(): void
    {
        $metrics = $this->collector()->collect();

        foreach (['object_count', 'asset_count', 'document_count', 'dataobject_class_count'] as $key) {
            $this->assertArrayNotHasKey($key, $metrics);
        }

        foreach ($this->executedSql as $sql) {
            $this->assertStringNotContainsString('COUNT(*)', $sql);
        }
    }

    /**
     * `datahub_enabled` was a bundle-active flag that `core.bundles` and `pillars.datahub_bundle_active`
     * already carried, and Data Hub now reports real adoption through `usage.*` and `datahub.*`. It was
     * removed because a third copy of the same L2 boolean invited the reading that "enabled" said
     * something about use - so reintroducing it would be a regression, not an addition.
     */
    public function testTheRemovedDatahubEnabledFlagStaysRemoved(): void
    {
        $this->assertArrayNotHasKey('datahub_enabled', $this->collector()->collect());
    }

    /**
     * Debug and dev mode are misconfiguration signals, not usage: either one left on in a production
     * deployment costs performance and exposes internals. Both are plain booleans about our own
     * runtime, so they carry nothing about the customer.
     */
    public function testReportsDebugAndDevMode(): void
    {
        $this->assertFalse($this->collector()->collect()['kernel_debug']);
        $this->assertTrue($this->collector(debugMode: true)->collect()['kernel_debug']);

        // dev mode is read from $_SERVER rather than injected, because there is no container
        // parameter for it; Pimcore::inDevMode() normalises whatever is there to a bool
        $this->assertIsBool($this->collector()->collect()['dev_mode']);
    }

    /**
     * A false boolean must survive the null-filter that omits failed counts - filtering on
     * truthiness instead of an explicit null check would silently drop kernel_debug=false.
     */
    public function testFalseAndEmptyValuesSurviveTheNullFilter(): void
    {
        $metrics = $this->collector(environment: '')->collect();

        $this->assertArrayHasKey('kernel_debug', $metrics);
        $this->assertFalse($metrics['kernel_debug']);
        $this->assertArrayHasKey('environment_name', $metrics);
        $this->assertSame('', $metrics['environment_name']);
    }

    /**
     * Every metric has to be scalar - the snapshot is flattened into PostHog group properties, and a
     * non-scalar would be silently dropped by the sanitizer.
     */
    public function testTheNewFlagsAreScalar(): void
    {
        $metrics = $this->collector()->collect();

        foreach (['kernel_debug', 'dev_mode', 'environment_name'] as $key) {
            $this->assertIsScalar($metrics[$key], sprintf("metric '%s' must be scalar", $key));
        }
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
     * `environment_name` is reported verbatim, matching what Tool\StatisticsManager already sends to
     * license.pimcore.com and what the group key is built from, so all three line up.
     */
    public function testTheEnvironmentIsReportedVerbatim(): void
    {
        $this->assertSame('prod', $this->collector()->collect()['environment_name']);
        $this->assertSame('staging', $this->collector(environment: 'staging')->collect()['environment_name']);

        // no collapsing and no normalising: whatever the kernel reports is what is sent
        $this->assertSame(
            'prod_acme-gmbh',
            $this->collector(environment: 'prod_acme-gmbh')->collect()['environment_name']
        );
        $this->assertSame('  PROD ', $this->collector(environment: '  PROD ')->collect()['environment_name']);
    }

    /**
     * The configured timezone wins over the PHP default, because Tool\Authentication re-sets the
     * global to the logged-in user's timezone - so the default is not reliably the system one.
     */
    public function testTheConfiguredTimezoneWinsOverThePhpDefault(): void
    {
        $this->assertSame(
            'Europe/Vienna',
            $this->collector(timezone: 'Europe/Vienna')->collect()['timezone']
        );
    }

    public function testAnUnconfiguredTimezoneFallsBackToThePhpDefault(): void
    {
        $this->assertSame(date_default_timezone_get(), $this->collector()->collect()['timezone']);
    }

    /**
     * Locale codes, not customer data. The list is kept as an array on purpose - EventSanitizer
     * preserves scalar arrays - and the count stays alongside it because a list is awkward to
     * aggregate on.
     */
    public function testReportsTheLanguageListAlongsideTheCount(): void
    {
        $metrics = $this->collector()->collect();

        $this->assertIsArray($metrics['system_languages']);
        $this->assertSame(count($metrics['system_languages']), $metrics['language_count']);
        foreach ($metrics['system_languages'] as $language) {
            $this->assertIsString($language);
        }
    }

    /**
     * Version::getRevision() is declared `string` but returns a nullable Composer reference, so an
     * install without one raises a TypeError. That must not cost the whole core.* namespace.
     */
    public function testTheGitHashIsAlwaysAString(): void
    {
        $this->assertIsString($this->collector()->collect()['pimcore_git_hash']);
    }

    private function collector(
        string $environment = 'prod',
        bool $debugMode = false,
        string $timezone = '',
    ): CoreSnapshotCollector {
        $this->executedSql = [];

        $bundleManager = $this->createMock(PimcoreBundleManager::class);
        $bundleManager->method('getActiveBundles')->willReturn([]);

        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnArgument(0);
        $connection->method('fetchOne')->willReturnCallback(
            function (string $sql): int|string {
                $this->executedSql[] = $sql;

                return str_contains($sql, 'VERSION()') ? '10.11-MariaDB' : 0;
            }
        );

        return new CoreSnapshotCollector(
            new ActiveBundles($bundleManager),
            new SnapshotQueryRunner($connection, 0),
            $environment,
            $debugMode,
            $timezone,
        );
    }
}
