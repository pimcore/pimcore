<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\ClassDefinition;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Tool\Serialize;
use Pimcore\File;

class Dao extends Model\Dao\AbstractDao
{
    use Object\ClassDefinition\Helper\Dao;

    /**
     * @var Object\ClassDefinition
     */
    protected $model;

    protected $_sqlChangeLog = array();

    protected $tableDefinitions = null;

    /**
     * @param null $id
     * @throws \Exception
     */
    public function getById($id = null)
    {
        if (!$id) {
            $id = $this->model->getId();
        }

        $classRaw = $this->db->fetchRow("SELECT * FROM classes WHERE id = ?", $id);

        if ($classRaw["id"]) {
            $this->assignVariablesToModel($classRaw);

            $this->model->setPropertyVisibility(Serialize::unserialize($classRaw["propertyVisibility"]));
            $this->model->setLayoutDefinitions($this->getLayoutData());
        } else {
            throw new \Exception("Class with ID " . $id . " doesn't exist");
        }
    }

    /**
     * @param null $name
     * @throws \Exception
     */
    public function getByName($name = null)
    {
        if (!$name) {
            $name = $this->model->getName();
        }

        $classRaw = $this->db->fetchRow("SELECT id FROM classes WHERE name = ?", $name);

        if ($classRaw["id"]) {
            $this->assignVariablesToModel($classRaw);
            // the layout is loaded in Object|Class::getByName();
        } else {
            throw new \Exception("Class with name " . $name . " doesn't exist");
        }
    }

    /**
     * Save object to database
     *
     * @return mixed
     */
    protected function getLayoutData()
    {
        $file = PIMCORE_CLASS_DIRECTORY."/definition_". $this->model->getId() .".psf";
        if (is_file($file)) {
            return Serialize::unserialize(file_get_contents($file));
        }
        return;
    }


    /**
     * Save object to database
     *
     * @return void
     */
    public function save()
    {
        if ($this->model->getId()) {
            return $this->update();
        }
        return $this->create();
    }

    /**
     * @throws \Exception
     * @throws \Zend_Db_Adapter_Exception
     */
    public function update()
    {
        $class = get_object_vars($this->model);
        $data = array();

        foreach ($class as $key => $value) {
            if (in_array($key, $this->getValidTableColumns("classes"))) {
                if (is_array($value) || is_object($value)) {
                    $value = Serialize::serialize($value);
                } elseif (is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$key] = $value;
            }
        }

        $this->db->update("classes", $data, $this->db->quoteInto("id = ?", $this->model->getId()));

        // save definition as a serialized file
        $definitionFile = PIMCORE_CLASS_DIRECTORY."/definition_". $this->model->getId() .".psf";
        if (!is_writable(dirname($definitionFile)) || (is_file($definitionFile) && !is_writable($definitionFile))) {
            throw new \Exception("Cannot write definition file in: " . $definitionFile . " please check write permission on this directory.");
        }
        File::put($definitionFile, Serialize::serialize($this->model->layoutDefinitions));

        $objectTable = "object_query_" . $this->model->getId();
        $objectDatastoreTable = "object_store_" . $this->model->getId();
        $objectDatastoreTableRelation = "object_relations_" . $this->model->getId();

        $objectView = "object_" . $this->model->getId();

        // create object table if not exists
        $protectedColums = array("oo_id", "oo_classId", "oo_className");
        $protectedDatastoreColumns = array("oo_id");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . $objectTable . "` (
			  `oo_id` int(11) NOT NULL default '0',
			  `oo_classId` int(11) default '" . $this->model->getId() . "',
			  `oo_className` varchar(255) default '" . $this->model->getName() . "',
			  PRIMARY KEY  (`oo_id`)
			) DEFAULT CHARSET=utf8;");

