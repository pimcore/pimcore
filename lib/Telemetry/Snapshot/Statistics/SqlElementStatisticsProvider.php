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

use Exception;
use Pimcore\Telemetry\Snapshot\ElementTypeCounts;
use Pimcore\Telemetry\Snapshot\SnapshotQueryRunner;
use function is_array;
use function is_numeric;
use function round;

/**
 * Always-available {@see ElementStatisticsProviderInterface} backed by SQL aggregates over the
 * element tables, run through the time-boxed {@see SnapshotQueryRunner}. One `GROUP BY type` scan per
 * table for the type counts, and a single combined MAX/AVG scan for depth. Every query degrades to a
 * zero/empty result on failure so the snapshot is never broken.
 *
 * @internal
 */
final readonly class SqlElementStatisticsProvider implements ElementStatisticsProviderInterface
{
    public function __construct(
        private SnapshotQueryRunner $queryRunner,
    ) {
    }

    public function typeCounts(ElementKind $kind): ElementTypeCounts
    {
        try {
            $rows = $this->queryRunner->fetchAllKeyValue(
                'SELECT ' . $this->quote('type') . ', COUNT(*) FROM ' . $this->quote($kind->table())
                . ' GROUP BY ' . $this->quote('type')
            );
        } catch (Exception) {
            return new ElementTypeCounts();
        }

        $byType = [];
        foreach ($rows as $type => $count) {
            $byType[(string) $type] = (int) $count;
        }

        return new ElementTypeCounts($byType);
    }

    public function treeDepth(ElementKind $kind): TreeDepth
    {
        $path = $this->quote('path');
        $depth = 'LENGTH(' . $path . ') - LENGTH(REPLACE(' . $path . ", '/', ''))";

        try {
            $row = $this->queryRunner->fetchAssociative(
                'SELECT MAX(d) AS max_d, AVG(d) AS avg_d FROM (SELECT ' . $depth . ' AS d FROM '
                . $this->quote($kind->table()) . ') t'
            );
        } catch (Exception) {
            $row = false;
        }

        if (!is_array($row)) {
            return new TreeDepth();
        }

        return new TreeDepth(
            is_numeric($row['max_d'] ?? null) ? (int) $row['max_d'] : 0,
            is_numeric($row['avg_d'] ?? null) ? (int) round((float) $row['avg_d']) : 0,
        );
    }

    public function objectsWithVariants(): int
    {
        return $this->intResult(
            'SELECT COUNT(DISTINCT ' . $this->quote('parentId') . ') FROM ' . $this->quote('objects')
            . ' WHERE ' . $this->quote('type') . " = 'variant'"
        );
    }

    public function maxVariantsPerObject(): int
    {
        return $this->intResult(
            'SELECT MAX(c) FROM (SELECT COUNT(*) c FROM ' . $this->quote('objects')
            . ' WHERE ' . $this->quote('type') . " = 'variant'"
            . ' GROUP BY ' . $this->quote('parentId') . ') t'
        );
    }

    public function maxObjectFanout(): int
    {
        return $this->intResult(
            'SELECT MAX(c) FROM (SELECT COUNT(*) c FROM ' . $this->quote('objects')
            . ' GROUP BY ' . $this->quote('parentId') . ') t'
        );
    }

    private function intResult(string $sql): int
    {
        try {
            $value = $this->queryRunner->fetchOne($sql);

            return is_numeric($value) ? (int) $value : 0;
        } catch (Exception) {
            return 0;
        }
    }

    private function quote(string $identifier): string
    {
        return $this->queryRunner->quoteIdentifier($identifier);
    }
}
