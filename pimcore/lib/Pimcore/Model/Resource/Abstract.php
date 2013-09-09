<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

abstract class Pimcore_Model_Resource_Abstract implements Pimcore_Model_Resource_Interface {

    const CACHEKEY = "system_resource_columns_";

    /**
     * @var Pimcore_Model_Abstract
     */
    protected $model;

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @param Pimcore_Model_Abstract $model
     * @return void
     */
    public function setModel($model) {
        $this->model = $model;
        return $this;
    }

    /**
     * @param Zend_Db_Adapter_Abstract $conf
     * @return void
     */
    public function configure($conf) {
        $this->db = $conf;
    }

    /**
     *
     */
    public function beginTransaction() {
        $this->db->beginTransaction();
    }

    /**
     *
     */
    public function commit() {
        $this->db->commit();
    }

    /**
     *
     */
    public function rollBack() {
        $this->db->rollBack();
    }

    /**
     * @param array $data
     * @return void
     */
    protected function assignVariablesToModel($data) {
        $this->model->setValues($data);
    }


    /**
     * @param string $table
     * @param bool $cache
     * @return array|mixed
     */
    public  function getValidTableColumns ($table, $cache = true) {
        
        $cacheKey = self::CACHEKEY . $table;
        
        if(Zend_Registry::isRegistered($cacheKey)) {
            $columns = Zend_Registry::get($cacheKey);
        }
        else
        {
            $columns = Pimcore_Model_Cache::load($cacheKey);
            
            if (!$columns || !$cache) {    
                $columns = array();
                $data = $this->db->fetchAll("SHOW COLUMNS FROM " . $table);
                foreach ($data as $d) {
                    $columns[] = $d["Field"];
                }
                Pimcore_Model_Cache::save($columns, $cacheKey, array("system","resource"), null, 997);
            }
            
             Zend_Registry::set($cacheKey, $columns);
        }
        
        return $columns;
    }

    /** Clears the column information for the given table.
     * @param $table
     */
    protected function resetValidTableColumnsCache($table) {
        $cacheKey = self::CACHEKEY . $table;
        Zend_Registry::getInstance()->offsetUnset($cacheKey);
        Pimcore_Model_Cache::clearTags(array("system", "resource"));

    }
}
