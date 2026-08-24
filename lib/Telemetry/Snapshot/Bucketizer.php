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
