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

use function array_sum;
use function count;

/**
 * The result of a single `SELECT type, COUNT(*) ... GROUP BY type` over an element table
 * (objects/assets/documents): a `type => count` map from which the total, any per-type count, and
 * the distinct-type variety are all derived without another scan. Content-never - `type` values are
 * Pimcore's own element-type identifiers (image/video/object/variant/page/...), never customer data.
 *
 * @internal
 */
final readonly class ElementTypeCounts
{
    /**
     * @param array<string, int> $byType element-type identifier => row count
     */
    public function __construct(
        private array $byType = [],
    ) {
    }

    public function total(): int
    {
        return (int)array_sum($this->byType);
    }

    public function ofType(string $type): int
    {
        return $this->byType[$type] ?? 0;
    }

    public function distinctTypes(): int
    {
        return count($this->byType);
    }
}
