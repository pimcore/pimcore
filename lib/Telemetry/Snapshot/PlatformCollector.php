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

namespace Pimcore\Telemetry\Snapshot;

use Exception;
use Pimcore\Workflow\Manager;
use function array_filter;
use function count;
use function is_numeric;

/**
 * How large this installation is and how it is run: seats, permission-model shape, database footprint,
 * schema currency, operational volume, and workflow reach.
 *
 * Complements the content collectors - {@see PillarUsageCollector} counts what is managed, this counts
 * who manages it and what it costs to host.
 *
 * Every figure is a count over a FIXED-NAME table. Nothing here enumerates table names: the database
 * footprint is a single SUM over information_schema, so only the aggregate leaves the server. That is
 * the line the legacy StatisticsManager crossed - half of its `tables` payload was per-class tables
 * whose names embed the customer's own class, brick and fieldcollection names.
 *
 * @internal
 */
final readonly class PlatformCollector implements SnapshotCollectorInterface
{
    private const SCHEMA_VERSION = 1;

    public function __construct(
        private SnapshotQueryRunner $queryRunner,
        private Manager $workflowManager,
    ) {
    }

    public function getNamespace(): string
    {
        return 'platform';
    }

    public function collect(): array
    {
        $metrics = [
            'schema_version' => self::SCHEMA_VERSION,

            // Seats. The users table also holds roles and folders (`type` enum), so every seat figure
            // is filtered - an unfiltered count would report the permission model as licensed users.
            'user_count' => $this->count('users', "type = 'user'"),
            'active_user_count' => $this->count('users', "type = 'user' AND active = 1"),
            'admin_user_count' => $this->count('users', "type = 'user' AND admin = 1"),
            'role_count' => $this->count('users', "type = 'role'"),

            // Permission-model shape.
            'permission_definition_count' => $this->count('users_permission_definitions'),
            'object_workspace_count' => $this->count('users_workspaces_object'),
            'asset_workspace_count' => $this->count('users_workspaces_asset'),
            'document_workspace_count' => $this->count('users_workspaces_document'),

            // Hosting footprint.
            'database_size_mb' => $this->databaseSizeMb(),
            'database_table_count' => $this->tableCount(),

            // Schema currency - an install can run a stale schema behind a current package version.
            'applied_migration_count' => $this->count('migration_versions'),

            // Unbounded operational tables. These are the most likely to exceed the statement timeout;
            // when they do the key is omitted, which reads as "too large to count in budget" rather
            // than as a small install. An information_schema row estimate is not acceptable at raw
            // precision - that is exactly what was removed when bucketing went.
            'version_count' => $this->count('versions'),
            'dependency_count' => $this->count('dependencies'),
            'search_index_entry_count' => $this->count('search_backend_data'),

            // Recycle bin. Both figures are needed: one entry can hold an entire subtree, so the row
            // count alone understates what is actually retained - and it is the element total that
            // drives the storage cost, since each entry keeps serialised data (and asset binaries).
            // A recycle bin that is never emptied is a real hygiene and support signal.
            // `path` and `deletedby` are customer content and are deliberately never read.
            'recyclebin_item_count' => $this->count('recyclebin'),
            'recyclebin_element_count' => $this->recyclebinElementCount(),

            // Workflow reach. Names are deliberately absent: `element_workflow_state.workflow` holds
            // customer-defined workflow names, so only the DISTINCT count is emitted.
            'workflow_configured_count' => $this->workflowCount(),
            'workflow_active_element_count' => $this->count('element_workflow_state'),
            'workflow_distinct_in_use_count' => $this->fetchCount(
                'SELECT COUNT(DISTINCT workflow) FROM '
                . $this->queryRunner->quoteIdentifier('element_workflow_state')
            ),
        ];

        // Unknown is not zero: a timed-out or failed count omits its key rather than claiming the
        // install has no seats.
        return array_filter($metrics, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return int|null null when the count could not be obtained (timeout, driver error)
     */
    private function count(string $table, ?string $where = null): ?int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->queryRunner->quoteIdentifier($table);
        if ($where !== null) {
            $sql .= ' WHERE ' . $where;
        }

        return $this->fetchCount($sql);
    }

    /**
     * Elements pending purge, not entries. COALESCE matters: SUM() over an empty table returns NULL,
     * which would omit the key and read as "unknown" when the truth is an empty recycle bin.
     */
    private function recyclebinElementCount(): ?int
    {
        return $this->fetchCount(
            'SELECT COALESCE(SUM(amount), 0) FROM ' . $this->queryRunner->quoteIdentifier('recyclebin')
        );
    }

    /**
     * Aggregate only. Deliberately selects no TABLE_NAME - see the class docblock.
     */
    private function databaseSizeMb(): ?int
    {
        $bytes = $this->fetchCount(
            'SELECT SUM(data_length + index_length) FROM information_schema.TABLES'
            . ' WHERE TABLE_SCHEMA = DATABASE()'
        );

        return $bytes === null ? null : (int)round($bytes / 1024 / 1024);
    }

    private function tableCount(): ?int
    {
        return $this->fetchCount(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()'
        );
    }

    private function workflowCount(): ?int
    {
        try {
            return count($this->workflowManager->getAllWorkflows());
        } catch (Exception) {
            return null;
        }
    }

    private function fetchCount(string $sql): ?int
    {
        try {
            $value = $this->queryRunner->fetchOne($sql);

            return is_numeric($value) ? (int)$value : null;
        } catch (Exception) {
            return null;
        }
    }
}
