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
 * Immutable terminal-state envelope for an agent task returned by
 * `AgentTaskServiceInterface::waitFor()`.
 */
final readonly class AgentTaskResult
{
    /**
     * @param array<string, mixed> $envelope
     */
    public function __construct(
        public string $id,
        public AgentTaskStatus $status,
        public array $envelope,
    ) {
    }
}
