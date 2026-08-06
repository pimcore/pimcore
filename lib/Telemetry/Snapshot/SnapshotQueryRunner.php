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
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Exception;
use function ltrim;
use function preg_replace;
use function sprintf;
use function str_starts_with;
use function strtoupper;

/**
 * Runs snapshot-collector SELECTs with a per-statement execution-time cap.
 *
 * The snapshot is produced by the maintenance job, off the request path - but its heaviest
 * collectors still run unbounded aggregates over the largest tables (e.g. a path-depth MAX/AVG
 * over every object row, or a GROUP BY on an unindexed column). On a big instance one of those can
 * run for minutes and evict the buffer pool. This runner caps each statement so a pathological
 * table aborts fast; the driver then throws and the collector's existing try/catch degrades that
 * one metric to 0/unknown rather than stalling the whole maintenance run.
 *
 * The cap is expressed per statement (no session state that could leak into sibling maintenance
 * tasks sharing the connection) using the platform-native mechanism:
 *   - MariaDB: `SET STATEMENT max_statement_time=<seconds> FOR <select>`
 *   - MySQL:   the `MAX_EXECUTION_TIME(<milliseconds>)` optimizer hint
 * Both only apply to read-only SELECTs (all snapshot queries are). A non-positive timeout, or a
 * non-SELECT statement, disables the cap and the SQL is passed through unchanged.
 *
 * @internal
 */
final readonly class SnapshotQueryRunner
{
    public function __construct(
        private Connection $connection,
        private int $timeoutSeconds,
    ) {
    }

    /**
     * @param array<int|string, mixed> $params
     */
    public function fetchOne(string $sql, array $params = []): mixed
    {
        return $this->connection->fetchOne($this->timebox($sql), $params);
    }

    /**
     * @param array<int|string, mixed> $params
     *
     * @return array<string, mixed>|false
     */
    public function fetchAssociative(string $sql, array $params = []): array|false
    {
        return $this->connection->fetchAssociative($this->timebox($sql), $params);
    }

    /**
     * @param array<int|string, mixed> $params
     *
     * @return array<int|string, mixed>
     */
    public function fetchAllKeyValue(string $sql, array $params = []): array
    {
        return $this->connection->fetchAllKeyValue($this->timebox($sql), $params);
    }

    public function quoteIdentifier(string $identifier): string
    {
        return $this->connection->quoteIdentifier($identifier);
    }

    /**
     * Pure, platform-parameterised builder so the wrapping is unit-testable without a connection.
     */
    public static function buildTimeoutSql(string $sql, bool $isMariaDb, int $timeoutSeconds): string
    {
        // Both mechanisms only bound read-only SELECTs; leave anything else (and a disabled cap) as-is.
        if ($timeoutSeconds <= 0 || !str_starts_with(strtoupper(ltrim($sql)), 'SELECT')) {
            return $sql;
        }

        if ($isMariaDb) {
            return sprintf('SET STATEMENT max_statement_time=%d FOR %s', $timeoutSeconds, $sql);
        }

        // MySQL: inject the hint immediately after the leading SELECT keyword.
        $hint = sprintf('SELECT /*+ MAX_EXECUTION_TIME(%d) */', $timeoutSeconds * 1000);

        return preg_replace('/^\s*SELECT\b/i', $hint, $sql, 1) ?? $sql;
    }

    private function timebox(string $sql): string
    {
        if ($this->timeoutSeconds <= 0) {
            return $sql;
        }

        return self::buildTimeoutSql($sql, $this->isMariaDb(), $this->timeoutSeconds);
    }

    private function isMariaDb(): bool
    {
        try {
            return $this->connection->getDatabasePlatform() instanceof MariaDBPlatform;
        } catch (Exception) {
            // Unknown platform: fall back to the MySQL hint form (a bare SELECT if it is neither).
            return false;
        }
    }
}
