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

use Doctrine\DBAL\Connection;
use Throwable;
use function is_numeric;
use function max;

/**
 * Reads the session's executed-statement counter, so the snapshot can report how much database work
 * it did ({@see SnapshotBuilder} emits it as `meta.db_statements`).
 *
 * This deliberately uses MySQL's own `Questions` session counter rather than a DBAL middleware: a
 * middleware would wrap every statement in the entire application, permanently, to serve a metric
 * that is produced once a day. Reading the counter costs one statement and only happens inside the
 * snapshot. It also counts *all* work - including queries issued outside
 * {@see SnapshotQueryRunner}, such as bundle enumeration - which is exactly the blind spot a
 * collector-level counter would miss.
 *
 * Every value is a plain integer; nothing here can carry customer content. Failures degrade to
 * `null`, which simply omits the metric.
 *
 * @internal
 */
final readonly class StatementCounter
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Statements executed on this session so far, or null when the counter is unavailable.
     */
    public function read(): ?int
    {
        try {
            $row = $this->connection->fetchAssociative("SHOW SESSION STATUS LIKE 'Questions'");
        } catch (Throwable) {
            return null;
        }

        $value = $row['Value'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Statements executed between two {@see self::read()} values, discounting the closing read
     * itself so the figure reflects only the work done in between.
     */
    public function between(?int $before, ?int $after): ?int
    {
        if ($before === null || $after === null) {
            return null;
        }

        return max(0, $after - $before - 1);
    }
}
