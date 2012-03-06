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
 * @package    User
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class User_Abstract_Resource extends Pimcore_Model_Resource_Abstract {
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
        $this->validColumns = $this->getValidTableColumns("users");
    }


    /**
     * Get the data for the object from database for the given id
     *
     * @param integer $id
     * @return void
     */
    public function getById($id) {

        if($this->model->getType()) {
            $data = $this->db->fetchRow("SELECT * FROM users WHERE `type` = ? AND id = ?", array($this->model->getType(), $id));
        } else {
            $data = $this->db->fetchRow("SELECT * FROM users WHERE `id` = ?", $id);
        }

        if (is_numeric($data["id"])) {
            $this->assignVariablesToModel($data);
        } else {
            throw new Exception("user doesn't exist");
        }


    }

    /**
     * Get the data for the object from database for the given name
     *
     * @param string $name
     * @return void
     */
    public function getByName($name) {
        try {
            $data = $this->db->fetchRow("SELECT * FROM users WHERE `type` = ? AND `name` = ?", array($this->model->getType(), $name));

            if ($data["id"]) {
                $this->assignVariablesToModel($data);
            }
            else {
                throw new Exception("user doesn't exist");
            }
        }
        catch (Exception $e) {
            throw $e;
        }

    }


    /**
     * Get the data for the object from database for the given name
     *
     * @param string $name
     * @return void
     */
    public function save() {

        if ($this->model->getId()) {
            return $this->model->update();
        }
        return $this->create();
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {
        try {
            $this->db->insert("users", array(
                "name" => $this->model->getName(),
                "type" => $this->model->getType()
            ));

            $this->model->setId($this->db->lastInsertId());

            return $this->save();
        }
        catch (Exception $e) {
            throw $e;
        }
    }

     /**
     * Quick test if there are children
     *
     * @return boolean
     */
    public function hasChilds() {
        $c = $this->db->fetchOne("SELECT id FROM users WHERE parentId = ?",  $this->model->getId());
        return (bool) $c;
    }


    /**
     * Save changes to database, it's an good idea to use save() instead
     *
     * @return void
     */
    public function update() {
        try {
            $data = array();
            $dataRaw = get_object_vars($this->model);
            foreach ($dataRaw as $key => $value) {
                if (in_array($key, $this->validColumns)) {

                    if (is_bool($value)) {
                        $value = (int) $value;
                    } else if($key == "permissions" || $key == "roles") {
                        // permission and roles are stored as csv
                        $value = implode(",", $value);
                    }
                    $data[$key] = $value;
                }
            }

            $this->db->update("users", $data, $this->db->quoteInto("id = ?", $this->model->getId() ));

        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {

        $userId = $this->model->getId();
        Logger::debug("delete user with ID: " . $userId);

        try {
            $this->db->delete("users", $this->db->quoteInto("id = ?", $userId ));
        }
        catch (Exception $e) {
            throw $e;
        }
    }
}
