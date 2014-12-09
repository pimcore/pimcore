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
 * @package    Object\Fieldcollection
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Fieldcollection\Definition;

use Pimcore\Model;
use Pimcore\Model\Object;

class Resource extends Model\Resource\AbstractResource {

    /**
     * @var null
     */
    protected $tableDefinitions = null;

    /**
     * @param Object\ClassDefinition $class
     * @return string
     */
    public function getTableName (Object\ClassDefinition $class) {
        return "object_collection_" . $this->model->getKey() . "_" . $class->getId();
    }

    /**
     * @param Object\ClassDefinition $class
     */
    public function delete (Object\ClassDefinition $class) {

        $table = $this->getTableName($class);
        $this->db->query("DROP TABLE IF EXISTS `" . $table . "`");
    }

    /**
     * @param Object\ClassDefinition $class
     */
    public function createUpdateTable (Object\ClassDefinition $class) {

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

        Object\ClassDefinition\Service::updateTableDefinitions($this->tableDefinitions, (array($table)));

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
        $this->tableDefinitions = null;

    }

    /**
     * @param $field
     * @param $table
     */
    protected function addIndexToField ($field, $table) {

        if ($field->getIndex()) {
            if (is_array($field->getColumnType())) {
                // multicolumn field
                foreach ($field->getColumnType() as $fkey => $fvalue) {
                    $columnName = $field->getName() . "__" . $fkey;
                    try {
                        $this->db->query("ALTER TABLE `" . $table . "` ADD INDEX `p_index_" . $columnName . "` (`" . $columnName . "`);");
                    } catch (\Exception $e) {}
                }
            } else {
                // single -column field
                $columnName = $field->getName();
                try {
                    $this->db->query("ALTER TABLE `" . $table . "` ADD INDEX `p_index_" . $columnName . "` (`" . $columnName . "`);");
                } catch (\Exception $e) {}
            }
        } else {
            if (is_array($field->getColumnType())) {
                // multicolumn field
                foreach ($field->getColumnType() as $fkey => $fvalue) {
                    $columnName = $field->getName() . "__" . $fkey;
                    try {
                        $this->db->query("ALTER TABLE `" . $table . "` DROP INDEX `p_index_" . $columnName . "`;");
                    } catch (\Exception $e) {}
                }
            } else {
                // single -column field
                $columnName = $field->getName();
                try {
                    $this->db->query("ALTER TABLE `" . $table . "` DROP INDEX `p_index_" . $columnName . "`;");
                } catch (\Exception $e) {}
            }
        }
    }

    /**
     * @param $table
     * @param $colName
     * @param $type
     * @param $default
     * @param $null
     */
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
            $this->resetValidTableColumnsCache($table);
        } else {

            if (!Object\ClassDefinition\Service::skipColumn($this->tableDefinitions, $table, $colName, $type, $default, $null)) {
                $this->db->query('ALTER TABLE `' . $table . '` CHANGE COLUMN `' . $existingColName . '` `' . $colName . '` ' . $type . $default . ' ' . $null . ';');
            }
        }
    }

    /**
     * @param $table
     * @param $columnsToRemove
     * @param $protectedColumns
     */
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
