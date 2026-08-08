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
            $count <= 50 => '11-50',
            $count <= 200 => '51-200',
            $count <= 1000 => '201-1000',
            default => '1000+',
        };
    }
}
