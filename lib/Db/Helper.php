<?php

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

class Helper
{
    public static function insertOrUpdate(\Doctrine\DBAL\Connection $connection, $table, array $data)
    {
        // extract and quote col names from the array keys
        $i = 0;
        $bind = [];
        $cols = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $cols[] = $connection->quoteIdentifier($col);
            $bind[':col' . $i] = $val;
            $vals[] = ':col' . $i;
            $i++;
        }

        // build the statement
        $set = [];
        foreach ($cols as $i => $col) {
            $set[] = sprintf('%s = %s', $col, $vals[$i]);
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s;',
            $connection->quoteIdentifier($table),
            implode(', ', $cols),
            implode(', ', $vals),
            implode(', ', $set)
        );

        $bind = array_merge($bind, $bind);

        return $connection->executeStatement($sql, $bind);
    }

    public static function quoteInto(\Doctrine\DBAL\Connection $db, $text, $value, $type = null, $count = null)
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
}
