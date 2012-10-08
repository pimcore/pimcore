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
        $this->db->query("DROP TABLE IF EXISTS `" . $table . "`");
    }
    
    public function createUpdateTable (Object_Class $class) {
        
        $table = $this->getTableName($class);
        
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . $table . "` (
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
            

    
            if (is_array($value->getColumnType())) {
                // if a datafield requires more than one field
                foreach ($value->getColumnType() as $fkey => $fvalue) {
                    $this->addModifyColumn($table, $key . "__" . $fkey, $fvalue, "", "NULL");
                    $protectedColums[] = $key . "__" . $fkey;
                }
            }
            else {    
                if ($value->getColumnType()) {
                    $this->addModifyColumn($table, $key, $value->getColumnType(), "", "NULL");
                    $protectedColums[] = $key;
                }
            }
            $this->addIndexToField($value,$table);
        }
        
        $this->removeUnusedColumns($table, $columnsToRemove, $protectedColums);
    }
    

    
    protected function addIndexToField ($field, $table) {
        
        if ($field->getIndex()) {
            if (is_array($field->getColumnType())) {
                // multicolumn field
                foreach ($field->getColumnType() as $fkey => $fvalue) {
                    $columnName = $field->getName() . "__" . $fkey;
                    try {
                        $this->db->query("ALTER TABLE `" . $table . "` ADD INDEX `p_index_" . $columnName . "` (`" . $columnName . "`);");
                    } catch (Exception $e) {}
                }            
            } else {
                // single -column field
                $columnName = $field->getName();
                try {
                    $this->db->query("ALTER TABLE `" . $table . "` ADD INDEX `p_index_" . $columnName . "` (`" . $columnName . "`);");
                } catch (Exception $e) {}
            }
        } else {
            if (is_array($field->getColumnType())) {
                // multicolumn field
                foreach ($field->getColumnType() as $fkey => $fvalue) {
                    $columnName = $field->getName() . "__" . $fkey;
                    try {
                        $this->db->query("ALTER TABLE `" . $table . "` DROP INDEX `p_index_" . $columnName . "`;");
                    } catch (Exception $e) {}
                }            
            } else {
                // single -column field
                $columnName = $field->getName();
                try {
                    $this->db->query("ALTER TABLE `" . $table . "` DROP INDEX `p_index_" . $columnName . "`;");
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
            $this->db->query('ALTER TABLE `' . $table . '` ADD COLUMN `' . $colName . '` ' . $type . $default . ' ' . $null . ';');
        } else {
            $this->db->query('ALTER TABLE `' . $table . '` CHANGE COLUMN `' . $existingColName . '` `' . $colName . '` ' . $type . $default . ' ' . $null . ';');
        }

    }
    
    protected function removeUnusedColumns ($table, $columnsToRemove, $protectedColumns) {
        if (is_array($columnsToRemove) && count($columnsToRemove) > 0) {
            foreach ($columnsToRemove as $value) {
                //if (!in_array($value, $protectedColumns)) {
                if (!in_array(strtolower($value), array_map('strtolower', $protectedColumns))) {
                    $this->db->query('ALTER TABLE `' . $table . '` DROP COLUMN `' . $value . '`;');
                }
            }
        }
    }
}
