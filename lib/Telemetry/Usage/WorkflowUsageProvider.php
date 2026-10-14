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
use function array_fill;
use function count;
use function implode;
use function is_numeric;

/**
 * Reference {@see BundleUsageProviderInterface} for a core capability: workflows are "used" when at
 * least one element has actually been moved through one - not merely when a workflow is configured.
 *
 * A workflow defined in config that no element has ever entered is exactly the shelfware `usage.*`
 * exists to surface, and counting it as used would report an adoption success where there is none.
 * {@see \Pimcore\Telemetry\Snapshot\PlatformCollector} emits the counts side by side so the gap shows.
 *
 * **Only some workflows are observable.** Pimcore supports five marking stores, and just `state_table`
 * persists into `element_workflow_state`; `single_state`, `multiple_state`,
 * `data_object_multiple_state` and `data_object_splitted_state` all keep the marking on the subject
 * itself, where no aggregate query can reach it. Reporting `false` for those would invent an adoption
 * gap for a workflow that is being exercised heavily, so anything not explicitly `state_table` counts
 * as unobservable and yields `null` (unknown) in the absence of positive evidence. Erring toward
 * unknown is deliberate: a false "not used" is the one answer this namespace must never give.
 *
 * The state-table query is scoped to the currently configured workflows, so rows left behind by a
 * workflow that has since been removed cannot report use that is no longer possible.
 *
 * Content-never: a boolean derived from a row count. Workflow names are used only as query parameters
 * and are never emitted.
 *
 * @internal
 */
final readonly class WorkflowUsageProvider implements BundleUsageProviderInterface
{
    /**
     * The one marking store whose state lands in `element_workflow_state`.
     */
    private const OBSERVABLE_MARKING_STORE = 'state_table';

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
            $configured = $this->workflowManager->getAllWorkflows();
        } catch (Exception) {
            return null;
        }

        if ($configured === []) {
            // Nothing configured, so nothing to exercise. The state table is not touched - the
            // majority of installs run no workflows at all and should pay nothing for this metric.
            return false;
        }

        $observable = $this->observableWorkflows($configured);

        if ($observable !== []) {
            $inWorkflow = $this->activeElementCount($observable);

            if ($inWorkflow === null) {
                return null;
            }

            if ($inWorkflow > 0) {
                return true;
            }
        }

        // No positive evidence. If every configured workflow is observable, the empty state table is a
        // real "configured but never exercised". If any is not, we simply cannot see it - unknown.
        return count($observable) === count($configured) ? false : null;
    }

    /**
     * @param  list<string> $configured
     *
     * @return list<string> those whose marking is readable from `element_workflow_state`
     */
    private function observableWorkflows(array $configured): array
    {
        $observable = [];

        foreach ($configured as $name) {
            try {
                $config = $this->workflowManager->getWorkflowConfig($name)->getWorkflowConfigArray();
            } catch (Exception) {
                // Cannot classify it, so cannot claim to observe it.
                continue;
            }

            if (($config['marking_store']['type'] ?? null) === self::OBSERVABLE_MARKING_STORE) {
                $observable[] = $name;
            }
        }

        return $observable;
    }

    /**
     * @param  list<string> $workflows scoped to these, so rows from removed workflows do not count
     *
     * @return int|null     null when the state table could not be read - unknown, not unused
     */
    private function activeElementCount(array $workflows): ?int
    {
        $placeholders = implode(', ', array_fill(0, count($workflows), '?'));

        try {
            $count = $this->queryRunner->fetchOne(
                'SELECT COUNT(*) FROM ' . $this->queryRunner->quoteIdentifier('element_workflow_state')
                . ' WHERE workflow IN (' . $placeholders . ')',
                $workflows
            );

            return is_numeric($count) ? (int)$count : null;
        } catch (Exception) {
            return null;
        }
    }
}
