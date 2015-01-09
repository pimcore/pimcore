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

namespace Pimcore\Model\Object\KeyValue\GroupConfig;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {

    const TABLE_NAME_GROUPS = "keyvalue_groups";

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
        $this->validColumns = $this->getValidTableColumns(self::TABLE_NAME_GROUPS);
    }

    /**
     * Get the data for the object from database for the given id, or from the ID which is set in the object
     *
     * @param integer $id
     * @return void
     */
    public function getById($id = null) {

        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow("SELECT * FROM " . self::TABLE_NAME_GROUPS . " WHERE id = ?", $this->model->getId());

        $this->assignVariablesToModel($data);
    }

    /**
     * @param null $name
     * @throws \Exception
     */
    public function getByName($name = null) {

        if ($name != null) {
            $this->model->setName($name);
        }

        $name = $this->model->getName();

        $data = $this->db->fetchRow("SELECT * FROM " . self::TABLE_NAME_GROUPS . " WHERE name = ?", $name);

        if($data["id"]) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("Config with name: " . $this->model->getName() . " does not exist");
        }
    }


    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {
        if ($this->model->getId()) {
            return $this->model->update();
        }
        return $this->create();
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete(self::TABLE_NAME_GROUPS, $this->db->quoteInto("id = ?", $this->model->getId()));
    }

    /**
     * @throws \Exception
     */
    public function update() {
        try {
            $ts = time();
            $this->model->setModificationDate($ts);

            $type = get_object_vars($this->model);

            foreach ($type as $key => $value) {
                if (in_array($key, $this->validColumns)) {
                    if(is_bool($value)) {
                        $value = (int) $value;
                    }
                    if(is_array($value) || is_object($value)) {
                        $value = \Pimcore\Tool\Serialize::serialize($value);
                    }

                    $data[$key] = $value;
                }
            }

            $this->db->update(self::TABLE_NAME_GROUPS, $data, $this->db->quoteInto("id = ?", $this->model->getId()));
            return $this->model;
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {
        $ts = time();
        $this->model->setModificationDate($ts);
        $this->model->setCreationDate($ts);

        $this->db->insert(self::TABLE_NAME_GROUPS, array());

        $this->model->setId($this->db->lastInsertId());

        return $this->save();
    }
}
