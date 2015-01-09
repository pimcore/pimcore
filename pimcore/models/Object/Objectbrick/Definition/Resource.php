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
 * @package    Object\Objectbrick
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Objectbrick\Definition;

use Pimcore\Model;
use Pimcore\Model\Object;

class Resource extends Model\Object\Fieldcollection\Definition\Resource {

    /**
     * @param Object\ClassDefinition $class
     * @param bool $query
     * @return string
     */
    public function getTableName (Object\ClassDefinition $class, $query = false) {
        if($query) {
            return "object_brick_query_" . $this->model->getKey() . "_" . $class->getId();
        } else {
            return "object_brick_store_" . $this->model->getKey() . "_" . $class->getId();
        }
    }

    /**
     * @param Object\ClassDefinition $class
     */
    public function delete (Object\ClassDefinition $class) {
        $table = $this->getTableName($class, false);
        $this->db->query("DROP TABLE IF EXISTS `" . $table . "`");

        $table = $this->getTableName($class, true);
        $this->db->query("DROP TABLE IF EXISTS `" . $table . "`");
    }

    /**
     * @param Object\ClassDefinition $class
     */
    public function createUpdateTable (Object\ClassDefinition $class) {

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

        Object\ClassDefinition\Service::updateTableDefinitions($this->tableDefinitions, (array($tableStore, $tableQuery)));

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
