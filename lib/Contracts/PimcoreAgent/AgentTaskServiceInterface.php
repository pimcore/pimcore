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

namespace Pimcore\Contracts\PimcoreAgent;

use Pimcore\Contracts\PimcoreAgent\Exception\AgentTaskExceptionInterface;
use Pimcore\Contracts\PimcoreAgent\Exception\InvalidTaskRequestException;
use Pimcore\Contracts\PimcoreAgent\Exception\TaskDispatchException;
use Pimcore\Contracts\PimcoreAgent\Exception\TaskWaitTimeoutException;
use Pimcore\Contracts\PimcoreAgent\Exception\TooManyTasksException;

/**
 * Consumer-facing facade for headless agent tasks. PHP modules (Copilot automation actions,
 * collab-bundle task delegation, any future consumer) delegate work to an agent through this
 * interface instead of driving the Studio chat UI.
 *
 * All documented failure modes are `AgentTaskExceptionInterface` — consumers catch that to
 * react to any agent-task-related error, or the specific subclasses (below) for finer control.
 */
interface AgentTaskServiceInterface
{
    /**
     * Create the task's session, persist the task row, and dispatch its first turn.
     * Blocks only for setup (milliseconds), not for the duration of the run.
     *
     * @throws InvalidTaskRequestException Unknown agent, invalid identity, or other fail-closed
     *                                     validation of the request.
     * @throws TooManyTasksException The implementation's concurrency cap for running tasks is
     *                               already reached.
     * @throws TaskDispatchException The dispatch step rejected the request or failed on transport;
     *                               the task row was marked terminal before this is thrown.
     */
    public function start(AgentTaskRequest $request): AgentTaskInfo;

    /**
     * Start a new task on the EXISTING session of a prior (now-terminal) task — the resume
     * primitive. Reuses `sessionId`, `agentName`, and identity from the previous task; only
     * `$instructions` (and optional overrides) come from this call.
     *
     * @param array<string, mixed>|null $outputSchema JSON Schema for the continuation's
     *                                                finalize output; null to require none
     *
     * @throws InvalidTaskRequestException The previous task is not yet terminal, or the session
     *                                     already has another non-terminal task.
     * @throws TooManyTasksException The implementation's concurrency cap is already reached.
     * @throws TaskDispatchException The dispatch step rejected the request or failed on transport.
     */
    public function continueTask(
        string $previousTaskId,
        string $instructions,
        ?int $deadlineMinutes = null,
        ?int $maxAutoContinues = null,
        ?array $outputSchema = null,
    ): AgentTaskInfo;

    /**
     * Read the current state.
     *
     * @throws AgentTaskExceptionInterface when the task does not exist.
     */
    public function get(string $taskId): AgentTaskInfo;

    /**
     * Poll until the task reaches a terminal state or the timeout elapses. Polls the status
     * only — no connection to the agent-server is held.
     *
     * On timeout, the task keeps running. Call `cancel()` explicitly to also kill the run.
     *
     * @throws TaskWaitTimeoutException The task did not reach a terminal state within `$timeoutSeconds`.
     * @throws AgentTaskExceptionInterface when the task does not exist.
     */
    public function waitFor(string $taskId, int $timeoutSeconds): AgentTaskResult;

    /**
     * Cancel a non-terminal task (best-effort). A task that is already terminal is a silent
     * no-op, so `cancel()` is safe to call more than once.
     */
    public function cancel(string $taskId): void;
}
