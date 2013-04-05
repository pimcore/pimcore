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
 * @category   Pimcore
 * @package    Object_Objectbrick
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Objectbrick_Definition_Resource extends Object_Fieldcollection_Definition_Resource {
    
    public function getTableName (Object_Class $class, $query = false) {
        if($query) {
             return "object_brick_query_" . $this->model->getKey() . "_" . $class->getId();
        } else {
            return "object_brick_store_" . $this->model->getKey() . "_" . $class->getId();
        }
    }
    
    public function delete (Object_Class $class) {
        $table = $this->getTableName($class, false);
        $this->db->query("DROP TABLE IF EXISTS `" . $table . "`");

        $table = $this->getTableName($class, true);
        $this->db->query("DROP TABLE IF EXISTS `" . $table . "`");
    }
    
    public function createUpdateTable (Object_Class $class) {

        $tableStore = $this->getTableName($class, false);
        $tableQuery = $this->getTableName($class, true);
        
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . $tableStore . "` (
		  `o_id` int(11) NOT NULL default '0',
          `fieldname` varchar(255) default NULL,
          PRIMARY KEY (`o_id`,`fieldname`),
          INDEX `o_id` (`o_id`),
          INDEX `fieldname` (`fieldname`)
		) DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . $tableQuery . "` (
		  `o_id` int(11) NOT NULL default '0',
          `fieldname` varchar(255) default NULL,
          PRIMARY KEY (`o_id`,`fieldname`),
          INDEX `o_id` (`o_id`),
          INDEX `fieldname` (`fieldname`)
		) DEFAULT CHARSET=utf8;");

        $existingColumnsStore = $this->getValidTableColumns($tableStore, false); // no caching of table definition
        $columnsToRemoveStore = $existingColumnsStore;
        $existingColumnsQuery = $this->getValidTableColumns($tableQuery, false); // no caching of table definition
        $columnsToRemoveQuery = $existingColumnsQuery;

        $protectedColumnsStore = array("o_id", "fieldname");
        $protectedColumnsQuery = array("o_id", "fieldname");
        
        foreach ($this->model->getFieldDefinitions() as $value) {
            
            $key = $value->getName();



            // if a datafield requires more than one column in the query table
            if (is_array($value->getQueryColumnType())) {
                foreach ($value->getQueryColumnType() as $fkey => $fvalue) {
                    $this->addModifyColumn($tableQuery, $key . "__" . $fkey, $fvalue, "", "NULL");
                    $protectedColumnsQuery[] = $key . "__" . $fkey;
                }
            }

            // if a datafield requires more than one column in the datastore table => only for non-relation types
            if(!$value->isRelationType() && is_array($value->getColumnType())) {
                foreach ($value->getColumnType() as $fkey => $fvalue) {
                    $this->addModifyColumn($tableStore, $key . "__" . $fkey, $fvalue, "", "NULL");
                    $protectedColumnsStore[] = $key . "__" . $fkey;
                }
            }

            // everything else
            if (!is_array($value->getQueryColumnType()) && !is_array($value->getColumnType())) {
                if ($value->getQueryColumnType()) {
                    $this->addModifyColumn($tableQuery, $key, $value->getQueryColumnType(), "", "NULL");
                    $protectedColumnsQuery[] = $key;
                }
                if ($value->getColumnType() && !$value->isRelationType()) {
                    $this->addModifyColumn($tableStore, $key, $value->getColumnType(), "", "NULL");
                    $protectedColumnsStore[] = $key;
                }
            }

            // add indices
            $this->addIndexToField($value, $tableStore);
            $this->addIndexToField($value, $tableQuery);

        }
        
        $this->removeUnusedColumns($tableStore, $columnsToRemoveStore, $protectedColumnsStore);
        $this->removeUnusedColumns($tableQuery, $columnsToRemoveQuery, $protectedColumnsQuery);
    }
}
