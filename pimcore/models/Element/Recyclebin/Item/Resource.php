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
 * @package    Element
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Element_Recyclebin_Item_Resource extends Pimcore_Model_Resource_Abstract {

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
        $this->validColumns = $this->getValidTableColumns("recyclebin");
    }

    /**
     * Get the data for the object from database for the given id
     *
     * @param integer $id
     * @return void
     */
    public function getById($id) {
        $data = $this->db->fetchRow("SELECT * FROM recyclebin WHERE id = ?", $id);

        if (!$data["id"]) {
            throw new Exception("Recyclebin item with id " . $id . " not found");
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {

        $version = get_object_vars($this->model);

        foreach ($version as $key => $value) {
            if (in_array($key, $this->validColumns)) {
                $data[$key] = $value;
            }
        }

        try {
            $this->db->insert("recyclebin", $data);
            $this->model->setId($this->db->lastInsertId());
        }
        catch (Exception $e) {
            Logger::érror($e);
        }

        return true;
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("recyclebin", $this->db->quoteInto("id = ?", $this->model->getId()));
    }
}
