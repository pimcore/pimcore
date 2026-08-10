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
 * Maps an exact count to a coarse bucket so the shape of a customer's catalog or data
 * model cannot be used to re-identify them. Shared by all snapshot collectors so every
 * count is bucketed identically.
 *
 * The scale is one order of magnitude per bucket, which is the resolution the fleet questions actually
 * need - "is this a small install or an enterprise catalog" - while staying coarse enough that no
 * bucket can single an instance out. It runs to 1M+ because real enterprise catalogs reach millions of
 * elements; a scale that saturated earlier would report the largest and the merely-large installations
 * as the same size.
 *
 * The 101-1000 decade is the one exception, split at 500: a large share of installs sit in that range,
 * where a full order of magnitude is too blunt to tell a small project from a mid-sized one.
 *
 * Ordering matters. The arms below are evaluated top-down, so they must stay ascending - moving the
 * 1000 arm above the 500 one would not fail anything loudly, it would simply make `101-500`
 * unreachable and silently re-label every instance in that range.
 *
 * @internal
 */
final readonly class Bucketizer
{
    public function bucket(int $count): string
    {
        return match (true) {
            $count <= 0 => '0',
            $count <= 10 => '1-10',
            $count <= 100 => '11-100',
            $count <= 500 => '101-500',
            $count <= 1000 => '501-1000',
            $count <= 10000 => '1001-10000',
            $count <= 100000 => '10001-100000',
            $count <= 1000000 => '100001-1000000',
            default => '1000000+',
        };
    }
}
