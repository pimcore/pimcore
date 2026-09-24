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

namespace Pimcore\Telemetry\Snapshot\Statistics;

/**
 * Hierarchy-depth statistics for one element kind, expressed in path-segment (slash-count) semantics:
 * a top-level element has depth 1. Both values are small integers - content-never.
 *
 * @internal
 */
final readonly class TreeDepth
{
    public function __construct(
        public int $max = 0,
        public int $avg = 0,
    ) {
    }
}
