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
     * Inserts a row, or updates it if a row with the same unique/primary key already exists.
     *
     * This is a single INSERT ... ON DUPLICATE KEY UPDATE statement, so the row is sent to the
     * database only once, no matter which of the two paths it takes.
     *
     * @param array<string, mixed> $data The data to be inserted or updated into the database table.
     * Array key corresponds to the database column, array value to the actual value.
     * @param string[] $keys The columns identifying the row - typically the primary key columns.
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
        $data = $quoteIdentifiers ? self::quoteDataIdentifiers($connection, $data) : $data;

        if ($data === []) {
            $connection->insert($table, $data);

            return self::lastInsertId($connection);
        }

        $columns = [];
        $placeholders = [];
        $assignments = [];

        foreach (array_keys($data) as $column) {
            $columns[] = $column;
            $placeholders[] = '?';
            // VALUES() and not the row alias introduced with MySQL 8.0.20, which MariaDB does not know
            $assignments[] = $column . ' = VALUES(' . $column . ')';
        }

        $sql = 'INSERT INTO ' . $table
            . ' (' . implode(', ', $columns) . ')'
            . ' VALUES (' . implode(', ', $placeholders) . ')'
            . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $assignments);

        // MySQL/MariaDB report the affected rows of INSERT ... ON DUPLICATE KEY UPDATE as 1 for an
        // inserted row, and as 2 (or 0, if the stored values already matched) for an updated one.
        $affectedRows = (int) $connection->executeStatement($sql, array_values($data));

        if ($affectedRows === 1) {
            return self::lastInsertId($connection);
        }

        // The row already existed, so this is the update path, which never returned an id. $keys is
        // not needed to address the row anymore, but it is still validated exactly where the
        // previous implementation built its WHERE clause from it, so that the same misuse is
        // reported under the same condition.
        foreach ($keys as $key) {
            $key = $quoteIdentifiers ? $connection->quoteIdentifier($key) : $key;
            if (($data[$key] ?? null) === null) {
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
