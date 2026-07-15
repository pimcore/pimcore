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
 * Immutable request to create an agent task.
 *
 * `initiator` is an opaque identifier (e.g. `"studio"`, `"copilot"`, `"collab"`) that
 * routes the resulting session to an initiator-specific access resolver.
 * `initiatorContext` carries whatever payload that initiator wants a resolver (or an
 * MCP tool) to see later — e.g. collab passes `['threadId' => 42]`.
 */
final readonly class AgentTaskRequest
{
    /**
     * @param list<array{type: string, id: int}>|null $elements
     * @param array<string, mixed>|null               $outputSchema     JSON Schema
     * @param array<string, mixed>                    $initiatorContext
     * @param ?string                                 $origin           Free-form provenance label stored on the created
     *                                                                  session (e.g. `copilot-importProducts`); the
     *                                                                  implementation normalizes it (trimmed, empty
     *                                                                  string becomes null, truncated to 190 chars).
     */
    public function __construct(
        public string $agentName,
        public string $instructions,
        public string $initiator,
        public ?int $actingUserId = null,
        public ?int $designatedUserId = null,
        public ?array $elements = null,
        public ?array $outputSchema = null,
        public ?int $deadlineMinutes = null,
        public ?int $maxAutoContinues = null,
        public array $initiatorContext = [],
        public ?string $origin = null,
    ) {
    }
}
