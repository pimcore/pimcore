<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Types\Type;
use Exception;
use LogicException;
use Pimcore\Model\Element\ValidationException;

class Helper
{
    /**
     *
     * @param array<string, mixed> $data The data to be inserted or updated into the database table.
     * Array key corresponds to the database column, array value to the actual value.
     * @param string[] $keys If the table needs to be updated, the columns listed in this parameter will be used as criteria/condition for the where clause.
     * Typically, these are the primary key columns.
     * The values for the specified keys are read from the $data parameter.
     */
    public static function upsert(Connection $connection, string $table, array $data, array $keys, bool $quoteIdentifiers = true): int|string
    {
        try {
            $data = $quoteIdentifiers ? self::quoteDataIdentifiers($connection, $data) : $data;

            return $connection->insert($table, $data);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $exception) {
            $critera = [];
            foreach ($keys as $key) {
                $key = $quoteIdentifiers ? $connection->quoteIdentifier($key) : $key;
                $critera[$key] = $data[$key] ?? throw new LogicException(sprintf('Key "%s" passed for upsert not found in data', $key));
            }

            return $connection->update($table, $data, $critera);
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

    public static function quoteInto(Connection $db, string $text, mixed $value, int|string|Type|null $type = null, ?int $count = null): array|string
    {
        if ($count === null) {
            return str_replace('?', $db->quote($value, $type), $text);
        }

        return implode($db->quote($value, $type), explode('?', $text, $count + 1));
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
