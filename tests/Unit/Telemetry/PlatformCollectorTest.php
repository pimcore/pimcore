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
use Pimcore\Telemetry\Snapshot\PlatformCollector;
use Pimcore\Telemetry\Snapshot\SnapshotQueryRunner;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\Manager;
use RuntimeException;
use function array_filter;
use function is_string;
use function preg_match;
use function preg_quote;
use function str_contains;

class PlatformCollectorTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $executedSql = [];

    public function testNamespaceIsPlatform(): void
    {
        $this->assertSame('platform', $this->collector()->getNamespace());
    }

    /**
     * Seats are the headline metric. The users table also stores roles and folders, so an unfiltered
     * COUNT(*) would inflate the seat count by the entire permission model - here 10 users would read
     * as 18. The type filter is the whole point of this metric.
     */
    public function testSeatCountsExcludeRolesAndFolders(): void
    {
        $metrics = $this->collector()->collect();

        $this->assertSame(10, $metrics['user_count']);
        $this->assertSame(8, $metrics['active_user_count']);
        $this->assertSame(2, $metrics['admin_user_count']);
        $this->assertSame(6, $metrics['role_count']);
    }

    /**
     * Every seat query must be scoped by type. A regression that dropped the filter would still return
     * a plausible integer, so assert on the SQL rather than only on the value.
     */
    public function testEverySeatQueryIsScopedByUserType(): void
    {
        $this->collector()->collect();

        $seatQueries = 0;
        foreach ($this->executedSql as $sql) {
            if (!str_contains($sql, 'FROM users') || str_contains($sql, 'users_')) {
                continue;
            }
            $seatQueries++;
            $this->assertStringContainsString('type = ', $sql, "unscoped count over the users table: $sql");
        }

        $this->assertSame(4, $seatQueries, 'expected user/active/admin/role counts');
    }

    public function testReportsThePermissionModelShape(): void
    {
        $metrics = $this->collector()->collect();

        $this->assertSame(106, $metrics['permission_definition_count']);
        $this->assertSame(3, $metrics['object_workspace_count']);
        $this->assertSame(3, $metrics['asset_workspace_count']);
        $this->assertSame(0, $metrics['document_workspace_count']);
    }

    /**
     * Database size is an aggregate over information_schema. Only the SUM leaves the server - never a
     * table name, which is exactly what made the legacy `tables` payload unsendable.
     */
    public function testReportsDatabaseSizeAndTableCountWithoutNamingTables(): void
    {
        $metrics = $this->collector()->collect();

        $this->assertSame(25, $metrics['database_size_mb']);
        $this->assertSame(297, $metrics['database_table_count']);

        foreach ($this->executedSql as $sql) {
            if (!str_contains($sql, 'information_schema')) {
                continue;
            }
            $this->assertStringNotContainsString('TABLE_NAME', $sql, 'must not select table names');
        }
    }

    /**
     * Schema currency, independent of the composer version string: an install can run an old schema
     * with a current package.
     */
    public function testReportsAppliedMigrationCount(): void
    {
        $this->assertSame(185, $this->collector()->collect()['applied_migration_count']);
    }

    /**
     * Versioning volume and relation-graph density are the two biggest storage drivers on a mature
     * install and neither was collected before.
     */
    public function testReportsOperationalVolume(): void
    {
        $metrics = $this->collector(overrides: [
            'versions' => 412_004,
            'dependencies' => 2_910,
            'search_backend_data' => 1_304,
        ])->collect();

        $this->assertSame(412_004, $metrics['version_count']);
        $this->assertSame(2_910, $metrics['dependency_count']);
        $this->assertSame(1_304, $metrics['search_index_entry_count']);
    }

    /**
     * These are the counts most likely to hit the statement timeout, because they are unbounded. An
     * absent key says "too large to count in budget", which is information; a wrong integer is not.
     * Losing one must not cost the others.
     */
    public function testATimedOutVolumeCountIsOmittedWithoutLosingTheRest(): void
    {
        $metrics = $this->collector(failFor: 'versions')->collect();

        $this->assertArrayNotHasKey('version_count', $metrics);
        $this->assertArrayHasKey('dependency_count', $metrics);
        $this->assertArrayHasKey('user_count', $metrics);
    }

    /**
     * Workflow reach: how many are defined versus how many elements actually sit in one.
     */
    public function testReportsWorkflowReach(): void
    {
        $metrics = $this->collector(
            workflows: ['product_approval', 'asset_review'],
            overrides: ['element_workflow_state' => 51]
        )->collect();

        $this->assertSame(2, $metrics['workflow_configured_count']);
        $this->assertSame(51, $metrics['workflow_active_element_count']);
        $this->assertSame(1, $metrics['workflow_distinct_in_use_count']);
    }

    /**
     * `element_workflow_state.workflow` holds customer-chosen workflow names, so only the DISTINCT
     * count may be emitted. Nothing in this namespace may be a string.
     */
    public function testNoWorkflowNameCanLeak(): void
    {
        $metrics = $this->collector(workflows: ['secret_project_gate'])->collect();

        foreach ($metrics as $key => $value) {
            $this->assertIsInt($value, "metric '$key' must be an int");
        }

        foreach ($this->executedSql as $sql) {
            $this->assertStringNotContainsString('secret_project_gate', $sql);
        }
    }

    /**
     * Every count is an exact integer - no buckets, no estimates.
     */
    public function testEveryMetricIsAnIntegerAndNoneIsABucket(): void
    {
        foreach ($this->collector()->collect() as $key => $value) {
            $this->assertIsInt($value, "metric '$key' must be an int");
            $this->assertStringNotContainsString('_bucket', (string)$key);
            $this->assertFalse(is_string($value));
        }
    }

    /**
     * A query that fails must omit its key, never report 0 - the same unknown-is-not-zero rule the
     * usage.* namespace follows. Reporting an install as having zero seats would be worse than silence.
     */
    public function testAFailedQueryOmitsItsKeyRatherThanReportingZero(): void
    {
        $metrics = $this->collector(failFor: 'users')->collect();

        $this->assertArrayNotHasKey('user_count', $metrics);
        $this->assertArrayNotHasKey('active_user_count', $metrics);
        $this->assertArrayHasKey('applied_migration_count', $metrics, 'unrelated metrics must survive');
    }

    /**
     * A table that genuinely holds nothing still reports 0 - the fallback has to distinguish
     * "could not count" from "counted zero".
     */
    public function testAGenuinelyEmptyTableStillReportsZero(): void
    {
        $metrics = $this->collector(overrides: ['users_workspaces_object' => 0])->collect();

        $this->assertSame(0, $metrics['object_workspace_count']);
    }

    /**
     * An unavailable workflow manager is unknown, not "no workflows" - so the configured count is
     * omitted while the state-table evidence, which stands on its own, is still collected.
     */
    public function testAnUnavailableWorkflowManagerOmitsTheConfiguredCount(): void
    {
        $metrics = $this->collector(failWorkflowManager: true)->collect();

        $this->assertArrayNotHasKey('workflow_configured_count', $metrics);
        $this->assertArrayHasKey('workflow_active_element_count', $metrics);
    }

    /**
     * With no workflows configured there is nothing to observe, so neither state query may run - PHP
     * evaluates array values eagerly, so this only holds because the workflow metrics are appended
     * separately rather than inlined.
     */
    public function testTheStateTableIsNotQueriedWhenNoWorkflowsAreConfigured(): void
    {
        $metrics = $this->collector(workflows: [])->collect();

        $this->assertSame(0, $metrics['workflow_configured_count']);
        $this->assertArrayNotHasKey('workflow_active_element_count', $metrics);
        $this->assertArrayNotHasKey('workflow_distinct_in_use_count', $metrics);

        foreach ($this->executedSql as $sql) {
            $this->assertStringNotContainsString('element_workflow_state', $sql);
        }
    }

    /**
     * Both recycle-bin figures are needed. One entry can hold an entire subtree, so entries alone
     * understate what is retained - and it is the element total that drives the storage cost, since
     * every entry keeps serialised data behind it.
     */
    public function testReportsRecyclebinEntriesAndElementsSeparately(): void
    {
        $metrics = $this->collector(overrides: ['recyclebin' => 12])->collect();

        $this->assertSame(12, $metrics['recyclebin_item_count']);
        $this->assertSame(84, $metrics['recyclebin_element_count']);
        $this->assertGreaterThan(
            $metrics['recyclebin_item_count'],
            $metrics['recyclebin_element_count'],
            'entries must not be conflated with elements'
        );
    }

    /**
     * MySQL's SUM() returns NULL over an empty table, and the collector's null-filter would then drop
     * the key - reporting an emptied recycle bin as "unknown" instead of as zero. COALESCE is the only
     * thing preventing that, and the behaviour lives in the database, so assert on the SQL: a mock
     * cannot distinguish "COALESCE worked" from "COALESCE is missing".
     */
    public function testTheRecyclebinElementSumIsCoalescedSoAnEmptyBinReadsAsZero(): void
    {
        $this->collector()->collect();

        $sumQueries = array_filter($this->executedSql, static fn (string $sql): bool => str_contains($sql, 'SUM(amount)'));

        $this->assertCount(1, $sumQueries, 'expected exactly one recycle-bin element sum');
        foreach ($sumQueries as $sql) {
            $this->assertStringContainsString('COALESCE(SUM(amount), 0)', $sql);
        }
    }

    /**
     * `recyclebin.path` holds the deleted element's path and `deletedby` a username - both customer
     * content. Neither column may be read.
     */
    public function testTheRecyclebinPathAndDeletedByAreNeverRead(): void
    {
        $this->collector(overrides: ['recyclebin' => 5])->collect();

        foreach ($this->executedSql as $sql) {
            if (!str_contains($sql, 'recyclebin')) {
                continue;
            }
            $this->assertStringNotContainsString('path', $sql);
            $this->assertStringNotContainsString('deletedby', $sql);
        }
    }

    /**
     * @param array<string, int> $overrides replacement counts, by table
     * @param list<string>       $workflows configured workflow names
     */
    private function collector(
        ?string $failFor = null,
        array $overrides = [],
        array $workflows = ['product_approval'],
        bool $failWorkflowManager = false,
    ): PlatformCollector {
        $this->executedSql = [];

        $counts = $overrides + [
            'users'                        => 10,
            'users:active'                 => 8,
            'users:admin'                  => 2,
            'users:role'                   => 6,
            'users_permission_definitions' => 106,
            'users_workspaces_object'      => 3,
            'users_workspaces_asset'       => 3,
            'users_workspaces_document'    => 0,
            'migration_versions'           => 185,
            'versions'                     => 0,
            'dependencies'                 => 0,
            'search_backend_data'          => 0,
            'element_workflow_state'       => 0,
            'recyclebin'                   => 0,
        ];

        $manager = $this->createMock(Manager::class);
        if ($failWorkflowManager) {
            $manager->method('getAllWorkflows')->willThrowException(new RuntimeException('container not booted'));
        } else {
            $manager->method('getAllWorkflows')->willReturn($workflows);
        }

        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnArgument(0);
        $connection->method('fetchOne')->willReturnCallback(
            function (string $sql, array $params = []) use ($counts, $failFor): int|string|false {
                $this->executedSql[] = $sql;

                if ($failFor !== null && str_contains($sql, $failFor)) {
                    // stands in for what the per-statement timeout surfaces as
                    throw new RuntimeException('max_statement_time exceeded');
                }

                if (str_contains($sql, 'SUM(data_length')) {
                    return 26_214_400; // 25 MiB
                }

                if (str_contains($sql, 'information_schema')) {
                    return 297;
                }

                if (str_contains($sql, 'SUM(amount)')) {
                    // one entry can hold a subtree, so elements far exceed entries
                    return $counts['recyclebin'] * 7;
                }

                if (str_contains($sql, 'COUNT(DISTINCT workflow)')) {
                    return $counts['element_workflow_state'] > 0 ? 1 : 0;
                }

                if (str_contains($sql, 'FROM users') && !str_contains($sql, 'users_')) {
                    if (str_contains($sql, "type = 'role'")) {
                        return $counts['users:role'];
                    }
                    if (str_contains($sql, 'active = 1')) {
                        return $counts['users:active'];
                    }
                    if (str_contains($sql, 'admin = 1')) {
                        return $counts['users:admin'];
                    }

                    return $counts['users'];
                }

                foreach ($counts as $table => $value) {
                    if (str_contains($table, ':')) {
                        continue;
                    }
                    // word-boundary match: a plain str_contains for 'users' also hits
                    // 'users_permission_definitions' and would answer the wrong query
                    if (preg_match('/\bFROM ' . preg_quote($table, '/') . '\b/', $sql) === 1) {
                        return $value;
                    }
                }

                return 0;
            }
        );

        return new PlatformCollector(new SnapshotQueryRunner($connection, 0), $manager);
    }
}
