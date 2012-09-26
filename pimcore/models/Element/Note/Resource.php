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

class Element_Note_Resource extends Pimcore_Model_Resource_Abstract {

    /**
     * Contains all valid columns in the database table
     * @var array
     */
    protected $validColumns = array();

    /**
     * Contains all valid columns in the database table
     * @var array
     */
    protected $validColumnsData = array();

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
        $this->validColumns = $this->getValidTableColumns("notes");
        $this->validColumnsData = $this->getValidTableColumns("notes_data");
    }

    /**
     * Get the data for the object from database for the given id
     * @param integer $id
     * @return void
     */
    public function getById($id) {
        $data = $this->db->fetchRow("SELECT * FROM notes WHERE id = ?", $id);

        if (!$data["id"]) {
            throw new Exception("Note item with id " . $id . " not found");
        }
        $this->assignVariablesToModel($data);

        // get key-value data
        $keyValues = $this->db->fetchAll("SELECT * FROM notes_data WHERE id = ?", $id);
        $preparedData = array();

        foreach ($keyValues as $keyValue) {

            $data = $keyValue["data"];
            $type = $keyValue["type"];
            $name = $keyValue["name"];

            if($type == "document") {
                if($data) {
                    $data = Document::getById($data);
                }
            } else if ($type == "asset") {
                if($data) {
                    $data = Asset::getById($data);
                }
            } else if ($type == "object") {
                if($data) {
                    $data = Object_Abstract::getById($data);
                }
            } else if ($type == "date") {
                if($data > 0) {
                    $data = new Zend_Date($data);
                }
            } else if ($type == "bool") {
                $data = (bool) $data;
            }

            $preparedData[$name] = array(
                "data" => $data,
                "type" => $type
            );
        }

        $this->model->setData($preparedData);
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {

        $version = get_object_vars($this->model);

        // save main table
        foreach ($version as $key => $value) {
            if (in_array($key, $this->validColumns)) {
                $data[$key] = $value;
            }
        }

        try {
            $this->db->insert("notes", $data);
            $this->model->setId($this->db->lastInsertId());
        }
        catch (Exception $e) {
            $this->db->update("notes", $data, $this->db->quoteInto("id = ?", $this->model->getId() ));
        }

        // save data table
        $this->deleteData();
        foreach ($this->model->getData() as $name => $meta) {

            $data = $meta["data"];
            $type = $meta["type"];

            if($type == "document") {
                if($data instanceof Document) {
                    $data = $data->getId();
                }
            } else if ($type == "asset") {
                if($data instanceof Asset) {
                    $data = $data->getId();
                }
            } else if ($type == "object") {
                if($data instanceof Object_Abstract) {
                    $data = $data->getId();
                }
            } else if ($type == "date") {
                if($data instanceof Zend_Date) {
                    $data = $data->getTimestamp();
                }
            } else if ($type == "bool") {
                $data = (bool) $data;
            }

            $this->db->insert("notes_data", array(
                "id" => $this->model->getId(),
                "name" => $name,
                "type" => $type,
                "data" => $data
            ));
        }

        return true;
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("notes", $this->db->quoteInto("id = ?", $this->model->getId() ));
        $this->deleteData();
    }

    protected function deleteData () {
        $this->db->delete("notes_data", $this->db->quoteInto("id = ?", $this->model->getId() ));
    }

}
