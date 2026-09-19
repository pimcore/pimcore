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

namespace Pimcore\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Exception\DriverException;
use Exception;
use LogicException;
use PDO;
use Pimcore\Model\Element\ValidationException;

class Helper
{
    /**
     * Inserts a row, or updates it if the row identified by $keys already exists.
     *
     * This is a single INSERT ... ON DUPLICATE KEY UPDATE statement, so the row is sent to the
     * database only once, no matter which of the two paths it takes. Because ON DUPLICATE KEY
     * fires for a conflict on ANY unique index of the table - not just on $keys - every
     * assignment is guarded to only apply when the conflicting row matches the incoming $keys
     * values. A conflict on some other unique index therefore leaves that foreign row's values
     * untouched and the call returns null, like the previous implementation's
     * UPDATE ... WHERE $keys, which matched no row in that situation. If the keyed row exists and
     * the update itself would violate another unique index, the statement fails with a
     * UniqueConstraintViolationException - again the same outcome as the previous UPDATE.
     *
     * Two differences to the previous two-statement implementation follow from the single
     * statement and define the contract of this method:
     *  - $keys must be the primary key or a unique index of the table. ON DUPLICATE KEY UPDATE
     *    can only ever touch the one row the conflict was detected on, so non-unique criteria
     *    update that row if it matches and nothing else. The previous UPDATE ... WHERE $keys
     *    addressed every matching row and, as it wrote the conflicting unique value to all of
     *    them, failed with a unique constraint violation whenever the criteria matched any row
     *    but the conflicting one.
     *  - On a conflict with a different unique index, the database still runs the conflicting
     *    row's BEFORE UPDATE triggers, with NEW equal to OLD (AFTER UPDATE triggers do not run,
     *    as the row is unchanged). The previous implementation's UPDATE matched no row there
     *    and ran no trigger at all.
     *
     * The insert and the update path are told apart by the affected-rows value (1 = inserted,
     * 2 or 0 = updated). This requires the default MySQL/MariaDB affected-rows semantics: with
     * CLIENT_FOUND_ROWS enabled (PDO::MYSQL_ATTR_FOUND_ROWS in the doctrine driverOptions -
     * Pimcore does not set it), an update that leaves the row unchanged would also report 1 and
     * be misread as an insert. Such a connection, an empty $keys list and a key column missing
     * from $data all fall back to the previous two-statement implementation (INSERT, and on a
     * duplicate UPDATE ... WHERE $keys), which is independent of the connection options and keeps
     * the behavior these calls had before.
     *
     * @param array<string, mixed> $data The data to be inserted or updated into the database table.
     * Array key corresponds to the database column, array value to the actual value.
     * @param string[] $keys The columns identifying the row - typically the primary key columns.
     * The values for the specified keys are read from the $data parameter. A null key value is
     * allowed and inserts normally (e.g. a new auto-increment row) but can never address an
     * existing row on the update path.
     *
     * @return int|string|null last insert id or null if the insert was not successful or it was an update.
     */
    public static function upsert(
        Connection $connection,
        string $table,
        array $data,
        array $keys,
        bool $quoteIdentifiers = true
    ): int|string|null {
        $data = $quoteIdentifiers ? self::quoteDataIdentifiers($connection, $data) : $data;
        $keys = array_map(
            static fn (string $key): string => $quoteIdentifiers ? $connection->quoteIdentifier($key) : $key,
            $keys
        );

        // the insert/update split below reads the affected-rows value, so a connection with
        // CLIENT_FOUND_ROWS semantics (a no-op duplicate update also reports 1) would return a
        // stale last insert id instead of the contractual null
        $foundRows = defined('PDO::MYSQL_ATTR_FOUND_ROWS')
            && ($connection->getParams()['driverOptions'][PDO::MYSQL_ATTR_FOUND_ROWS] ?? false);

        // the guarded single statement needs at least one key and every key readable via
        // VALUES(); the previous implementation read $keys only after a duplicate, so an insert
        // that does not collide succeeds even without keys or with a key missing from $data
        $keysUsable = $keys !== []
            && array_filter($keys, static fn (string $key): bool => !array_key_exists($key, $data)) === [];

        if ($data === [] || $foundRows || !$keysUsable) {
            return self::legacyUpsert($connection, $table, $data, $keys);
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        // a plain = and not the NULL-safe <=>: NULL = NULL is not true, so a null key value never
        // matches an existing row - not even one storing NULL in that column - and the guard
        // below skips the row instead of writing it
        $keysMatch = implode(' AND ', array_map(
            static fn (string $key): string => $key . ' = VALUES(' . $key . ')',
            $keys
        ));

        // the key columns are assigned as well: they compare equal under the guard, but the
        // stored representation may still differ (e.g. EN vs en under a case-insensitive
        // collation) and the previous UPDATE wrote all of $data, so they are written too
        $assignments = array_map(
            // VALUES() and not the row alias introduced with MySQL 8.0.20, which MariaDB does not know
            static fn (string $column): string => $column . ' = IF(' . $keysMatch . ', VALUES(' . $column . '), ' . $column . ')',
            $columns
        );

        $sql = 'INSERT INTO ' . $table
            . ' (' . implode(', ', $columns) . ')'
            . ' VALUES (' . implode(', ', $placeholders) . ')'
            . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $assignments);

        // MySQL/MariaDB report the affected rows of INSERT ... ON DUPLICATE KEY UPDATE as 1 for an
        // inserted row, and as 2 (or 0, if the stored values already matched - or the guard above
        // skipped a row that conflicted on a non-key unique index) for an updated one.
        $affectedRows = (int) $connection->executeStatement($sql, array_values($data));

        if ($affectedRows === 1) {
            return self::lastInsertId($connection);
        }

        // Update path: it never returned an id. A null key value cannot have matched the guard,
        // so nothing was written for it - report the misuse exactly like the previous
        // implementation did when building its WHERE clause.
        foreach ($keys as $key) {
            if ($data[$key] === null) {
                throw new LogicException(sprintf('Key "%s" passed for upsert not found in data', $key));
            }
        }

        return null;
    }

