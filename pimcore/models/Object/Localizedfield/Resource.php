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
 * @package    Object
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Localizedfield;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Tool;

class Resource extends Model\Resource\AbstractResource {

    /**
     * @var null
     */
    protected $tableDefinitions = null;

    /**
     * @return string
     */
    public function getTableName () {
        return "object_localized_data_" . $this->model->getClass()->getId();
    }

    /**
     * @return string
     */
    public function getQueryTableName () {
        return "object_localized_query_" . $this->model->getClass()->getId();
    }

    /**
     *
     */
    public function save () {
        $this->delete(false);

        $object = $this->model->getObject();
        $validLanguages = Tool::getValidLanguages();
        $fieldDefinitions = $this->model->getClass()->getFielddefinition("localizedfields")->getFielddefinitions();

        foreach ($validLanguages as $language) {
            $inheritedValues = Object\AbstractObject::doGetInheritedValues();
            Object\AbstractObject::setGetInheritedValues(false);

            $insertData = array(
                "ooo_id" => $this->model->getObject()->getId(),
                "language" => $language
            );

            foreach ($fieldDefinitions as $fd) {
                if (method_exists($fd, "save")) {
                    // for fieldtypes which have their own save algorithm eg. objects, multihref, ...
                    $fd->save($this->model, array("language" => $language));

                } else {
                    if (is_array($fd->getColumnType())) {
                        $insertDataArray = $fd->getDataForResource($this->model->getLocalizedValue($fd->getName(), $language, true), $object);
                        $insertData = array_merge($insertData, $insertDataArray);
                    } else {
                        $insertData[$fd->getName()] = $fd->getDataForResource($this->model->getLocalizedValue($fd->getName(), $language, true), $object);
                    }
                }
            }

            $storeTable = $this->getTableName();
            $queryTable = $this->getQueryTableName() . "_" . $language;

            $this->db->insertOrUpdate($this->getTableName(), $insertData);

            Object\AbstractObject::setGetInheritedValues(true);

            $data = array();
            $data["ooo_id"] = $this->model->getObject()->getId();
            $data["language"] = $language;

            $this->inheritanceHelper = new Object\Concrete\Resource\InheritanceHelper($object->getClassId(), "ooo_id", $storeTable, $queryTable);
            $this->inheritanceHelper->resetFieldsToCheck();
            $sql = "SELECT * FROM " . $queryTable . " WHERE ooo_id = " . $object->getId() . " AND language = '" . $language . "'";

            try {
                $oldData = $this->db->fetchRow($sql);
            } catch (\Exception $e) {
                // if the table doesn't exist -> create it!
                if(strpos($e->getMessage(), "exist")) {
                    $this->model->commit();

                    $this->createUpdateTable();

                    $this->model->beginTransaction();
                    $oldData = $this->db->fetchRow($sql);
                }
            }

            // get fields which shouldn't be updated
            $untouchable = array();

            // @TODO: currently we do not support lazyloading in localized fields

            foreach ($fieldDefinitions as $fd) {

                $key = $fd->getName();

                if ($fd) {
                    if ($fd->getQueryColumnType()) {

                        // exclude untouchables if value is not an array - this means data has not been loaded
                        if (!(in_array($key, $untouchable) and !is_array($this->model->$key))) {
                            $localizedValue = $this->model->getLocalizedValue($key, $language);
                            $insertData = $fd->getDataForQueryResource($localizedValue, $object);

                            if (is_array($insertData)) {
                                $data = array_merge($data, $insertData);
                            }
                            else {
                                $data[$key] = $insertData;
                            }

                            //get changed fields for inheritance
                            if($fd->isRelationType()) {
                                if (is_array($insertData)) {
                                    $doInsert = false;
                                    foreach($insertData as $insertDataKey => $insertDataValue) {
                                        if($oldData[$insertDataKey] != $insertDataValue) {
                                            $doInsert = true;
                                        }
                                    }

                                    if($doInsert) {
                                        $this->inheritanceHelper->addRelationToCheck($key, array_keys($insertData));
                                    }
                                } else {
                                    if($oldData[$key] != $insertData) {
                                        $this->inheritanceHelper->addRelationToCheck($key);
                                    }
                                }

                            } else {
                                if (is_array($insertData)) {
                                    foreach($insertData as $insertDataKey => $insertDataValue) {
                                        if($oldData[$insertDataKey] != $insertDataValue) {
                                            $this->inheritanceHelper->addFieldToCheck($insertDataKey);
                                        }
                                    }
                                } else {
                                    if($oldData[$key] != $insertData) {
                                        $this->inheritanceHelper->addFieldToCheck($key);
                                    }
                                }
                            }
                        } else {
                            \Logger::debug("Excluding untouchable query value for object [ " . $this->model->getId() . " ]  key [ $key ] because it has not been loaded");
                        }
                    }
                }
            }


            $queryTable = $this->getQueryTableName() . "_" . $language;
            $this->db->insertOrUpdate($queryTable, $data);
            $this->inheritanceHelper->doUpdate($object->getId());
            $this->inheritanceHelper->resetFieldsToCheck();

            Object\AbstractObject::setGetInheritedValues($inheritedValues);

        } // foreach language
    }

