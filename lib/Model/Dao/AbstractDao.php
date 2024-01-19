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

namespace Pimcore\Model\Dao;

use Doctrine\DBAL\Connection;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;

abstract class AbstractDao implements DaoInterface
{
    use DaoTrait;

    const CACHEKEY = 'system_resource_columns_';

    public Connection $db;

    public function configure(): void
    {
        $this->db = Db::get();
    }

    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function commit(): void
    {
        $this->db->commit();
    }

    public function rollBack(): void
    {
        $this->db->rollBack();
    }

    /**
     * @return string[]
     */
    public function getPrimaryKey(string $table, bool $cache = true): array
    {
        return $this->getValidTableColumns($table, $cache, true);
    }

    /**
     * @return string[]
     */
    public function getValidTableColumns(string $table, bool $cache = true, bool $primaryKeyColumnsOnly = false): array
    {
        $cacheKey = self::CACHEKEY . $table;

        if (RuntimeCache::isRegistered($cacheKey)) {
            $allColumns = RuntimeCache::get($cacheKey);
        } else {
            $allColumns = Cache::load($cacheKey);

            if (!$allColumns || !$cache) {
                $columns = [];
                $primaryKeyColumns = [];
                $data = $this->db->fetchAllAssociative('SHOW COLUMNS FROM ' . $table);
                foreach ($data as $d) {
                    $fieldName = $d['Field'];
                    $columns[] = $fieldName;
                    if ($d['Key'] === 'PRI') {
                        $primaryKeyColumns[] = $fieldName;
                    }
                }
                $allColumns = ['columns' => $columns,  'primaryKeyColumns' => $primaryKeyColumns];
                Cache::save($allColumns, $cacheKey, ['system', 'resource'], null, 997);
            }

            RuntimeCache::set($cacheKey, $allColumns);
        }

        return $primaryKeyColumnsOnly ? $allColumns['primaryKeyColumns'] : $allColumns['columns'];
    }

    /**
     * Clears the column information for the given table.
     *
     */
    public function resetValidTableColumnsCache(string $table): void
    {
        $cacheKey = self::CACHEKEY . $table;
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::getInstance()->offsetUnset($cacheKey);
        }
        Cache::clearTags(['system', 'resource']);
    }

    public static function getForeignKeyName(string $table, string $column): string
    {
        $fkName = 'fk_'.$table.'__'.$column;
        if (strlen($fkName) > 64) {
            $fkName = substr($fkName, 0, 55) . '_' . hash('crc32', $fkName);
        }

        return $fkName;
    }
}
