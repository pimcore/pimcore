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
 * Decides which Pimcore MCP tool-group slugs get attached to a task session, given the
 * initiator's opaque per-task context and the caller identity. Implementations are routed
 * to by initiator string (e.g. `"studio"`, `"collab"`); each integration registers its own
 * resolver rather than teaching the agent-bundle every initiator's tool matrix.
 *
 * Called at every dispatch (start, `continueTask`, auto-continue) so grants are live authz:
 * revoking or widening an initiator's tool set takes effect on the next turn without any
 * persisted state to migrate. Return `[]` to attach no extras.
 *
 * The returned slugs are the ATTACHED set, not an allowlist to intersect with a caller
 * request: there is no caller-supplied field to filter. Any per-task variation the
 * initiator wants to express should be encoded in `$initiatorContext` (verbatim from
 * `AgentTaskRequest::$initiatorContext`) and read here.
 *
 * ## Trust model
 *
 * `AgentTaskServiceInterface::start()` is a bundle-integration facade, not a user-facing
 * endpoint. The `$initiator` string on `AgentTaskRequest` is set by the integrating bundle
 * itself (e.g. collab-bundle hardcodes `initiator: 'collab'` on every delegation), never
 * derived from untrusted caller input. Bundle integrations that forward user input into
 * `$initiator` bypass this trust model and let a caller pick which resolver runs. Don't do
 * that.
 *
 * Given that model, this resolver is called for tasks whose initiator identity has already
 * been vetted by the integrating bundle. `$initiatorContext + $callerUserId` are what a
 * resolver reasons about — the initiator identity itself is not passed, because the resolver
 * IS the resolver for its initiator (routed by service-tag attribute).
 *
 * Slugs must match `[a-z][a-z0-9]*(-[a-z0-9]+)*` (ASCII-only — the bundle interpolates
 * them into agent-server URL paths). Malformed slugs are silently dropped by the bundle.
 */
interface InitiatorMcpServerResolverInterface
{
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
    public function allowedMcpServers(array $initiatorContext, int $callerUserId): array;
}
