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
 * @package    Object
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Data\KeyValue;

use Pimcore\Model;
use Pimcore\Logger;

/**
 * @property \Pimcore\Model\Object\Data\KeyValue $model
 */
class Dao extends Model\Dao\AbstractDao
{

    /**
     * Get the valid columns from the database
     */
    public function init()
    {
    }

    /**
     * Save object to database
     */
    public function save()
    {
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

            $this->db->insert($this->getTableName(), [
                "o_id" => $objectId,
                "key" => $key,
                "value" => $value,
                "translated" => $translated,
                "metadata" => $metadata
            ]);
        }
    }

    /**
     * Deletes object from database
     */
    public function delete()
    {
        $sql = $this->db->quoteInto("o_id = ?", $this->model->getObjectId());

        // $sql = "o_id = " . $this->model->getObjectId();
        Logger::debug("query= " . $sql);
        $this->db->delete($this->getTableName(), $sql);
    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     */
    public function update()
    {
        Logger::debug("update called");
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create()
    {
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        $model = $this->model;
        $class = $model->getClass();
        $classId = $class->getId();

        return "object_keyvalue_" . $classId;
    }

    /**
     *
     */
    public function createUpdateTable()
    {
        Logger::debug("createUpdateTable called");

        $model = $this->model;
        $class = $model->getClass();
        ;
        $classId = $class->getId();
        $table = $this->getTableName();

        $db = \Pimcore\Db::get();
        $db->query("CREATE TABLE IF NOT EXISTS `" . $table . "` (
    		`id` INT NOT NULL AUTO_INCREMENT,
    		`o_id` INT NOT NULL,
    		`key` INT NOT NULL,
    		`value` VARCHAR(255),
            `translated` LONGTEXT NULL,
            `metadata` LONGTEXT NULL,
    	    PRIMARY KEY  (`id`),
	        INDEX `o_id` (`o_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $validColumns = $this->getValidTableColumns($table, false); // no caching of table definition

        if (!in_array("translated", $validColumns)) {
            $db->query("ALTER TABLE `" . $table . "` ADD COLUMN `translated` LONGTEXT NULL AFTER `value`;");
        }

        if (!in_array("metadata", $validColumns)) {
            $db->query("ALTER TABLE `" . $table . "` ADD COLUMN `metadata` LONGTEXT NULL AFTER `translated`;");
        }

        Logger::debug("createUpdateTable done");
    }

    /**
     *
     */
    public function load()
    {
        $model = $this->model;
        Logger::debug("load called");

        $table = $this->getTableName();
        $db = \Pimcore\Db::get();
        $sql = "SELECT * FROM " . $table . " WHERE o_id = " . $model->getObjectId();
        $result = $db->fetchAll($sql);
        $model->setProperties($result);

        Logger::debug("result=" . $result);
    }
}
