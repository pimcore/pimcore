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
 * @package    Document
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Document_Permissions_Resource extends Pimcore_Model_Resource_Abstract {

    /**
     * Contains the valid database colums
     *
     * @var array
     */
    protected $validColumns = array();

    /**
     * Get the valid database columns from database
     *
     * @return void
     */
    public function init() {
        $this->validColumns = $this->getValidTableColumns("documents_permissions");
    }

   

    /**
     * Get the data for the object by the given id, or by the id which is set in the object
     *
     * @param integer $id
     * @return void
     */
    public function getById($id = null) {
        if ($id) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow("SELECT * FROM documents_permissions WHERE id = ?", $this->model->getId());
        $this->assignVariablesToModel($data);
    }


    /**
     * Save the current state of the object to the database, if the object doesn't exists yet create a new record
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
     * Deletes the object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("documents_permissions", $this->db->quoteInto("id = ?" , $this->model->getId()));
    }


    /**
     * Updates the object's data to the database, it's an good idea to use save() instead
     *
     * @return void
     */
    public function update() {
        try {
            $type = get_object_vars($this->model);

            foreach ($type as $key => $value) {
                if(is_bool($value)) {
                    $value = (int)$value;
                }
                if (in_array($key, $this->validColumns)) {
                    $data[$key] = $value;
                }
            }

            $this->db->update("documents_permissions", $data, $this->db->quoteInto("id = ?", $this->model->getId() ));
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a new record for the object in the database
     *
     * @return void
     */
    public function create() {
        $this->db->insert("documents_permissions", array());

        $this->model->setId($this->db->lastInsertId());

        return $this->save();
    }
}
