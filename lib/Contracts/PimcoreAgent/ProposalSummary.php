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
 * Compact view of a pending proposal produced by an agent task, suitable for rendering
 * a summary entry ("Agent proposed 3 changes …") in a downstream consumer's timeline
 * without exposing the full proposal payload.
 *
 * `kind` is a short slug (e.g. `"dataObject.update"`, `"asset.create"`); the exact set
 * is defined by the producing implementation. `targetId` accepts `int|string` because
 * different Pimcore element types use different id shapes, and `null` for proposals
 * whose target does not yet exist (e.g. `"asset.create"` before the create is applied).
 */
final readonly class ProposalSummary
{
    public function __construct(
        public string $id,
        public string $kind,
        public string $targetType,
        public int|string|null $targetId,
        public string $label,
    ) {
    }
}