        // update default value of classname columns
        $this->db->query('ALTER TABLE `' . $objectTable . "` ALTER COLUMN `oo_className` SET DEFAULT '" . $this->model->getName() . "';");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . $objectDatastoreTable . "` (
			  `oo_id` int(11) NOT NULL default '0',
			  PRIMARY KEY  (`oo_id`)
			) DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . $objectDatastoreTableRelation . "` (
          `src_id` int(11) NOT NULL DEFAULT '0',
          `dest_id` int(11) NOT NULL DEFAULT '0',
          `type` varchar(50) NOT NULL DEFAULT '',
          `fieldname` varchar(70) NOT NULL DEFAULT '0',
          `index` int(11) unsigned NOT NULL DEFAULT '0',
          `ownertype` enum('object','fieldcollection','localizedfield','objectbrick') NOT NULL DEFAULT 'object',
          `ownername` varchar(70) NOT NULL DEFAULT '',
          `position` varchar(70) NOT NULL DEFAULT '0',
          PRIMARY KEY (`src_id`,`dest_id`,`ownertype`,`ownername`,`fieldname`,`type`,`position`),
          KEY `index` (`index`),
          KEY `src_id` (`src_id`),
          KEY `dest_id` (`dest_id`),
          KEY `fieldname` (`fieldname`),
          KEY `position` (`position`),
          KEY `ownertype` (`ownertype`),
          KEY `type` (`type`),
          KEY `ownername` (`ownername`)
        ) DEFAULT CHARSET=utf8;");



        $existingColumns = $this->getValidTableColumns($objectTable, false); // no caching of table definition
        $existingDatastoreColumns = $this->getValidTableColumns($objectDatastoreTable, false); // no caching of table definition

        $columnsToRemove = $existingColumns;
        $datastoreColumnsToRemove = $existingDatastoreColumns;

        Object\ClassDefinition\Service::updateTableDefinitions($this->tableDefinitions, array($objectTable, $objectDatastoreTable));

        // add non existing columns in the table
        if (is_array($this->model->getFieldDefinitions()) && count($this->model->getFieldDefinitions())) {
            foreach ($this->model->getFieldDefinitions() as $key => $value) {



                // if a datafield requires more than one column in the query table
                if (is_array($value->getQueryColumnType())) {
                    foreach ($value->getQueryColumnType() as $fkey => $fvalue) {
                        $this->addModifyColumn($objectTable, $key . "__" . $fkey, $fvalue, "", "NULL");
                        $protectedColums[] = $key . "__" . $fkey;
                    }
                }

                // if a datafield requires more than one column in the datastore table => only for non-relation types
                if (!$value->isRelationType() && is_array($value->getColumnType())) {
                    foreach ($value->getColumnType() as $fkey => $fvalue) {
                        $this->addModifyColumn($objectDatastoreTable, $key . "__" . $fkey, $fvalue, "", "NULL");
                        $protectedDatastoreColumns[] = $key . "__" . $fkey;
                    }
                }

                // everything else
//                if (!is_array($value->getQueryColumnType()) && !is_array($value->getColumnType())) {
                if (!is_array($value->getQueryColumnType()) && $value->getQueryColumnType()) {
                    $this->addModifyColumn($objectTable, $key, $value->getQueryColumnType(), "", "NULL");
                    $protectedColums[] = $key;
                }
                if (!is_array($value->getColumnType()) && $value->getColumnType() && !$value->isRelationType()) {
                    $this->addModifyColumn($objectDatastoreTable, $key, $value->getColumnType(), "", "NULL");
                    $protectedDatastoreColumns[] = $key;
                }
//                }

                // add indices
                $this->addIndexToField($value, $objectTable, "getQueryColumnType");
                $this->addIndexToField($value, $objectDatastoreTable, "getColumnType");
            }
        }

        // remove unused columns in the table
        $this->removeUnusedColumns($objectTable, $columnsToRemove, $protectedColums);
        $this->removeUnusedColumns($objectDatastoreTable, $datastoreColumnsToRemove, $protectedDatastoreColumns);

        // remove / cleanup unused relations
        if (is_array($datastoreColumnsToRemove)) {
            foreach ($datastoreColumnsToRemove as $value) {
                if (!in_array(strtolower($value), array_map('strtolower', $protectedDatastoreColumns))) {

                    $tableRelation = "object_relations_" . $this->model->getId();
                    $this->db->delete($tableRelation, "fieldname = " . $this->db->quote($value) . " AND ownertype = 'object'");

                    // @TODO: remove localized fields and fieldcollections
                }
            }
        }

        // create view
        try {
            //$this->db->query('CREATE OR REPLACE VIEW `' . $objectView . '` AS SELECT * FROM `objects` left JOIN `' . $objectTable . '` ON `objects`.`o_id` = `' . $objectTable . '`.`oo_id` WHERE `objects`.`o_classId` = ' . $this->model->getId() . ';');
            $this->db->query('CREATE OR REPLACE VIEW `' . $objectView . '` AS SELECT * FROM `' . $objectTable . '` JOIN `objects` ON `objects`.`o_id` = `' . $objectTable . '`.`oo_id`;');
        } catch (\Exception $e) {
            \Logger::debug($e);
        }

        $this->tableDefinitions = null;
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create()
    {
        $this->db->insert("classes", array("name" => $this->model->getName()));

        $this->model->setId($this->db->lastInsertId());
        $this->model->setCreationDate(time());
        $this->model->setModificationDate(time());

        $this->save();
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete()
    {
        $this->db->delete("classes", $this->db->quoteInto("id = ?", $this->model->getId()));

        $objectTable = "object_query_" . $this->model->getId();
        $objectDatastoreTable = "object_store_" . $this->model->getId();
        $objectDatastoreTableRelation = "object_relations_" . $this->model->getId();
        $objectMetadataTable = "object_metadata_" . $this->model->getId();


        $this->db->query('DROP TABLE `' . $objectTable . '`');
        $this->db->query('DROP TABLE `' . $objectDatastoreTable . '`');
        $this->db->query('DROP TABLE `' . $objectDatastoreTableRelation . '`');
        $this->db->query('DROP TABLE IF EXISTS `' . $objectMetadataTable . '`');

        $this->db->query('DROP VIEW `object_' . $this->model->getId() . '`');

        // delete data
        $this->db->delete("objects", $this->db->quoteInto("o_classId = ?", $this->model->getId()));

        // remove fieldcollection tables
        $allTables = $this->db->fetchAll("SHOW TABLES LIKE 'object_collection_%_" . $this->model->getId() . "'");
        foreach ($allTables as $table) {
            $collectionTable = current($table);
            $this->db->query("DROP TABLE IF EXISTS `".$collectionTable."`");
        }

        // remove localized fields tables and views
        $allViews = $this->db->fetchAll("SHOW TABLES LIKE 'object_localized_" . $this->model->getId() . "_%'");
        foreach ($allViews as $view) {
            $localizedView = current($view);
            $this->db->query("DROP VIEW IF EXISTS `".$localizedView."`");
        }

        $allTables = $this->db->fetchAll("SHOW TABLES LIKE 'object_localized_query_" . $this->model->getId() . "_%'");
        foreach ($allTables as $table) {
            $queryTable = current($table);
            $this->db->query("DROP TABLE IF EXISTS `".$queryTable."`");
        }

        $this->db->query("DROP TABLE IF EXISTS object_localized_data_" . $this->model->getId());

        // objectbrick tables
        $allTables = $this->db->fetchAll("SHOW TABLES LIKE 'object_brick_%_" . $this->model->getId() . "'");
        foreach ($allTables as $table) {
            $brickTable = current($table);
            $this->db->query("DROP TABLE `".$brickTable."`");
        }

        @unlink(PIMCORE_CLASS_DIRECTORY."/definition_". $this->model->getId() .".psf");
    }

    /**
     * Update the class name in all object
     *
     * @return void
     */
    public function updateClassNameInObjects($newName)
    {
        $this->db->update("objects", array(
            "o_className" => $newName
        ), $this->db->quoteInto("o_classId = ?", $this->model->getId()));

        $this->db->update("object_query_" . $this->model->getId(), array(
            "oo_className" => $newName
        ));
    }
}
