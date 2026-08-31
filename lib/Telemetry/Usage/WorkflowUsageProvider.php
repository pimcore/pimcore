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

namespace Pimcore\Telemetry\Usage;

use Exception;
use Pimcore\Telemetry\Snapshot\SnapshotQueryRunner;
use Pimcore\Workflow\Manager;
use function is_numeric;

/**
 * Reference {@see BundleUsageProviderInterface} for a core capability: workflows are "used" when at
 * least one element actually sits in a workflow state - not merely when a workflow is configured.
 *
 * That distinction is the whole point of this namespace. A workflow defined in config that no element
 * has ever entered is exactly the shelfware `usage.*` exists to surface, and counting it as used would
 * report an adoption success where there is none. {@see PlatformCollector} emits the two counts side by
 * side (`workflow_configured_count` vs `workflow_active_element_count`) so the gap is visible.
 *
 * Purely structural and content-never: a boolean derived from a row count. It self-resets - archiving
 * the last in-flight element flips it back - so the capability owns the definition of "used".
 *
 * Also the reference for the null case: if neither the workflow manager nor the state table can be
 * consulted we do not know whether workflows are in use, and saying `false` there would be a
 * fabricated adoption gap.
 *
 * @internal
 */
final readonly class WorkflowUsageProvider implements BundleUsageProviderInterface
{
    public function __construct(
        private Manager $workflowManager,
        private SnapshotQueryRunner $queryRunner,
    ) {
    }

    public function getBundleKey(): string
    {
        return 'workflow';
    }

    public function isUsed(): ?bool
    {
        try {
            if ($this->workflowManager->getAllWorkflows() === []) {
                // Nothing configured, so there is nothing to exercise - no need to touch the state
                // table, and an empty state table here would be unused rather than unknown anyway.
                return false;
            }
        } catch (Exception) {
            return null;
        }

        $inWorkflow = $this->activeElementCount();

        return $inWorkflow === null ? null : $inWorkflow > 0;
    }

    /**
     * @return int|null null when the state table could not be read - unknown, not unused
     */
    private function activeElementCount(): ?int
    {
        try {
            $count = $this->queryRunner->fetchOne(
                'SELECT COUNT(*) FROM ' . $this->queryRunner->quoteIdentifier('element_workflow_state')
            );

            return is_numeric($count) ? (int)$count : null;
        } catch (Exception) {
            return null;
        }
    }
}
