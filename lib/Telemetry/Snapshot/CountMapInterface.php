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

/**
 * Gives the `name => count` maps in the snapshot one deterministic, bounded shape. Behind an interface
 * so a collector can be tested against a scripted ranking.
 *
 * @internal
 */
interface CountMapInterface
{
    /**
     * @param array<string, int> $counts
     *
     * @return array<string, int> ranked by count, highest first, ties by key; everything beyond
     *                            $limit summed into $otherKey
     */
    public function ranked(array $counts, int $limit = PHP_INT_MAX, string $otherKey = 'other'): array;
}
