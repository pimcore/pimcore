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

use Throwable;

/**
 * Consumer-facing facade for headless agent tasks. PHP modules (Copilot automation actions,
 * collab-bundle task delegation, any future consumer) delegate work to an agent through this
 * interface instead of driving the Studio chat UI.
 *
 * Concrete implementations may throw specific exception types (see the agent-bundle
 * implementation); at the contract level `@throws \Throwable` covers all failure modes.
 */
interface AgentTaskServiceInterface
{
    /**
     * Create the task's session, persist the task row, and dispatch its first turn.
     * Blocks only for setup (milliseconds), not for the duration of the run.
     *
     * @throws Throwable on setup failure (unknown agent, dispatch rejected, concurrency cap
     *                   reached, invalid identity). Implementations SHOULD throw specific
     *                   types.
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
     * @throws Throwable when the previous task is not yet terminal, the session already has
     *                   another non-terminal task, or dispatch is rejected.
     */
    public function continueTask(
        string $previousTaskId,
        string $instructions,
        ?int $deadlineMinutes = null,
        ?int $maxAutoContinues = null,
        ?array $outputSchema = null,
    ): AgentTaskInfo;

    /**
     * Read the current state. Implementations throw when the task does not exist.
     *
     * @throws Throwable
     */
    public function get(string $taskId): AgentTaskInfo;

    /**
     * Poll until the task reaches a terminal state or the timeout elapses. Polls the status
     * only — no connection to the agent-server is held.
     *
     * On timeout, the task keeps running. Call `cancel()` explicitly to also kill the run.
     *
     * @throws Throwable on timeout, or when the task does not exist.
     */
    public function waitFor(string $taskId, int $timeoutSeconds): AgentTaskResult;

    /**
     * Cancel a non-terminal task (best-effort). A task that is already terminal is a silent
     * no-op, so `cancel()` is safe to call more than once.
     */
    public function cancel(string $taskId): void;
}
