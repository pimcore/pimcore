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
use function count;

/**
 * The shape of the configured workflows, summed: how many places, transitions, start places, end places and
 * global actions the installation's definitions declare. Next to `workflow_configured_count` this says how
 * elaborate the modelled processes are - a three-place linear flow and a twelve-place review graph with four
 * global actions are both "one workflow".
 *
 * The definitions come from the Symfony workflow registry through the {@see Manager}; nothing is queried. A
 * start place is an initial marking (Symfony defaults to the first place, so every workflow has at least one),
 * an end place is a place no transition leaves. Names of workflows, places, transitions and actions are
 * customer content: they are counted and never emitted.
 *
 * The sums are all-or-nothing. A workflow whose service cannot be resolved makes the whole shape unknown
 * (null) rather than a lower sum that would read as a smaller installation.
 *
 * @internal
 */
final readonly class WorkflowShape
{
    public function __construct(
        private Manager $workflowManager,
    ) {
    }

    /**
     * @param string[] $workflowNames
     *
     * @return array<string, int>|null the five sums, or null as soon as one definition is unavailable
     */
    public function sums(array $workflowNames): ?array
    {
        $sums = [
            'workflow_place_count' => 0,
            'workflow_transition_count' => 0,
            'workflow_start_place_count' => 0,
            'workflow_end_place_count' => 0,
            'workflow_global_action_count' => 0,
        ];

        foreach ($workflowNames as $name) {
            $shape = $this->shapeOf($name);

            if ($shape === null) {
                return null;
            }

            foreach ($shape as $key => $value) {
                $sums[$key] += $value;
            }
        }

        return $sums;
    }

    /**
     * @return array<string, int>|null
     */
    private function shapeOf(string $workflowName): ?array
    {
        try {
            $workflow = $this->workflowManager->getWorkflowByName($workflowName);
        } catch (Exception) {
            return null;
        }

        if ($workflow === null) {
            return null;
        }

        $definition = $workflow->getDefinition();
        $left = [];

        foreach ($definition->getTransitions() as $transition) {
            foreach ($transition->getFroms() as $from) {
                $left[$from] = true;
            }
        }

        $ends = 0;

        foreach ($definition->getPlaces() as $place) {
            if (!isset($left[$place])) {
                $ends++;
            }
        }

        return [
            'workflow_place_count' => count($definition->getPlaces()),
            'workflow_transition_count' => count($definition->getTransitions()),
            'workflow_start_place_count' => count($definition->getInitialPlaces()),
            'workflow_end_place_count' => $ends,
            'workflow_global_action_count' => count($this->workflowManager->getGlobalActions($workflowName)),
        ];
    }
}
