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
use RuntimeException;
use function str_contains;

class CoreSnapshotCollectorTest extends TestCase
{
    /**
     * Every table below is set up so that trusting the estimate and running an exact count land in
     * DIFFERENT buckets. A wrong decision therefore changes the asserted value instead of being
     * masked by both paths agreeing.
     *
     * @var array<string, array{estimate: int, exact: int}>
     */
    private const TABLES = [
        // mid-decade estimate: 125k..500k all sit in 100001-1000000, so no COUNT(*) is needed
        'objects' => ['estimate' => 250_000, 'exact' => 999_999],
        // straddles a decade boundary (4k..16k), so the estimate cannot be trusted
        'classes' => ['estimate' => 8_000, 'exact' => 40],
        // optimizer has no estimate at all
        'assets' => ['estimate' => 0, 'exact' => 7],
        // small but unambiguous (1..6 is entirely within 1-10)
        'documents' => ['estimate' => 3, 'exact' => 5_000],
    ];

    /**
     * @var list<string>
     */
    private array $executedSql = [];

    public function testNamespaceIsCore(): void
    {
        $this->assertSame('core', $this->collector()->getNamespace());
    }

    /**
     * The estimate is only trusted when it buckets identically after being scaled by the assumed error
     * margin in both directions. That rule is derived from the bucket scale, so widening the buckets
     * cannot silently invalidate it - which is exactly what a fixed row threshold did.
     */
    public function testAnEstimateIsTrustedOnlyWhenItBucketsUnambiguously(): void
    {
        $metrics = $this->collector()->collect();

        $this->assertSame('100001-1000000', $metrics['object_count_bucket'], 'mid-decade estimate is trusted');
        $this->assertSame('11-100', $metrics['dataobject_class_count_bucket'], 'boundary estimate must be counted');
        $this->assertSame('1-10', $metrics['asset_count_bucket'], 'a missing estimate must be counted');
        $this->assertSame('1-10', $metrics['document_count_bucket'], 'unambiguous small estimate is trusted');
    }

    /**
     * The point of the estimate is to avoid scanning a huge table, so assert on the queries actually
     * issued rather than only on the resulting bucket.
     */
    public function testTheExactCountIsSkippedForTablesWhoseEstimateIsTrusted(): void
    {
        $this->collector()->collect();

        $this->assertFalse($this->counted('objects'), 'a multi-million-row table must not be scanned');
        $this->assertFalse($this->counted('documents'), 'an unambiguous small estimate needs no scan');
        $this->assertTrue($this->counted('classes'), 'a boundary estimate must be resolved exactly');
        $this->assertTrue($this->counted('assets'), 'a missing estimate must be resolved exactly');
    }

    /**
     * Exact counts can now land on large tables - any estimate within the error margin of a bucket
     * boundary - so the statement timeout is reachable where it previously was not. A failed count must
     * yield to the estimate: reporting a multi-thousand-element table as `0` would be a far worse
     * answer than a coarse one.
     */
    public function testAFailedExactCountFallsBackToTheEstimateRatherThanZero(): void
    {
        // 'classes' has an ambiguous estimate of 8000, so an exact count is attempted - and denied
        $metrics = $this->collector('prod', failCountFor: 'classes')->collect();

        $this->assertSame('1001-10000', $metrics['dataobject_class_count_bucket']);
    }

    /**
     * The corollary: a table that really is empty must still report 0, so the fallback has to
     * distinguish "could not count" from "counted zero".
     */
    public function testAGenuinelyEmptyTableStillReportsZero(): void
    {
        $metrics = $this->collector('prod', exactOverrides: ['assets' => 0])->collect();

        $this->assertSame('0', $metrics['asset_count_bucket']);
    }

    /**
     * Debug and dev mode are misconfiguration signals, not usage: either one left on in a production
     * deployment costs performance and exposes internals. Both are plain booleans about our own
     * runtime, so they carry nothing about the customer.
     */
    public function testReportsDebugAndDevMode(): void
    {
        $this->assertFalse($this->collector('prod')->collect()['kernel_debug']);
        $this->assertTrue($this->collector('prod', debugMode: true)->collect()['kernel_debug']);

        // dev mode is read from $_SERVER rather than injected, because there is no container
        // parameter for it; Pimcore::inDevMode() normalises whatever is there to a bool
        $this->assertIsBool($this->collector('prod')->collect()['dev_mode']);
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
        $this->assertSame('prod', $this->collector('prod')->collect()['environment_name']);
        $this->assertSame('staging', $this->collector('staging')->collect()['environment_name']);

        // no collapsing and no normalising: whatever the kernel reports is what is sent
        $this->assertSame('prod_acme-gmbh', $this->collector('prod_acme-gmbh')->collect()['environment_name']);
        $this->assertSame('  PROD ', $this->collector('  PROD ')->collect()['environment_name']);
        $this->assertSame('', $this->collector('')->collect()['environment_name']);
    }

    /**
     * The configured timezone wins over the PHP default, because Tool\Authentication re-sets the
     * global to the logged-in user's timezone - so the default is not reliably the system one.
     */
    public function testTheConfiguredTimezoneWinsOverThePhpDefault(): void
    {
        $this->assertSame(
            'Europe/Vienna',
            $this->collector('prod', timezone: 'Europe/Vienna')->collect()['timezone']
        );
    }

    public function testAnUnconfiguredTimezoneFallsBackToThePhpDefault(): void
    {
        $this->assertSame(date_default_timezone_get(), $this->collector('prod')->collect()['timezone']);
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

    private function counted(string $table): bool
    {
        foreach ($this->executedSql as $sql) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, $table)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string|null            $failCountFor   table whose exact COUNT(*) should blow up
     * @param array<string, int>     $exactOverrides replacement exact counts, by table
     */
    private function collector(
        string $environment = 'prod',
        ?string $failCountFor = null,
        array $exactOverrides = [],
        bool $debugMode = false,
        string $timezone = '',
    ): CoreSnapshotCollector {
        $this->executedSql = [];

        $bundleManager = $this->createMock(PimcoreBundleManager::class);
        $bundleManager->method('getActiveBundles')->willReturn([]);

        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnArgument(0);
        $connection->method('fetchOne')->willReturnCallback(
            function (string $sql, array $params = []) use ($failCountFor, $exactOverrides): int|string {
                $this->executedSql[] = $sql;

                if (str_contains($sql, 'information_schema')) {
                    return self::TABLES[$params[0] ?? '']['estimate'] ?? 0;
                }

                if (str_contains($sql, 'VERSION()')) {
                    return '10.11-MariaDB';
                }

                foreach (self::TABLES as $table => $fixture) {
                    if (!str_contains($sql, $table)) {
                        continue;
                    }

                    if ($table === $failCountFor) {
                        // stands in for what the per-statement timeout surfaces as; the collector
                        // catches \Exception, and SnapshotQueryRunner passes driver errors straight
                        // through, so the concrete Doctrine class does not matter here
                        throw new RuntimeException('max_statement_time exceeded');
                    }

                    return $exactOverrides[$table] ?? $fixture['exact'];
                }

                return 0;
            }
        );

        return new CoreSnapshotCollector(
            new ActiveBundles($bundleManager),
            new SnapshotQueryRunner($connection, 0),
            new Bucketizer(),
            $environment,
            $debugMode,
            $timezone,
        );
    }
}
