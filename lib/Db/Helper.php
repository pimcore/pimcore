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
     * Inserts a row, or updates the rows matching $keys if the insert hits a unique constraint.
     *
     * This runs an INSERT and, on a duplicate, an UPDATE ... WHERE $keys - two statements and an
     * exception on every update of an existing row. Where the row usually exists,
     * {@see self::updateOrInsert()} addresses the same rows with a single UPDATE.
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
     * Updates the rows matching $keys, or inserts the row if none matched.
     *
     * The rows addressed are those of {@see self::upsert()} - $keys are the criteria of an
     * UPDATE ... WHERE, no other row is ever touched, no trigger runs on a row the criteria do
     * not match - but the UPDATE runs first. Where the row usually exists and changes, as for
     * the main element tables whose DAOs insert the row in create() before every update(), or
     * the class store tables on an update, this is a single statement without the duplicate
     * key exception, on any connection: with CLIENT_FOUND_ROWS the UPDATE of an unchanged row
     * reports 1, which is equally correct here.
     *
     * If the UPDATE changes no row, the row is missing or it matched but nothing changed (it
     * already holds these values, or a BEFORE UPDATE trigger reset them), and the affected-rows
     * value cannot tell the two apart. The UPDATE therefore records that it matched a row: one
     * of its assignments evaluates LAST_INSERT_ID(<token>) - a random token, in an expression
     * that depends on the row and whose result is compared rather than null-tested, so it is
     * evaluated per matched row on MariaDB and MySQL alike and never optimized away - and a
     * matched row leaves the token in the connection's LAST_INSERT_ID(), read back with
     * one cheap SELECT only on this path. Matched means done, without touching the row (or its
     * triggers) a second time; not matched means missing, and the insert goes through upsert(),
     * whose duplicate handling also covers a row inserted concurrently since the UPDATE. Note
     * that this leaves the token as the connection's last insert id, which nothing reads after
     * an UPDATE anyway.
     *
     * Where the row usually does not exist, upsert() is the better choice - a plain INSERT -
     * as the UPDATE would be a wasted round trip. A null or missing key value skips the UPDATE
     * and goes to upsert().
     *
     * The one observable difference to upsert(): on the update path of a row whose values
     * change, upsert()'s INSERT failed on the duplicate and ran the table's BEFORE INSERT
     * triggers first (their effects rolled back with the failed statement); this method runs
     * them only when it actually tries to insert.
     *
     * @param string $table Used as given, exactly as upsert() and DBAL's insert()/update() use it:
     * $quoteIdentifiers applies to the column names in $data and $keys only. A table name that
     * needs quoting, or a schema-qualified one, is passed already quoted.
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

        // the first key column's assignment also records the match: LAST_INSERT_ID(<token>) is
        // evaluated for every matched row and, as the token is never 0, the column is assigned
        // its value as usual. The LENGTH() of the column keeps the argument from being folded
        // into a constant, and the comparison with 0 (rather than IS NULL, which MySQL folds to
        // false for a function that cannot return NULL) keeps the call from being optimized away
        $token = random_int(1, PHP_INT_MAX);
        $matchKey = array_key_first($criteria);
        $assignments = [];
        foreach (array_keys($quotedData) as $column) {
            $assignments[] = $column === $matchKey
                ? $column . ' = IF(LAST_INSERT_ID(' . $token . ' + 0 * LENGTH(' . $column . ')) = 0, ' . $column . ', ?)'
                : $column . ' = ?';
        }
        $affectedRows = (int) $connection->executeStatement(
            'UPDATE ' . $table
            . ' SET ' . implode(', ', $assignments)
            . ' WHERE ' . implode(' AND ', array_map(static fn (string $key): string => $key . ' = ?', array_keys($criteria))),
            [...array_values($quotedData), ...array_values($criteria)]
        );
        if ($affectedRows > 0) {
            return null;
        }

        // 0 changed rows: matched but unchanged (done), or missing - the token tells
        if ((string) $connection->fetchOne('SELECT LAST_INSERT_ID()') === (string) $token) {
            return null;
        }

        return self::upsert($connection, $table, $data, $keys, $quoteIdentifiers);
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