    /**
     * The previous two-statement implementation: INSERT, and on a duplicate UPDATE ... WHERE $keys.
     *
     * @param array<string, mixed> $data already quoted, as are $keys
     * @param string[] $keys
     */
    private static function legacyUpsert(
        Connection $connection,
        string $table,
        array $data,
        array $keys
    ): int|string|null {
        try {
            $connection->insert($table, $data);

            return self::lastInsertId($connection);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            $criteria = [];
            foreach ($keys as $key) {
                $criteria[$key] = $data[$key] ?? throw new LogicException(sprintf('Key "%s" passed for upsert not found in data', $key));
            }

            $connection->update($table, $data, $criteria);

            return null;
        }
    }

    private static function lastInsertId(Connection $connection): int|string|null
    {
        try {
            return $connection->lastInsertId();
        } catch (DriverException) {
            return null;
        }
    }

    public static function fetchPairs(Connection $db, string $sql, array $params = [], array $types = []): array
    {
        $stmt = $db->executeQuery($sql, $params, $types);
        $data = [];
        if ($stmt instanceof Result) {
            while ($row = $stmt->fetchNumeric()) {
                $data[$row[0]] = $row[1];
            }
        }

        return $data;
    }

    public static function selectAndDeleteWhere(Connection $db, string $table, string $idColumn = 'id', string $where = ''): void
    {
        $sql = 'SELECT ' . $db->quoteIdentifier($idColumn) . '  FROM ' . $table;

        if ($where) {
            $sql .= ' WHERE ' . $where;
        }

        $idsForDeletion = $db->fetchFirstColumn($sql);

        if (!empty($idsForDeletion)) {
            $chunks = array_chunk($idsForDeletion, 1000);
            foreach ($chunks as $chunk) {
                $idString = implode(',', array_map([$db, 'quote'], $chunk));
                $db->executeStatement('DELETE FROM ' . $table . ' WHERE ' . $idColumn . ' IN (' . $idString . ')');
            }
        }
    }

    public static function queryIgnoreError(Connection $db, string $sql, array $exclusions = []): ?\Doctrine\DBAL\Result
    {
        try {
            return $db->executeQuery($sql);
        } catch (Exception $e) {
            foreach ($exclusions as $exclusion) {
                if ($e instanceof $exclusion) {
                    throw new ValidationException($e->getMessage(), 0, $e);
                }
            }
            // we simply ignore the error
        }

        return null;
    }

    /**
     * @deprecated mixed $value is deprecated and will be changed to string in the next major version.
     */
    public static function quoteInto(Connection $db, string $text, mixed $value, ?int $count = null): array|string
    {
        if ($count === null) {
            return str_replace('?', $db->quote((string)$value), $text);
        }

        return implode($db->quote((string)$value), explode('?', $text, $count + 1));
    }

    public static function escapeLike(string $like): string
    {
        return str_replace(['_', '%'], ['\\_', '\\%'], $like);
    }

    public static function quoteDataIdentifiers(Connection $db, array $data): array
    {
        $newData = [];
        foreach ($data as $key => $value) {
            $newData[$db->quoteIdentifier($key)] = $value;
        }

        return $newData;
    }
}
