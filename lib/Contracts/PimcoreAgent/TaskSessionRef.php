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
 * Identifies a task-origin chat session for access-control purposes. `initiator` routes the
 * lookup to the matching `TaskSessionAccessResolverInterface`; `initiatorContext` carries
 * whatever payload the initiator attached (e.g. collab passes `['threadId' => 42]`).
 */
final readonly class TaskSessionRef
{
    /**
     * @param array<string, mixed>|null $initiatorContext
     */
    public function __construct(
        public string $sessionId,
        public string $initiator,
        public ?array $initiatorContext,
    ) {
    }
}
