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
 * Decides whether `$userId` may access a task-origin chat session. Implementations are
 * routed to by initiator string (e.g. `"studio"`, `"collab"`); each integration registers
 * its own resolver rather than teaching the agent-bundle every possible ownership model.
 *
 * Implementations MUST be fail-closed: on any unexpected state, deny access.
 */
interface TaskSessionAccessResolverInterface
{
    public function canAccess(TaskSessionRef $ref, int $userId): bool;
}
