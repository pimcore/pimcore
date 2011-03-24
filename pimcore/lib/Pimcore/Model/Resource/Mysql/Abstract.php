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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

abstract class Pimcore_Model_Resource_Mysql_Abstract implements Pimcore_Model_Resource_Interface {

    protected $model;
    protected $db;

    public function setModel($model) {
        $this->model = $model;
    }

    public function configure($conf) {
        $this->db = $conf;
    }

    protected function assignVariablesToModel($data) {
        $this->model->setValues($data);
    }
    
    protected function getValidTableColumns ($table, $cache = true) {
        
        $cacheKey = "system_mysql_columns_" . $table;
        
        try {
            $columns = Zend_Registry::get($cacheKey);
        } catch (Exception $e) {
            $columns = Pimcore_Model_Cache::load($cacheKey);
            
            if (!$columns || !$cache) {    
                $columns = array();
                $data = $this->db->fetchAll("SHOW COLUMNS FROM " . $table);
                foreach ($data as $d) {
                    $columns[] = $d["Field"];
                }
                Pimcore_Model_Cache::save($columns, $cacheKey, array("system","resource"));
            }
            
            Zend_Registry::set($cacheKey, $columns);
        }
        
        return $columns;
    }
}
