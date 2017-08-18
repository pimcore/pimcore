<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Object;

/**
 * @property \Pimcore\Model\Object\ClassDefinition $model
 */
class Dao extends Model\Dao\AbstractDao
{
    use Object\ClassDefinition\Helper\Dao;

    /**
     * @var Object\ClassDefinition
     */
    protected $model;

    /**
     * @var array
     */
    protected $_sqlChangeLog = [];

    /**
     * @var mixed
     */
    protected $tableDefinitions = null;

    /**
     * @param null $id
     *
     * @return string
     */
    public function getNameById($id = null)
    {
        $name = $this->db->fetchOne('SELECT name FROM classes WHERE id = ?', $id);

        return $name;
    }

    /**
     * @param null $name
     *
     * @return string
     */
    public function getIdByName($name = null)
    {
        $id = $this->db->fetchOne('SELECT id FROM classes WHERE name = ?', $name);

        return $id;
    }

    /**
     * Save object to database
     *
     * @return bool
     *
     * @todo: update() or create() don't return anything
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
     * @throws \Exception
     */
    public function update()
    {
        $class = get_object_vars($this->model);
        $data = [];

        foreach ($class as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('classes'))) {
                $data[$key] = $value;
            }
        }

        $this->db->update('classes', $data, ['id' => $this->model->getId()]);

        $objectTable = 'object_query_' . $this->model->getId();
        $objectDatastoreTable = 'object_store_' . $this->model->getId();
        $objectDatastoreTableRelation = 'object_relations_' . $this->model->getId();

        $objectView = 'object_' . $this->model->getId();

        // create object table if not exists
        $protectedColums = ['oo_id', 'oo_classId', 'oo_className'];
        $protectedDatastoreColumns = ['oo_id'];

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $objectTable . "` (
			  `oo_id` int(11) NOT NULL default '0',
			  `oo_classId` int(11) default '" . $this->model->getId() . "',
			  `oo_className` varchar(255) default '" . $this->model->getName() . "',
			  PRIMARY KEY  (`oo_id`)
			) DEFAULT CHARSET=utf8mb4;");

        // update default value of classname columns
        $this->db->query('ALTER TABLE `' . $objectTable . "` ALTER COLUMN `oo_className` SET DEFAULT '" . $this->model->getName() . "';");

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $objectDatastoreTable . "` (
			  `oo_id` int(11) NOT NULL default '0',
			  PRIMARY KEY  (`oo_id`)
			) DEFAULT CHARSET=utf8mb4;");

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $objectDatastoreTableRelation . "` (
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
        ) DEFAULT CHARSET=utf8mb4;");

        $existingColumns = $this->getValidTableColumns($objectTable, false); // no caching of table definition
        $existingDatastoreColumns = $this->getValidTableColumns($objectDatastoreTable, false); // no caching of table definition

        $columnsToRemove = $existingColumns;
        $datastoreColumnsToRemove = $existingDatastoreColumns;

        Object\ClassDefinition\Service::updateTableDefinitions($this->tableDefinitions, [$objectTable, $objectDatastoreTable]);

        // add non existing columns in the table
        if (is_array($this->model->getFieldDefinitions()) && count($this->model->getFieldDefinitions())) {
            foreach ($this->model->getFieldDefinitions() as $key => $value) {

                // if a datafield requires more than one column in the query table
                if (is_array($value->getQueryColumnType())) {
                    foreach ($value->getQueryColumnType() as $fkey => $fvalue) {
                        $this->addModifyColumn($objectTable, $key . '__' . $fkey, $fvalue, '', 'NULL');
                        $protectedColums[] = $key . '__' . $fkey;
                    }
                }

                // if a datafield requires more than one column in the datastore table => only for non-relation types
                if (!$value->isRelationType() && is_array($value->getColumnType())) {
                    foreach ($value->getColumnType() as $fkey => $fvalue) {
                        $this->addModifyColumn($objectDatastoreTable, $key . '__' . $fkey, $fvalue, '', 'NULL');
                        $protectedDatastoreColumns[] = $key . '__' . $fkey;
                    }
                }

                // everything else
                //                if (!is_array($value->getQueryColumnType()) && !is_array($value->getColumnType())) {
                if (!is_array($value->getQueryColumnType()) && $value->getQueryColumnType()) {
                    $this->addModifyColumn($objectTable, $key, $value->getQueryColumnType(), '', 'NULL');
                    $protectedColums[] = $key;
                }
                if (!is_array($value->getColumnType()) && $value->getColumnType() && !$value->isRelationType()) {
                    $this->addModifyColumn($objectDatastoreTable, $key, $value->getColumnType(), '', 'NULL');
                    $protectedDatastoreColumns[] = $key;
                }
                //                }

                // add indices
                $this->addIndexToField($value, $objectTable, 'getQueryColumnType');
                $this->addIndexToField($value, $objectDatastoreTable, 'getColumnType', true);
            }
        }

        // remove unused columns in the table
        $this->removeUnusedColumns($objectTable, $columnsToRemove, $protectedColums);
        $this->removeUnusedColumns($objectDatastoreTable, $datastoreColumnsToRemove, $protectedDatastoreColumns);

        // remove / cleanup unused relations
        if (is_array($datastoreColumnsToRemove)) {
            foreach ($datastoreColumnsToRemove as $value) {
                if (!in_array(strtolower($value), array_map('strtolower', $protectedDatastoreColumns))) {
                    $tableRelation = 'object_relations_' . $this->model->getId();
                    $this->db->delete($tableRelation, ['fieldname' => $value, 'ownertype' => 'object']);
                    // @TODO: remove localized fields and fieldcollections
                }
            }
        }

        // create view
        try {
            //$this->db->query('CREATE OR REPLACE VIEW `' . $objectView . '` AS SELECT * FROM `objects` left JOIN `' . $objectTable . '` ON `objects`.`o_id` = `' . $objectTable . '`.`oo_id` WHERE `objects`.`o_classId` = ' . $this->model->getId() . ';');
            $this->db->query('CREATE OR REPLACE VIEW `' . $objectView . '` AS SELECT * FROM `' . $objectTable . '` JOIN `objects` ON `objects`.`o_id` = `' . $objectTable . '`.`oo_id`;');
        } catch (\Exception $e) {
            Logger::debug($e);
        }

        $this->tableDefinitions = null;
    }

    /**
     * Create a new record for the object in database
     *
     * @return bool
     */
    public function create()
    {
        $this->db->insert('classes', ['name' => $this->model->getName()]);
        $this->model->setId($this->db->lastInsertId());
        $this->save();
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $this->db->delete('classes', ['id' => $this->model->getId()]);

        $objectTable = 'object_query_' . $this->model->getId();
        $objectDatastoreTable = 'object_store_' . $this->model->getId();
        $objectDatastoreTableRelation = 'object_relations_' . $this->model->getId();
        $objectMetadataTable = 'object_metadata_' . $this->model->getId();

        $this->db->query('DROP TABLE `' . $objectTable . '`');
        $this->db->query('DROP TABLE `' . $objectDatastoreTable . '`');
        $this->db->query('DROP TABLE `' . $objectDatastoreTableRelation . '`');
        $this->db->query('DROP TABLE IF EXISTS `' . $objectMetadataTable . '`');

        $this->db->query('DROP VIEW `object_' . $this->model->getId() . '`');

        // delete data
        $this->db->delete('objects', ['o_classId' => $this->model->getId()]);

        // remove fieldcollection tables
        $allTables = $this->db->fetchAll("SHOW TABLES LIKE 'object\_collection\_%\_" . $this->model->getId() . "'");
        foreach ($allTables as $table) {
            $collectionTable = current($table);
            $this->db->query('DROP TABLE IF EXISTS `'.$collectionTable.'`');
        }

        // remove localized fields tables and views
        $allViews = $this->db->fetchAll("SHOW TABLES LIKE 'object\_localized\_" . $this->model->getId() . "\_%'");
        foreach ($allViews as $view) {
            $localizedView = current($view);
            $this->db->query('DROP VIEW IF EXISTS `'.$localizedView.'`');
        }

        $allTables = $this->db->fetchAll("SHOW TABLES LIKE 'object\_localized\_query\_" . $this->model->getId() . "\_%'");
        foreach ($allTables as $table) {
            $queryTable = current($table);
            $this->db->query('DROP TABLE IF EXISTS `'.$queryTable.'`');
        }

        $this->db->query('DROP TABLE IF EXISTS object_localized_data_' . $this->model->getId());

        // objectbrick tables
        $allTables = $this->db->fetchAll("SHOW TABLES LIKE 'object\_brick\_%\_" . $this->model->getId() . "'");
        foreach ($allTables as $table) {
            $brickTable = current($table);
            $this->db->query('DROP TABLE `'.$brickTable.'`');
        }
    }

    /**
     * Update the class name in all object
     *
     * @param $newName
     */
    public function updateClassNameInObjects($newName)
    {
        $this->db->update('objects', ['o_className' => $newName], ['o_classId' => $this->model->getId()]);

        $this->db->updateWhere('object_query_' . $this->model->getId(), [
            'oo_className' => $newName
        ]);
    }
}
