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
 * Immutable snapshot of agent-task state.
 *
 * `deeplink` is a host-relative link that opens the task's session in Pimcore Studio;
 * consumers prepend their own origin. `envelope` is non-null only when
 * `$status->isTerminal()` is true and the finalize step wrote one.
 *
 * @see AgentTaskStatus
 */
final readonly class AgentTaskInfo
{
    /**
     * @param array<string, mixed>|null $envelope
     */
    public function __construct(
        public string $id,
        public string $sessionId,
        public string $deeplink,
        public AgentTaskStatus $status,
        public ?array $envelope,
    ) {
    }
}