    /**
     * @param bool $deleteQuery
     */
    public function delete ($deleteQuery = true) {

        try {
            if ($deleteQuery) {
                $id = $this->model->getObject()->getId();
                $tablename = $this->getTableName();
                $this->db->delete($tablename, $this->db->quoteInto("ooo_id = ?", $id));

                $validLanguages = Tool::getValidLanguages();
                foreach ($validLanguages as $language) {
                    $queryTable = $this->getQueryTableName() . "_" . $language;
                    $this->db->delete($queryTable, $this->db->quoteInto("ooo_id = ?", $id));
                }
            }
        } catch (\Exception $e) {
            \Logger::error($e);
            $this->createUpdateTable();
        }

        // remove relations
        $this->db->delete("object_relations_" . $this->model->getObject()->getClassId(), $this->db->quoteInto("ownertype = 'localizedfield' AND ownername = 'localizedfield' AND src_id = ?", $this->model->getObject()->getId()));
    }

    /**
     *
     */
    public function load () {
        $validLanguages = Tool::getValidLanguages();
        foreach ($validLanguages as &$language) {
            $language = $this->db->quote($language);
        }

        $data = $this->db->fetchAll("SELECT * FROM " . $this->getTableName() . " WHERE ooo_id = ? AND language IN (" . implode(",",$validLanguages) . ")", $this->model->getObject()->getId());
        foreach ($data as $row) {
            foreach ($this->model->getClass()->getFielddefinition("localizedfields")->getFielddefinitions() as $key => $fd) {
                if($fd) {
                    if (method_exists($fd, "load")) {
                        // datafield has it's own loader
                        $value = $fd->load($this->model, array("language" => $row["language"]));
                        if($value === 0 || !empty($value)) {
                            $this->model->setLocalizedValue($key, $value, $row["language"]);
                        }
                    } else {
                        if (is_array($fd->getColumnType())) {
                            $multidata = array();
                            foreach ($fd->getColumnType() as $fkey => $fvalue) {
                                $multidata[$key . "__" . $fkey] = $row[$key . "__" . $fkey];
                            }
                            $this->model->setLocalizedValue($key, $fd->getDataFromResource($multidata), $row["language"]);
                        } else {
                            $this->model->setLocalizedValue($key, $fd->getDataFromResource($row[$key]), $row["language"]);
                        }
                    }
                }
            }
        }
    }

    /**
     *
     */
    public function createLocalizedViews () {

        $languages = Tool::getValidLanguages();

        $defaultTable = 'object_query_' . $this->model->getClass()->getId();

        foreach ($languages as $language) {
            try {
                $tablename = $this->getQueryTableName() . "_" . $language;
                $this->db->query('CREATE OR REPLACE VIEW `object_localized_' . $this->model->getClass()->getId() . '_' . $language . '` AS SELECT * FROM `' . $defaultTable . '` JOIN `objects` ON `objects`.`o_id` = `' . $defaultTable . '`.`oo_id` left JOIN `' . $tablename . '` ON `' . $defaultTable . '`.`oo_id` = `' . $tablename . '`.`ooo_id`;');
            }
            catch (\Exception $e) {
                \Logger::error($e);
            }
        }
    }

