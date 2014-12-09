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

namespace Pimcore\Model\Object\Data\KeyValue;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumns = array();

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {
        $this->delete();
        $db = $this->db;
        $model = $this->model;
        $objectId = $model->getObjectId();
        $properties = $model->getInternalProperties();
        foreach ($properties as $pair) {
            $key = $pair["key"];
            $value = $pair["value"];
            $translated = $pair["translated"];
            $metadata = $pair["metadata"];

            $this->db->insert($this->getTableName(), array(
                "o_id" => $objectId,
                "key" => $key,
                "value" => $value,
                "translated" => $translated,
                "metadata" => $metadata
            ));
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {

        $sql = $this->db->quoteInto("o_id = ?", $this->model->getObjectId());

        // $sql = "o_id = " . $this->model->getObjectId();
        \Logger::debug("query= " . $sql);
        $this->db->delete($this->getTableName(), $sql);
    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     *
     * @return void
     */
    public function update() {
        \Logger::debug("update called");
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {
    }

    /**
     * @return string
     */
    public function getTableName() {
        $model = $this->model;
        $class = $model->getClass();
        $classId = $class->getId();
        return "object_keyvalue_" . $classId;
    }

    /**
     *
     */
    public function createUpdateTable () {
        \Logger::debug("createUpdateTable called");

        $model = $this->model;
        $class = $model->getClass();;
        $classId = $class->getId();
        $table = $this->getTableName();

        $db = \Pimcore\Resource::get();
        $db->query("CREATE TABLE IF NOT EXISTS `" . $table . "` (
    		`id` INT NOT NULL AUTO_INCREMENT,
    		`o_id` INT NOT NULL,
    		`key` INT NOT NULL,
    		`value` VARCHAR(255),
            `translated` LONGTEXT NULL,
            `metadata` LONGTEXT NULL,
    	    PRIMARY KEY  (`id`),
	        INDEX `o_id` (`o_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $validColumns = $this->getValidTableColumns($table, false); // no caching of table definition

        if (!in_array("translated", $validColumns)) {
            $db->query("ALTER TABLE `" . $table . "` ADD COLUMN `translated` LONGTEXT NULL AFTER `value`;");
        }

        if (!in_array("metadata", $validColumns)) {
            $db->query("ALTER TABLE `" . $table . "` ADD COLUMN `metadata` LONGTEXT NULL AFTER `translated`;");
        }

        \Logger::debug("createUpdateTable done");
    }

    /**
     *
     */
    public function load() {
        $model = $this->model;
        \Logger::debug("load called");

        $table = $this->getTableName();
        $db = \Pimcore\Resource::get();
        $sql = "SELECT * FROM " . $table . " WHERE o_id = " . $model->getObjectId();
        $result = $db->fetchAll($sql);
        $model->setProperties($result);

        \Logger::debug("result=" . $result);
    }
}
