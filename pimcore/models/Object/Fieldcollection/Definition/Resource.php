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

class Object_Fieldcollection_Definition_Resource extends Pimcore_Model_Resource_Abstract {
    
    public function getTableName (Object_Class $class) {
        return "object_collection_" . $this->model->getKey() . "_" . $class->getId();
    }
    
    public function delete (Object_Class $class) {
        
        $table = $this->getTableName($class);
        $this->dbexec("DROP TABLE IF EXISTS `" . $table . "`");
    }
    
    public function createUpdateTable (Object_Class $class) {
        
        $table = $this->getTableName($class);
        
        $this->dbexec("CREATE TABLE IF NOT EXISTS `" . $table . "` (
		  `o_id` int(11) NOT NULL default '0',
		  `index` int(11) default '0',
          `fieldname` varchar(255) default NULL,
          PRIMARY KEY (`o_id`,`index`,`fieldname`(255)),
          INDEX `o_id` (`o_id`),
          INDEX `index` (`index`),
          INDEX `fieldname` (`fieldname`)
		) DEFAULT CHARSET=utf8;");
        
        $existingColumns = $this->getValidTableColumns($table, false); // no caching of table definition
        $columnsToRemove = $existingColumns;
        $protectedColums = array("o_id", "index","fieldname");
        
        foreach ($this->model->getFieldDefinitions() as $value) {
            
            $key = $value->getName();
            
            // nullable & default value
            list($defaultvalue, $nullable) = $this->getDefaultValueAndNullableForField($value);
    
            if (is_array($value->getColumnType())) {
                // if a datafield requires more than one field
                foreach ($value->getColumnType() as $fkey => $fvalue) {
                    $this->addModifyColumn($table, $key . "__" . $fkey, $fvalue, $defaultvalue, $nullable);
                    $protectedColums[] = $key . "__" . $fkey;
                }
            }
            else {    
                if ($value->getColumnType()) {
                    $this->addModifyColumn($table, $key, $value->getColumnType(), $defaultvalue, $nullable);
                    $protectedColums[] = $key;
                }
            }
            $this->addIndexToField($value,$table);
        }
        
        $this->removeUnusedColumns($table, $columnsToRemove, $protectedColums);
    }
    
    // @TODO the following methods dublicates Object_Class_Resource
    protected function getDefaultValueAndNullableForField ($field) {
        
        $nullable = "NULL";

        /*if ($field->getMandatory()) {
            $nullable = "NOT NULL";
        }*/
        
        $defaultvalue = "";
        if (method_exists($field, 'getDefaultValue') && $field->getDefaultValue() !== null) {
            $defaultvalue = " DEFAULT '" . $field->getDefaultValue() . "'";
        } else if (method_exists($field, 'getDefaultValue') && $field->getDefaultValue() === null and $nullable == "NULL"){
            $defaultvalue = " DEFAULT NULL";
        }
        
        return array($defaultvalue, $nullable);
    }
    
    protected function addIndexToField ($field, $table) {
        
        if ($field->getIndex()) {
            if (is_array($field->getColumnType())) {
                // multicolumn field
                foreach ($field->getColumnType() as $fkey => $fvalue) {
                    $columnName = $field->getName() . "__" . $fkey;
                    try {
                        $this->dbexec("ALTER TABLE `" . $table . "` ADD INDEX `p_index_" . $columnName . "` (`" . $columnName . "`);");
                    } catch (Exception $e) {}
                }            
            } else {
                // single -column field
                $columnName = $field->getName();
                try {
                    $this->dbexec("ALTER TABLE `" . $table . "` ADD INDEX `p_index_" . $columnName . "` (`" . $columnName . "`);");
                } catch (Exception $e) {}
            }
        } else {
            if (is_array($field->getColumnType())) {
                // multicolumn field
                foreach ($field->getColumnType() as $fkey => $fvalue) {
                    $columnName = $field->getName() . "__" . $fkey;
                    try {
                        $this->dbexec("ALTER TABLE `" . $table . "` DROP INDEX `p_index_" . $columnName . "`;");
                    } catch (Exception $e) {}
                }            
            } else {
                // single -column field
                $columnName = $field->getName();
                try {
                    $this->dbexec("ALTER TABLE `" . $table . "` DROP INDEX `p_index_" . $columnName . "`;");
                } catch (Exception $e) {}
            }
        }
    }
    
    protected function addModifyColumn ($table, $colName, $type, $default, $null) {
        
        $existingColumns = $this->getValidTableColumns($table, false);

        $existingColName = null;

        // check for existing column case insensitive eg a rename from myInput to myinput
        $matchingExisting = preg_grep('/^' . preg_quote($colName, '/') . '$/i', $existingColumns);
        if(is_array($matchingExisting) && !empty($matchingExisting)) {
            $existingColName = current($matchingExisting);
        }
        if ($existingColName === null) {
            $this->dbexec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $colName . '` ' . $type . $default . ' ' . $null . ';');
        } else {
            $this->dbexec('ALTER TABLE `' . $table . '` CHANGE COLUMN `' . $existingColName . '` `' . $colName . '` ' . $type . $default . ' ' . $null . ';');
        }

    }
    
    protected function removeUnusedColumns ($table, $columnsToRemove, $protectedColumns) {
        if (is_array($columnsToRemove) && count($columnsToRemove) > 0) {
            foreach ($columnsToRemove as $value) {
                //if (!in_array($value, $protectedColumns)) {
                if (!in_array(strtolower($value), array_map('strtolower', $protectedColumns))) {
                    $this->dbexec('ALTER TABLE `' . $table . '` DROP COLUMN `' . $value . '`;');
                }
            }
        }
    }
    
    protected function dbexec($sql) {
        $this->db->query($sql);
        $this->logSql($sql);
    }
    
    protected function logSql ($sql) {
        $this->_sqlChangeLog[] = $sql;
    }
    
    public function __destruct () {
        
        // write sql change log for deploying to production system
        if(!empty($this->_sqlChangeLog)) {
            $log = implode("\n\n\n", $this->_sqlChangeLog);
            
            $filename = "db-change-log_".time()."_class-".$this->model->getKey().".sql";
            $file = PIMCORE_SYSTEM_TEMP_DIRECTORY."/".$filename;
            if(defined("PIMCORE_DB_CHANGELOG_DIRECTORY")) {
                $file = PIMCORE_DB_CHANGELOG_DIRECTORY."/".$filename;
            }
            
            file_put_contents($file, $log);
            chmod($file, 0766);
        }
    }
}
