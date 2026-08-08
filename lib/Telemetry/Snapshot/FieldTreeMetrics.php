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

use function count;
use function max;

/**
 * Immutable, additive metrics for one data-model field tree (a class, field collection, or object
 * brick). Instances are combined with {@see self::combine()} to aggregate the whole model. Holds
 * only counts, a max depth, per-field-type tallies, and capability flags - content-never.
 *
 * @internal
 */
final readonly class FieldTreeMetrics
{
    /**
     * @param array<string, int> $typeUsage field-type identifier => occurrence count
     */
    public function __construct(
        public int $fieldCount = 0,
        public int $maxDepth = 0,
        public array $typeUsage = [],
        public int $relationFieldCount = 0,
        public bool $usesLocalizedfields = false,
        public bool $usesBlocks = false,
        public bool $usesClassificationstore = false,
        public bool $usesCalculatedValue = false,
        public bool $usesAdvancedRelations = false,
    ) {
    }

    public function combine(self $other): self
    {
        $typeUsage = $this->typeUsage;
        foreach ($other->typeUsage as $type => $count) {
            $typeUsage[$type] = ($typeUsage[$type] ?? 0) + $count;
        }

        return new self(
            $this->fieldCount + $other->fieldCount,
            max($this->maxDepth, $other->maxDepth),
            $typeUsage,
            $this->relationFieldCount + $other->relationFieldCount,
            $this->usesLocalizedfields || $other->usesLocalizedfields,
            $this->usesBlocks || $other->usesBlocks,
            $this->usesClassificationstore || $other->usesClassificationstore,
            $this->usesCalculatedValue || $other->usesCalculatedValue,
            $this->usesAdvancedRelations || $other->usesAdvancedRelations,
        );
    }

    public function distinctTypeCount(): int
    {
        return count($this->typeUsage);
    }
}
