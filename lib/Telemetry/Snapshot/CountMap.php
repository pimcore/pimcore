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

namespace Pimcore\Telemetry\Snapshot;

use function array_slice;
use function array_sum;
use function count;
use function strcmp;
use function uksort;

/**
 * Deterministic shape for the `name => count` maps in the snapshot: ranked by count, highest first, ties
 * by key, and bounded - everything beyond the limit is summed into one residual key so a map can never
 * grow with the installation's vocabulary.
 *
 * @internal
 */
final readonly class CountMap implements CountMapInterface
{
    /**
     * @param array<string, int> $counts
     *
     * @return array<string, int>
     */
    public function ranked(array $counts, int $limit = PHP_INT_MAX, string $otherKey = 'other'): array
    {
        uksort($counts, static fn (string $a, string $b): int => ($counts[$b] <=> $counts[$a]) ?: strcmp($a, $b));

        if (count($counts) <= $limit) {
            return $counts;
        }

        $kept = array_slice($counts, 0, $limit, true);
        $other = array_sum(array_slice($counts, $limit, null, true)) + ($kept[$otherKey] ?? 0);
        unset($kept[$otherKey]);
        $kept[$otherKey] = $other;

        return $kept;
    }
}
