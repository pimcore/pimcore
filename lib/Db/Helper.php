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
     * Inserts a row, or updates the rows matching $keys if the insert hits a unique constraint.
     *
     * This runs an INSERT and, on a duplicate, an UPDATE ... WHERE $keys - two statements and an
     * exception on every update of an existing row. Callers whose $keys are the primary key or a
     * unique index of the table should use {@see self::upsertByUniqueKey()}, which does the same
     * in a single statement.
     *
     * @param array<string, mixed> $data The data to be inserted or updated into the database table.
     * Array key corresponds to the database column, array value to the actual value.
     * @param string[] $keys If the table needs to be updated, the columns listed in this parameter will be used as criteria/condition for the where clause.
     * Typically, these are the primary key columns.
     * The values for the specified keys are read from the $data parameter.
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
        try {
            $data = $quoteIdentifiers ? self::quoteDataIdentifiers($connection, $data) : $data;
            $connection->insert($table, $data);

            try {
                return $connection->lastInsertId();
            } catch (DriverException) {
                return null;
            }
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $exception) {
            $critera = [];
            foreach ($keys as $key) {
                $key = $quoteIdentifiers ? $connection->quoteIdentifier($key) : $key;
                $critera[$key] = $data[$key] ?? throw new LogicException(sprintf('Key "%s" passed for upsert not found in data', $key));
            }

            $connection->update($table, $data, $critera);

            return null;
        }
    }

    /**
     * Inserts a row, or updates it if the row identified by $uniqueKeyColumns already exists.
     *
     * This is a single INSERT ... ON DUPLICATE KEY UPDATE statement, so the row is sent to the
     * database only once, no matter which of the two paths it takes. The return value is the
     * same as for {@see self::upsert()}: the last insert id on an insert, null on an update.
     *
     * $uniqueKeyColumns must be the primary key or a unique index of the table, and the method
     * is meant for tables where that is the only unique index the data can collide on - as the
     * class and brick query tables, the classification store tables, properties or versions.
     * The class store and localized store tables are not such tables: a field marked unique
     * gets a unique index there (u_index_*), and neither are objects, assets, documents and
     * tags with their second unique index. ON DUPLICATE KEY
     * UPDATE can only ever touch the one row the conflict was detected on, so non-unique
     * criteria would update that row if it matches and nothing else (where upsert() addresses
     * every row matching its WHERE clause). On a table with another unique index the conflict
     * may be detected on that index instead; as a safety net every assignment is guarded to only
     * apply when the conflicting row matches the incoming key values, so such a foreign row is
     * assigned its own values and the call returns null, like upsert()'s UPDATE ... WHERE, which
     * matches no row in that situation. The database does however still run the foreign row's
     * UPDATE triggers with NEW equal to OLD (BEFORE UPDATE always, AFTER UPDATE depending on the
     * server version), which upsert() never did, and a BEFORE UPDATE trigger that assigns to NEW
     * writes to the row. This is why the core DAOs use {@see self::updateOrInsert()} or
     * upsert() for the tables named above. If the keyed
     * row exists and the update itself would violate another unique index, the statement fails
     * with a UniqueConstraintViolationException, the same outcome as upsert()'s UPDATE.
     *
     * The insert and the update path are told apart by the affected-rows value (1 = inserted,
     * 2 or 0 = updated). This requires the default MySQL/MariaDB affected-rows semantics: with
     * CLIENT_FOUND_ROWS enabled (PDO::MYSQL_ATTR_FOUND_ROWS, or MYSQLI_CLIENT_FOUND_ROWS in the
     * mysqli 'flags', in the doctrine driverOptions - Pimcore does not set either), an update
     * that leaves the row unchanged would also report 1 and be misread as an insert. Such a
     * connection, an empty $uniqueKeyColumns list and a key
     * column missing from $data all fall back to {@see self::upsert()}, which is independent of
     * the connection options.
     *
     * @param array<string, mixed> $data The data to be inserted or updated into the database table.
     * Array key corresponds to the database column, array value to the actual value.
     * @param string[] $uniqueKeyColumns The columns of the primary key or of a unique index of
     * the table, identifying the row. The values are read from the $data parameter. A null key
     * value is allowed and inserts normally (e.g. a new auto-increment row) but can never address
     * an existing row on the update path.
     *
     * @return int|string|null last insert id or null if the insert was not successful or it was an update.
     */
    public static function upsertByUniqueKey(
        Connection $connection,
        string $table,
        array $data,
        array $uniqueKeyColumns,
        bool $quoteIdentifiers = true
    ): int|string|null {
        // the insert/update split below reads the affected-rows value, so a connection with
        // CLIENT_FOUND_ROWS semantics (a no-op duplicate update also reports 1) would return a
        // stale last insert id instead of the contractual null
        $foundRows = self::hasFoundRowsSemantics($connection);

        $quotedData = $quoteIdentifiers ? self::quoteDataIdentifiers($connection, $data) : $data;
        $keys = array_map(
            static fn (string $key): string => $quoteIdentifiers ? $connection->quoteIdentifier($key) : $key,
            $uniqueKeyColumns
        );

        // the guarded single statement needs at least one key and every key readable via
        // VALUES(); upsert() reads its keys only after a duplicate, so an insert that does not
        // collide succeeds there even without keys or with a key missing from $data
        $keysUsable = $keys !== []
            && array_filter($keys, static fn (string $key): bool => !array_key_exists($key, $quotedData)) === [];

        if ($quotedData === [] || $foundRows || !$keysUsable) {
            return self::upsert($connection, $table, $data, $uniqueKeyColumns, $quoteIdentifiers);
        }

        $columns = array_keys($quotedData);
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
        // collation) and upsert()'s UPDATE writes all of $data, so they are written too
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
        $affectedRows = (int) $connection->executeStatement($sql, array_values($quotedData));

        if ($affectedRows === 1) {
            try {
                return $connection->lastInsertId();
            } catch (DriverException) {
                return null;
            }
        }

        // Update path: it never returned an id. A null key value cannot have matched the guard,
        // so nothing was written for it - report the misuse exactly like upsert() does when
        // building its WHERE clause.
        foreach ($keys as $key) {
            if ($quotedData[$key] === null) {
                throw new LogicException(sprintf('Key "%s" passed for upsert not found in data', $key));
            }
        }

        return null;
    }

    /**
     * Updates the rows matching $keys, or inserts the row if none was changed.
     *
     * The rows addressed are those of {@see self::upsert()} - $keys are the criteria of an
     * UPDATE ... WHERE, no other row is ever touched, no trigger runs on a row the criteria do
     * not match - but the UPDATE runs first. Where the row usually exists and changes, as for
     * the main element tables whose DAOs insert the row in create() before every update(), or
     * the class store tables on an update, this is a single statement without the duplicate
     * key exception, on any connection: with CLIENT_FOUND_ROWS the UPDATE of an unchanged row
     * reports 1, which is equally correct here.
     *
     * If the UPDATE changes no row, the row is missing or already holds these values, and the
     * two cannot be told apart by the affected-rows value. The INSERT is tried then, exactly as
     * upsert() does; if it fails on the duplicate the row exists now - whether it was unchanged
     * all along or inserted concurrently since the UPDATE - and a second UPDATE, restricted to
     * a row whose values differ from $data, applies the data in the concurrent case and
     * matches nothing in the unchanged one. So a concurrent insert is never lost, and the
     * UPDATE triggers of an unchanged row run once, as they did with upsert().
     *
     * Where the row usually does not exist, upsert() or {@see self::upsertByUniqueKey()} are
     * the better choice, as the UPDATE would be a wasted round trip. A null or missing key value
     * skips the UPDATE and goes to upsert().
     *
     * The one observable difference to upsert(): on the update path of a row whose values
     * change, upsert()'s INSERT failed on the duplicate and ran the table's BEFORE INSERT
     * triggers first (their effects rolled back with the failed statement); this method runs
     * them only when it actually tries to insert.
     *
     * @param array<string, mixed> $data The data to be inserted or updated into the database table.
     * Array key corresponds to the database column, array value to the actual value.
     * @param string[] $keys The columns used as criteria/condition for the where clause, typically
     * the primary key columns. The values for the specified keys are read from the $data parameter.
     *
     * @return int|string|null last insert id if a row was inserted, null if a row was updated.
     */
    public static function updateOrInsert(
        Connection $connection,
        string $table,
        array $data,
        array $keys,
        bool $quoteIdentifiers = true
    ): int|string|null {
        $quotedData = $quoteIdentifiers ? self::quoteDataIdentifiers($connection, $data) : $data;

        // a null or missing key value (e.g. the id of a new auto-increment row) cannot match a
        // row, so the UPDATE is skipped and upsert() handles the call exactly as before
        $criteria = [];
        foreach ($keys as $key) {
            $key = $quoteIdentifiers ? $connection->quoteIdentifier($key) : $key;
            if (!isset($quotedData[$key])) {
                $criteria = [];

                break;
            }
            $criteria[$key] = $quotedData[$key];
        }

        if ($criteria === []) {
            return self::upsert($connection, $table, $data, $keys, $quoteIdentifiers);
        }

        if ((int) $connection->update($table, $quotedData, $criteria) > 0) {
            return null;
        }

        // 0 changed rows: the row is missing, or it already holds these values
        try {
            $connection->insert($table, $quotedData);

            try {
                return $connection->lastInsertId();
            } catch (DriverException) {
                return null;
            }
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            // the row exists now: unchanged all along, or inserted concurrently since the
            // UPDATE above - only a row whose values differ from $data is written, so the
            // concurrent insert is not lost and an unchanged row is not updated a second time
            $columns = array_keys($quotedData);
            $connection->executeStatement(
                'UPDATE ' . $table
                . ' SET ' . implode(', ', array_map(static fn (string $column): string => $column . ' = ?', $columns))
                . ' WHERE ' . implode(' AND ', array_map(static fn (string $key): string => $key . ' = ?', array_keys($criteria)))
                . ' AND NOT (' . implode(' AND ', array_map(static fn (string $column): string => $column . ' <=> ?', $columns)) . ')',
                [...array_values($quotedData), ...array_values($criteria), ...array_values($quotedData)]
            );

            return null;
        }
    }

    /**
     * Whether the connection was opened with CLIENT_FOUND_ROWS, i.e. reports matched instead of
     * changed rows: PDO::MYSQL_ATTR_FOUND_ROWS for pdo_mysql, MYSQLI_CLIENT_FOUND_ROWS in the
     * 'flags' bitmask for mysqli (both live in the doctrine driverOptions).
     */
    private static function hasFoundRowsSemantics(Connection $connection): bool
    {
        $driverOptions = $connection->getParams()['driverOptions'] ?? [];

        if (defined('PDO::MYSQL_ATTR_FOUND_ROWS') && ($driverOptions[PDO::MYSQL_ATTR_FOUND_ROWS] ?? false)) {
            return true;
        }

        return defined('MYSQLI_CLIENT_FOUND_ROWS')
            && (((int) ($driverOptions['flags'] ?? 0)) & MYSQLI_CLIENT_FOUND_ROWS) !== 0;
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
