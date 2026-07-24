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

/**
 * One resolver per initiator. Given the initiator identifier, its opaque per-task context,
 * and the caller identity, returns the Pimcore MCP tool-group slugs a task originating from
 * this initiator should have attached to its session (on top of the agent's own
 * configuration).
 *
 * The bundle calls this at every dispatch — task start, `continueTask`, and each
 * auto-continue turn — so the resolver is authoritative live authz: revoking or widening an
 * initiator's grants takes effect on the next dispatch without any persisted state to
 * migrate. Return `[]` to attach no extras.
 *
 * The returned slugs are the ATTACHED set, not an allowlist to intersect with a caller
 * request: there is no caller-supplied field to filter. Any per-task variation the
 * initiator wants to express should be encoded in `$initiatorContext` and read here.
 *
 * Consumers register an implementation as a service tagged
 * `pimcore_agent.mcp_server_resolver` with an `initiator` attribute equal to
 * `initiator()`; the bundle's compiler pass builds the registry.
 *
 * Slugs must match `[a-z][a-z0-9]*(-[a-z0-9]+)*` (ASCII-only — the bundle interpolates
 * them into agent-server URL paths). Malformed slugs are silently dropped by the bundle.
 */
interface InitiatorMcpServerResolverInterface
{
    /**
     * The initiator identifier this resolver handles. MUST match `AgentTaskRequest::$initiator`
     * of every task this resolver applies to.
     */
    public function initiator(): string;

    /**
     * @param array<string, mixed> $initiatorContext Verbatim from `AgentTaskRequest::$initiatorContext`
     *                                               at task creation; preserved unchanged across
     *                                               `continueTask` and auto-continue.
     * @param int                  $callerUserId     The effective user identity the task runs as
     *                                               (persisted `AgentTask::$actingUserId` or the
     *                                               agent's execution user).
     *
     * @return list<string> MCP tool-group slugs to attach; `[]` to attach none.
     */
    public function allowedMcpServers(string $initiator, array $initiatorContext, int $callerUserId): array;
}
