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
 * @package    Object_Fieldcollection
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
        $this->dbexec("DROP TABLE IF EXISTS `" . $table . "`");

        $table = $this->getTableName($class, true);
        $this->dbexec("DROP TABLE IF EXISTS `" . $table . "`");
    }
    
    public function createUpdateTable (Object_Class $class) {

        $tableStore = $this->getTableName($class, false);
        $tableQuery = $this->getTableName($class, true);
        
        $this->dbexec("CREATE TABLE IF NOT EXISTS `" . $tableStore . "` (
		  `o_id` int(11) NOT NULL default '0',
          `fieldname` varchar(255) default NULL,
          PRIMARY KEY (`o_id`,`fieldname`),
          INDEX `o_id` (`o_id`),
          INDEX `fieldname` (`fieldname`)
		) DEFAULT CHARSET=utf8;");

        $this->dbexec("CREATE TABLE IF NOT EXISTS `" . $tableQuery . "` (
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

            // nullable & default value
            list($defaultvalue, $nullable) = $this->getDefaultValueAndNullableForField($value);

            // if a datafield requires more than one column in the query table
            if (is_array($value->getQueryColumnType())) {
                foreach ($value->getQueryColumnType() as $fkey => $fvalue) {
                    $this->addModifyColumn($tableQuery, $key . "__" . $fkey, $fvalue, $defaultvalue, $nullable);
                    $protectedColumnsQuery[] = $key . "__" . $fkey;
                }
            }

            // if a datafield requires more than one column in the datastore table => only for non-relation types
            if(!$value->isRelationType() && is_array($value->getColumnType())) {
                foreach ($value->getColumnType() as $fkey => $fvalue) {
                    $this->addModifyColumn($tableStore, $key . "__" . $fkey, $fvalue, $defaultvalue, $nullable);
                    $protectedColumnsStore[] = $key . "__" . $fkey;
                }
            }

            // everything else
            if (!is_array($value->getQueryColumnType()) && !is_array($value->getColumnType())) {
                if ($value->getQueryColumnType()) {
                    $this->addModifyColumn($tableQuery, $key, $value->getQueryColumnType(), $defaultvalue, $nullable);
                    $protectedColumnsQuery[] = $key;
                }
                if ($value->getColumnType() && !$value->isRelationType()) {
                    $this->addModifyColumn($tableStore, $key, $value->getColumnType(), $defaultvalue, $nullable);
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
    
//    // @TODO the following methods dublicates Object_Class_Resource
//    private function getDefaultValueAndNullableForField ($field) {
//
//        $nullable = "NULL";
//        if ($field->getMandatory()) {
//            $nullable = "NOT NULL";
//        }
//
//        $defaultvalue = "";
//        if (method_exists($field, 'getDefaultValue') && $field->getDefaultValue() !== null) {
//            $defaultvalue = " DEFAULT '" . $field->getDefaultValue() . "'";
//        } else if (method_exists($field, 'getDefaultValue') && $field->getDefaultValue() === null and $nullable == "NULL"){
//            $defaultvalue = " DEFAULT NULL";
//        }
//
//        return array($defaultvalue, $nullable);
//    }
    
//    private function addIndexToField ($field, $table) {
//
//        if ($field->getIndex()) {
//            if (is_array($field->getColumnType())) {
//                // multicolumn field
//                foreach ($field->getColumnType() as $fkey => $fvalue) {
//                    $columnName = $field->getName() . "__" . $fkey;
//                    try {
//                        $this->dbexec("ALTER TABLE `" . $table . "` ADD INDEX `p_index_" . $columnName . "` (`" . $columnName . "`);");
//                    } catch (Exception $e) {}
//                }
//            } else {
//                // single -column field
//                $columnName = $field->getName();
//                try {
//                    $this->dbexec("ALTER TABLE `" . $table . "` ADD INDEX `p_index_" . $columnName . "` (`" . $columnName . "`);");
//                } catch (Exception $e) {}
//            }
//        } else {
//            if (is_array($field->getColumnType())) {
//                // multicolumn field
//                foreach ($field->getColumnType() as $fkey => $fvalue) {
//                    $columnName = $field->getName() . "__" . $fkey;
//                    try {
//                        $this->dbexec("ALTER TABLE `" . $table . "` DROP INDEX `p_index_" . $columnName . "`;");
//                    } catch (Exception $e) {}
//                }
//            } else {
//                // single -column field
//                $columnName = $field->getName();
//                try {
//                    $this->dbexec("ALTER TABLE `" . $table . "` DROP INDEX `p_index_" . $columnName . "`;");
//                } catch (Exception $e) {}
//            }
//        }
//    }
    
//    private function addModifyColumn ($table, $colName, $type, $default, $null) {
//
//        $existingColumns = $this->getValidTableColumns($table, false);
//        $existingColName = null;
//
//        // check for existing column case insensitive eg a rename from myInput to myinput
//        $matchingExisting = preg_grep('/^' . preg_quote($colName, '/') . '$/i', $existingColumns);
//        if(is_array($matchingExisting) && !empty($matchingExisting)) {
//            $existingColName = current($matchingExisting);
//        }
//
//        if ($existingColName === null) {
//            $this->dbexec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $colName . '` ' . $type . $default . ' ' . $null . ';');
//        } else {
//            $this->dbexec('ALTER TABLE `' . $table . '` CHANGE COLUMN `' . $existingColName . '` `' . $colName . '` ' . $type . $default . ' ' . $null . ';');
//        }
//    }
    
//    private function removeUnusedColumns ($table, $columnsToRemove, $protectedColumns) {
//        if (is_array($columnsToRemove) && count($columnsToRemove) > 0) {
//            foreach ($columnsToRemove as $value) {
//                //if (!in_array($value, $protectedColumns)) {
//                if (!in_array(strtolower($value), array_map('strtolower', $protectedColumns))) {
//                    $this->dbexec('ALTER TABLE `' . $table . '` DROP COLUMN `' . $value . '`;');
//                }
//            }
//        }
//    }
    
//    private function dbexec($sql) {
//        $this->db->exec($sql);
//        $this->logSql($sql);
//    }
//
//    private function logSql ($sql) {
//        $this->_sqlChangeLog[] = $sql;
//    }
//
//    public function __destruct () {
//
//        // write sql change log for deploying to production system
//        if(!empty($this->_sqlChangeLog)) {
//            $log = implode("\n\n\n", $this->_sqlChangeLog);
//
//            $filename = "db-change-log_".time()."_class-".$this->model->getKey().".sql";
//            $file = PIMCORE_SYSTEM_TEMP_DIRECTORY."/".$filename;
//            if(defined("PIMCORE_DB_CHANGELOG_DIRECTORY")) {
//                $file = PIMCORE_DB_CHANGELOG_DIRECTORY."/".$filename;
//            }
//
//            file_put_contents($file, $log);
//        }
//    }
}
