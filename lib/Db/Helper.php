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
     * values. A conflict on some other unique index therefore leaves that foreign row untouched
     * and the call returns null, exactly like the previous implementation's
     * UPDATE ... WHERE $keys, which matched no row in that situation.
     *
     * The insert and the update path are told apart by the affected-rows value (1 = inserted,
     * 2 or 0 = updated). This requires the default MySQL/MariaDB affected-rows semantics: with
     * CLIENT_FOUND_ROWS enabled (PDO::MYSQL_ATTR_FOUND_ROWS in the doctrine driverOptions -
     * Pimcore does not set it), an update that leaves the row unchanged would also report 1 and
     * be misread as an insert, so such a connection is rejected with a LogicException before
     * anything is written.
     *
     * @param array<string, mixed> $data The data to be inserted or updated into the database table.
     * Array key corresponds to the database column, array value to the actual value.
     * @param string[] $keys The columns identifying the row - typically the primary key columns.
     * The values for the specified keys are read from the $data parameter. A null key value is
     * allowed and inserts normally (e.g. a new auto-increment row) but can never address an
     * existing row on the update path; a key column missing from $data entirely keeps the
     * previous behavior - the insert runs, and only a duplicate raises the misuse LogicException.
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

        if ($data === []) {
            $connection->insert($table, $data);

            return self::lastInsertId($connection);
        }

        if ($keys === []) {
            throw new LogicException('upsert() requires at least one key column');
        }

        // the insert/update split below reads the affected-rows value, so a connection with
        // CLIENT_FOUND_ROWS semantics (a no-op duplicate update also reports 1) would return a
        // stale last insert id instead of the contractual null - reject it before any write
        if (
            defined('PDO::MYSQL_ATTR_FOUND_ROWS') &&
            ($connection->getParams()['driverOptions'][\PDO::MYSQL_ATTR_FOUND_ROWS] ?? false)
        ) {
            throw new LogicException(
                'upsert() requires the default affected-rows semantics - PDO::MYSQL_ATTR_FOUND_ROWS must not be enabled on the connection'
            );
        }

        $keys = array_map(
            static fn (string $key): string => $quoteIdentifiers ? $connection->quoteIdentifier($key) : $key,
            $keys
        );

        $missingKeys = array_filter($keys, static fn (string $key): bool => !array_key_exists($key, $data));
        if ($missingKeys !== []) {
            // the guarded single statement below needs every key readable via VALUES(). The
            // previous implementation read $keys only after a duplicate, so an insert that does
            // not collide succeeds even with a key missing from $data - keep exactly that
            // behavior for such calls via the legacy two-step path.
            try {
                $connection->insert($table, $data);

                return self::lastInsertId($connection);
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
                throw new LogicException(
                    sprintf('Key "%s" passed for upsert not found in data', reset($missingKeys))
                );
            }
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        // NULL-safe <=> also keeps a null key value from ever matching an existing row
        $keysMatch = implode(' AND ', array_map(
            static fn (string $key): string => $key . ' <=> VALUES(' . $key . ')',
            $keys
        ));

        $assignments = [];
        foreach ($columns as $column) {
            if (in_array($column, $keys, true)) {
                continue;
            }
            // VALUES() and not the row alias introduced with MySQL 8.0.20, which MariaDB does not know
            $assignments[] = $column . ' = IF(' . $keysMatch . ', VALUES(' . $column . '), ' . $column . ')';
        }
        if ($assignments === []) {
            // every column is a key column - nothing to update, but the clause must not be empty
            $assignments[] = $keys[0] . ' = ' . $keys[0];
        }

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
