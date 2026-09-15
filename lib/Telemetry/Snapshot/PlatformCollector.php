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
 * schema currency, operational volume, and workflow reach and shape.
 *
 * Every figure reads a FIXED-NAME table and only aggregates leave the server; table names appear as
 * bound predicates, never in a SELECT list. `version_count`, `dependency_count` and
 * `search_index_entry_count` are InnoDB row estimates (information_schema TABLE_ROWS), because an
 * exact COUNT(*) over those unbounded tables timed out in production; everything else is exact.
 *
 * @internal
 */
final readonly class PlatformCollector implements SnapshotCollectorInterface
{
    private const SCHEMA_VERSION = 1;

    private WorkflowShape $workflowShape;

    public function __construct(
        private SnapshotQueryRunner $queryRunner,
        private Manager $workflowManager,
    ) {
        $this->workflowShape = new WorkflowShape($workflowManager);
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
            'version_count' => $this->rowEstimate('versions'),
            'dependency_count' => $this->rowEstimate('dependencies'),
            'search_index_entry_count' => $this->rowEstimate('search_backend_data'),

            // Recycle bin. Both figures are needed: one entry can hold an entire subtree, so the row
            // count alone understates what is actually retained - and it is the element total that
            // drives the storage cost, since each entry keeps serialised data (and asset binaries).
            // A recycle bin that is never emptied is a real hygiene and support signal.
            // `path` and `deletedby` are customer content and are deliberately never read.
            'recyclebin_item_count' => $this->count('recyclebin'),
            'recyclebin_element_count' => $this->recyclebinElementCount(),

        ];

        // Workflow reach, appended separately so the state table is only queried when at least one
        // workflow is configured - PHP would otherwise evaluate both counts regardless, making every
        // workflow-free install pay for two scans. Names are deliberately absent:
        // `element_workflow_state.workflow` holds customer-defined names, so only the DISTINCT count
        // is emitted.
        //
        // Both counts are unscoped on purpose. Rows can outlive the workflow that wrote them, so
        // comparing them against `workflow_configured_count` is itself the signal for leftover state -
        // scoping them would hide exactly that. `usage.workflow` does scope, because there the answer
        // is a single boolean that must not claim use which is no longer possible.
        $metrics += $this->workflowMetrics();

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

    private function rowEstimate(string $table): ?int
    {
        return $this->fetchCount(
            'SELECT TABLE_ROWS FROM information_schema.TABLES'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table],
        );
    }

    private function tableCount(): ?int
    {
        return $this->fetchCount(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()'
        );
    }

    /**
     * The state table is skipped only when the configured count is known to be zero. An unavailable
     * manager is unknown rather than zero, and the state counts stand on their own as evidence, so
     * they are still collected in that case - just without a configured count to compare them to.
     *
     * The shape sums - places, transitions, start and end places, global actions - come from the workflow
     * definitions themselves and cost no query; {@see WorkflowShape} makes them all-or-nothing.
     *
     * @return array<string, int|null>
     */
    private function workflowMetrics(): array
    {
        try {
            $names = $this->workflowManager->getAllWorkflows();
        } catch (Exception) {
            $names = null;
        }

        if ($names === []) {
            return ['workflow_configured_count' => 0];
        }

        $metrics = [
            'workflow_configured_count' => $names === null ? null : count($names),
            'workflow_active_element_count' => $this->count('element_workflow_state'),
            'workflow_distinct_in_use_count' => $this->fetchCount(
                'SELECT COUNT(DISTINCT workflow) FROM '
                . $this->queryRunner->quoteIdentifier('element_workflow_state')
            ),
        ];

        if ($names === null) {
            return $metrics;
        }

        return $metrics + ($this->workflowShape->sums($names) ?? []);
    }

    /**
     * @param list<string> $params
     */
    private function fetchCount(string $sql, array $params = []): ?int
    {
        try {
            $value = $this->queryRunner->fetchOne($sql, $params);

            return is_numeric($value) ? (int)$value : null;
        } catch (Exception) {
            return null;
        }
    }
}
