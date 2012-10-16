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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Localizedfield_Resource extends Pimcore_Model_Resource_Abstract {

    public function getTableName () {
        return "object_localized_data_" . $this->model->getClass()->getId();
    }

    public function save () {
        $this->delete();

        $object = $this->model->getObject();

        foreach ($this->model->getItems() as $language => $items) {

            $insertData = array(
                "ooo_id" => $this->model->getObject()->getId(),
                "language" => $language
            );

            foreach ($this->model->getClass()->getFielddefinition("localizedfields")->getFielddefinitions() as $fd) {
                /*if($fd->isRelationType()) {

                    $relations = $fd->getDataForResource($items[$fd->getName()], $this->model);

                    if (is_array($relations) && !empty($relations)) {
                        foreach ($relations as $relation) {
                            $relation["src_id"] = $object->getId();
                            $relation["ownertype"] = "localizedfield";
                            $relation["ownername"] = "localizedfield";
                            $relation["position"] = $language;

                            //relation needs to be an array with src_id, dest_id, type, fieldname
                            try {
                                $this->db->insert("object_relations_" . $object->getO_classId(), $relation);
                            } catch (Exception $e) {
                                Logger::warning("It seems that the relation " . $relation["src_id"] . " => " . $relation["dest_id"] . " already exist");
                            }
                        }
                    }
                }*/

                if (method_exists($fd, "save")) {
                    // for fieldtypes which have their own save algorithm eg. objects, multihref, ...
                    $fd->save($this->model, array("language" => $language));
                    
                } else {
                    if (is_array($fd->getColumnType())) {
                        $insertDataArray = $fd->getDataForResource($items[$fd->getName()], $object);
                        $insertData = array_merge($insertData, $insertDataArray);
                    } else {
                        $insertData[$fd->getName()] = $fd->getDataForResource($items[$fd->getName()], $object);
                    }
                }
            }
            
            $this->db->insert($this->getTableName(), $insertData);
        }
    }

    public function delete () {

        try {
            $this->db->delete($this->getTableName(), $this->db->quoteInto("ooo_id = ?", $this->model->getObject()->getId()));
        } catch (Exception $e) {
            $this->createUpdateTable();
        }

        // remove relations
        $this->db->delete("object_relations_" . $this->model->getObject()->getO_classId(), $this->db->quoteInto("ownertype = 'localizedfield' AND ownername = 'localizedfield' AND src_id = ?", $this->model->getObject()->getId()));
    }

    public function load () {

        $items = array();

        $data = $this->db->fetchAll("SELECT * FROM " . $this->getTableName() . " WHERE ooo_id = ?", $this->model->getObject()->getId());
        foreach ($data as $row) {
            foreach ($this->model->getClass()->getFielddefinition("localizedfields")->getFielddefinitions() as $key => $fd) {
                if($fd) {
                    if (method_exists($fd, "load")) {
                        // datafield has it's own loader
                        $value = $fd->load($this->model, array("language" => $row["language"]));
                        if($value === 0 || !empty($value)) {
                            $items[$row["language"]][$key] = $value;
                        }
                    } else {
                        if (is_array($fd->getColumnType())) {
                            $multidata = array();
                            foreach ($fd->getColumnType() as $fkey => $fvalue) {
                                $multidata[$key . "__" . $fkey] = $row[$key . "__" . $fkey];
                            }
                            $items[$row["language"]][$key] = $fd->getDataFromResource($multidata);

                        } else {
                            $items[$row["language"]][$key] = $fd->getDataFromResource($row[$key]);
                        }
                    }
                }
            }
        }

        $this->model->setItems($items);

        return $items;
    }

    public function createLocalizedViews () {

        $languages = array();
        $conf = Pimcore_Config::getSystemConfig();
        if($conf->general->validLanguages) {
            $languages = explode(",",$conf->general->validLanguages);
        }

        $defaultView = 'object_' . $this->model->getClass()->getId();
        
        foreach ($languages as $language) {
            try {

                $this->db->query('CREATE OR REPLACE VIEW `object_localized_' . $this->model->getClass()->getId() . '_' . $language . '` AS SELECT * FROM `' . $defaultView . '` left JOIN `' . $this->getTableName() . '` ON `' . $defaultView . '`.`o_id` = `' . $this->getTableName() . '`.`ooo_id` AND `' . $this->getTableName() . '`.`language` = \'' . $language . '\';');
            }
            catch (Exception $e) {
                Logger::error($e);
            }
        }

        $concats = array();
        if($this->model->getClass()->getFielddefinition("localizedfields")) {
            foreach ($this->model->getClass()->getFielddefinition("localizedfields")->getFielddefinitions() as $fd) {
                // only add non-relational fields with one column to the group-concat
                if(!$fd->isRelationType() && !is_array($fd->getColumnType())) {
                    $concats[] = "group_concat(" . $this->getTableName() . "." . $fd->getName() . ") AS `" . $fd->getName() . "`";
                }
            }
        }

        // and now the default view for query where the locale is missing

        $furtherSelects = implode(",",$concats);
        if(!empty($furtherSelects)) {
            $furtherSelects = "," . $furtherSelects;
        }

        $this->db->query('CREATE OR REPLACE VIEW `object_localized_' . $this->model->getClass()->getId() . '_default` AS SELECT `' . $defaultView . '`.* ' . $furtherSelects . ' FROM `' . $defaultView . '` left JOIN `' . $this->getTableName() . '` ON `' . $defaultView . '`.`o_id` = `' . $this->getTableName() . '`.`ooo_id` GROUP BY `' . $defaultView . '`.`o_id`;');
    }

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
        $protectedColums = array("ooo_id", "language");

        foreach ($this->model->getClass()->getFielddefinition("localizedfields")->getFielddefinitions() as $value) {

            // continue to the next field if the current one is a relational field
            if($value->isRelationType()) {
                continue;
            }

            $key = $value->getName();



            if (is_array($value->getColumnType())) {
                // if a datafield requires more than one field
                foreach ($value->getColumnType() as $fkey => $fvalue) {
                    $this->addModifyColumn($table, $key . "__" . $fkey, $fvalue,"", "NULL");
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

        $this->createLocalizedViews();
    }



    private function addIndexToField ($field, $table) {

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
        } else {
            $this->db->query('ALTER TABLE `' . $table . '` CHANGE COLUMN `' . $existingColName . '` `' . $colName . '` ' . $type . $default . ' ' . $null . ';');
        }
    }

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
