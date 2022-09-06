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

namespace Pimcore\Model\Dao;

use Doctrine\DBAL\Connection;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;

abstract class AbstractDao implements DaoInterface
{
    use DaoTrait;

    const CACHEKEY = 'system_resource_columns_';

    /**
     * @var Connection
     */
    public $db;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->db = Db::get();
    }

    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    public function commit()
    {
        $this->db->commit();
    }

    public function rollBack()
    {
        $this->db->rollBack();
    }

    /**
     * @param string $table
     * @param bool $cache
     *
     * @return array|mixed
     */
    public function getValidTableColumns($table, $cache = true)
    {
        $cacheKey = self::CACHEKEY . $table;

        if (RuntimeCache::isRegistered($cacheKey)) {
            $columns = RuntimeCache::get($cacheKey);
        } else {
            $columns = Cache::load($cacheKey);

            if (!$columns || !$cache) {
                $columns = [];
                $data = $this->db->fetchAllAssociative('SHOW COLUMNS FROM ' . $table);
                foreach ($data as $d) {
                    $columns[] = $d['Field'];
                }
                Cache::save($columns, $cacheKey, ['system', 'resource'], null, 997);
            }

            RuntimeCache::set($cacheKey, $columns);
        }

        return $columns;
    }

    /**
     * Clears the column information for the given table.
     *
     * @param string $table
     */
    public function resetValidTableColumnsCache($table)
    {
        $cacheKey = self::CACHEKEY . $table;
        if (RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::getInstance()->offsetUnset($cacheKey);
        }
        Cache::clearTags(['system', 'resource']);
    }

    /**
     * @param string $table
     * @param string $column
     *
     * @return string
     */
    public static function getForeignKeyName($table, $column)
    {
        $fkName = 'fk_'.$table.'__'.$column;
        if (strlen($fkName) > 64) {
            $fkName = substr($fkName, 0, 55) . '_' . hash('crc32', $fkName);
        }

        return $fkName;
    }
}