    /**
     *
     */
    public function createUpdateTable () {

        $table = $this->getTableName();

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . $table . "` (
		  `ooo_id` int(11) NOT NULL default '0',
		  `language` varchar(10) NOT NULL DEFAULT '',
		  PRIMARY KEY (`ooo_id`,`language`),
          INDEX `ooo_id` (`ooo_id`),
          INDEX `language` (`language`)
		) DEFAULT CHARSET=utf8;");

        $existingColumns = $this->getValidTableColumns($table, false); // no caching of table definition
        $columnsToRemove = $existingColumns;
        $protectedColumns = array("ooo_id", "language");

        Object\ClassDefinition\Service::updateTableDefinitions($this->tableDefinitions, (array($table)));

        foreach ($this->model->getClass()->getFielddefinition("localizedfields")->getFielddefinitions() as $value) {

            if($value->getColumnType()) {
                $key = $value->getName();

                if (is_array($value->getColumnType())) {
                    // if a datafield requires more than one field
                    foreach ($value->getColumnType() as $fkey => $fvalue) {
                        $this->addModifyColumn($table, $key . "__" . $fkey, $fvalue,"", "NULL");
                        $protectedColumns[] = $key . "__" . $fkey;
                    }
                }
                else {
                    if ($value->getColumnType()) {
                        $this->addModifyColumn($table, $key, $value->getColumnType(), "", "NULL");
                        $protectedColumns[] = $key;
                    }
                }
                $this->addIndexToField($value,$table);
            }
        }

        $this->removeUnusedColumns($table, $columnsToRemove, $protectedColumns);

        $validLanguages = Tool::getValidLanguages();

        foreach ($validLanguages as &$language) {
            $queryTable = $this->getQueryTableName();
            $queryTable .= "_" . $language;

            $this->db->query("CREATE TABLE IF NOT EXISTS `" . $queryTable . "` (
                  `ooo_id` int(11) NOT NULL default '0',
                  `language` varchar(10) NOT NULL DEFAULT '',
                  PRIMARY KEY (`ooo_id`,`language`),
                  INDEX `ooo_id` (`ooo_id`),
                  INDEX `language` (`language`)
                ) DEFAULT CHARSET=utf8;");

            // create object table if not exists
            $protectedColumns = array("ooo_id", "language");

            $existingColumns = $this->getValidTableColumns($queryTable, false); // no caching of table definition
            $columnsToRemove = $existingColumns;

            Object\ClassDefinition\Service::updateTableDefinitions($this->tableDefinitions, array($queryTable));

            $fieldDefinitions = $this->model->getClass()->getFielddefinition("localizedfields")->getFielddefinitions();

            // add non existing columns in the table
            if (is_array($fieldDefinitions) && count($fieldDefinitions)) {
                foreach ($fieldDefinitions as $value) {
                    if($value->getQueryColumnType()) {
                        $key = $value->getName();

                        // if a datafield requires more than one column in the query table
                        if (is_array($value->getQueryColumnType())) {
                            foreach ($value->getQueryColumnType() as $fkey => $fvalue) {
                                $this->addModifyColumn($queryTable, $key . "__" . $fkey, $fvalue, "", "NULL");
                                $protectedColumns[] = $key . "__" . $fkey;
                            }
                        }

                        // everything else
                        if (!is_array($value->getQueryColumnType()) && $value->getQueryColumnType()) {
                            $this->addModifyColumn($queryTable, $key, $value->getQueryColumnType(), "", "NULL");
                            $protectedColumns[] = $key;
                        }

                        // add indices
                        $this->addIndexToField($value, $queryTable);
                    }
                }
            }

            // remove unused columns in the table
            $this->removeUnusedColumns($queryTable, $columnsToRemove, $protectedColumns);
        }

        $this->createLocalizedViews();

        $this->tableDefinitions = null;
    }

    /**
     * @param $field
     * @param $table
     */
    private function addIndexToField ($field, $table) {

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
                } catch (\Exception $e) {
                }
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
    private function addModifyColumn ($table, $colName, $type, $default, $null) {

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
    private function removeUnusedColumns ($table, $columnsToRemove, $protectedColumns) {
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
