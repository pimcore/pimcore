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
 * @package    Property
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Property_Predefined_Resource extends Pimcore_Model_Resource_Abstract {

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
        $this->validColumns = $this->getValidTableColumns("properties_predefined");
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

        $data = $this->db->fetchRow("SELECT * FROM properties_predefined WHERE id = ?", $this->model->getId());
        $this->assignVariablesToModel($data);
    }


    /**
     * Get the data for the object from database for the given key, or from the key which is set in the object
     *
     * @param string $key
     * @return void
     */
    public function getByKey($key = null) {

        if ($key != null) {
            $this->model->setKey($key);
        }

        $data = $this->db->fetchRow("SELECT * FROM properties_predefined WHERE `key` = ?", $this->model->getKey());
        $this->assignVariablesToModel($data);
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
        $this->db->delete("properties_predefined", $this->db->quoteInto("id = ?", $this->model->getId()));
    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     *
     * @return void
     */
    public function update() {
        try {
            $type = get_object_vars($this->model);

            foreach ($type as $key => $value) {
                if (in_array($key, $this->validColumns)) {
                    if(is_bool($value)) {
                        $value = (int)$value;
                    }
                    $data[$key] = $value;
                }
            }

            $this->db->update("properties_predefined", $data, $this->db->quoteInto("id = ?", $this->model->getId() ));
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {
        $this->db->insert("properties_predefined", array());

        $this->model->setId($this->db->lastInsertId());

        return $this->save();
    }
}
