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

namespace Pimcore\Contracts\PimcoreAgent\Event;

use Pimcore\Contracts\PimcoreAgent\AgentTaskInfo;

/**
 * Dispatched inline by the agent-task implementation immediately after an agent task commits
 * into a terminal status. `$task` reflects the just-committed row; `$previousStatus` is the
 * string status the task held before the transition (for listeners that only care about a
 * specific prior state).
 *
 * Listeners MUST be idempotent — the dispatch is advisory fan-out, not a durable side
 * channel. A throwing listener MUST NOT block settlement; the dispatcher wraps the call in a
 * try/catch.
 */
final readonly class AgentTaskCompletedEvent
{
    public function __construct(
        public AgentTaskInfo $task,
        public string $previousStatus,
    ) {
    }
}
