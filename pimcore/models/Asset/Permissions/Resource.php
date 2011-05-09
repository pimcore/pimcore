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
 * @package    Asset
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Asset_Permissions_Resource extends Pimcore_Model_Resource_Abstract {

    /**
     * List of valid columns in database table
     * This is used for automatic matching the objects properties to the database
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
        $this->validColumns = $this->getValidTableColumns("assets_permissions");
    }

    /**
     * Get the data for the object from database and assign it to the object (model)
     *
     * @param integer $id
     * @return void
     */
    public function getById($id = null) {
        if ($id) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow("SELECT * FROM assets_permissions WHERE id = ?", $this->model->getId());
        $this->assignVariablesToModel($data);
    }

    /**
     * Save current state of model/object to database
     * Checks if the object already exists, if not create a new one
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
     * Remove the object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("assets_permissions", $this->db->quoteInto("id = ?", $this->model->getId()));
    }

    /**
     * Update data from object to the database
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

            $this->db->update("assets_permissions", $data, $this->db->quoteInto("id = ?", $this->model->getId()));
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a the new object in database, an get the new assigned ID
     * Then call update() to save data
     *
     * @return void
     */
    public function create() {
        $this->db->insert("assets_permissions", array());

        $this->model->setId($this->db->lastInsertId());

        return $this->save();
    }
}
