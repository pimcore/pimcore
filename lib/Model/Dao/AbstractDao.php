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

    const CACHE_KEY_PRIMARY_KEY = 'system_resource_primary_key_columns_';

    /**
     * @var Connection
     */
    public $db;

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
        $cacheKeyPrimaryKey = self::CACHE_KEY_PRIMARY_KEY . $table;

        if (RuntimeCache::isRegistered($cacheKeyPrimaryKey)) {
            $primaryKeyColumns = RuntimeCache::get($cacheKeyPrimaryKey);
        } else {
            $primaryKeyColumns = Cache::load($cacheKeyPrimaryKey);

            if (!$primaryKeyColumns || !$cache) {
                $this->getValidTableColumns($table, $cache);
                $primaryKeyColumns = RuntimeCache::get($cacheKeyPrimaryKey);
            }
        }

        return $primaryKeyColumns;
    }

    /**
     * @return string[]
     */
    public function getValidTableColumns(string $table, bool $cache = true): array
    {
        $cacheKey = self::CACHEKEY . $table;
        $cacheKeyPrimaryKey = self::CACHE_KEY_PRIMARY_KEY . $table;

        if (RuntimeCache::isRegistered($cacheKey)) {
            $columns = RuntimeCache::get($cacheKey);
        } else {
            $columns = Cache::load($cacheKey);
            $primaryKeyColumns = Cache::load($cacheKeyPrimaryKey);

            if (!$columns || !$cache || !$primaryKeyColumns) {
                $columns = [];
                $primaryKeyColumns = [];
                $data = $this->db->fetchAllAssociative('SHOW COLUMNS FROM ' . $table);
                foreach ($data as $d) {
                    $fieldName = $d['Field'];
                    $columns[] = $fieldName;
                    if($d['Key'] === 'PRI') {
                        $primaryKeyColumns[] = $fieldName;
                    }
                }
                Cache::save($columns, $cacheKey, ['system', 'resource'], null, 997);
                Cache::save($primaryKeyColumns, $cacheKeyPrimaryKey, ['system', 'resource'], null, 997);
            }

            RuntimeCache::set($cacheKey, $columns);
            RuntimeCache::set($cacheKeyPrimaryKey, $primaryKeyColumns);
        }

        return $columns;
    }

    /**
     * Clears the column information for the given table.
     *
     * @param string $table
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
