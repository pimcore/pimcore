<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Dao;

use Pimcore\Cache;
use Pimcore\Db;

abstract class AbstractDao implements DaoInterface
{

    use DaoTrait;

    const CACHEKEY = "system_resource_columns_";

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     */
    public function configure()
    {
        $this->db = Db::get();
    }

    /**
     *
     */
    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    /**
     *
     */
    public function commit()
    {
        $this->db->commit();
    }

    /**
     *
     */
    public function rollBack()
    {
        $this->db->rollBack();
    }

    /**
     * @param string $table
     * @param bool $cache
     * @return array|mixed
     */
    public function getValidTableColumns($table, $cache = true)
    {
        $cacheKey = self::CACHEKEY . $table;

        if (\Zend_Registry::isRegistered($cacheKey)) {
            $columns = \Zend_Registry::get($cacheKey);
        } else {
            $columns = Cache::load($cacheKey);

            if (!$columns || !$cache) {
                $columns = array();
                $data = $this->db->fetchAll("SHOW COLUMNS FROM " . $table);
                foreach ($data as $d) {
                    $columns[] = $d["Field"];
                }
                Cache::save($columns, $cacheKey, array("system", "resource"), null, 997);
            }

            \Zend_Registry::set($cacheKey, $columns);
        }

        return $columns;
    }

    /** Clears the column information for the given table.
     * @param $table
     */
    public function resetValidTableColumnsCache($table)
    {
        $cacheKey = self::CACHEKEY . $table;
        \Zend_Registry::getInstance()->offsetUnset($cacheKey);
        Cache::clearTags(array("system", "resource"));
    }
}
