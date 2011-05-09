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

class Asset_Image_Thumbnail_Resource extends Pimcore_Model_Resource_Abstract {

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
        $this->validColumns = $this->getValidTableColumns("thumbnails");
    }

    /**
     * Get the data for the object from database for the given id
     *
     * @param integer $id
     * @return void
     */
    public function getById($id) {
        $data = $this->db->fetchRow("SELECT * FROM thumbnails WHERE id = ?", $id);

        if (strlen($data["name"]) < 1) {
            $m = "there is no thumbnail for the requested id " . $id;
            Logger::error($m);
            throw new Exception($m);
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Get the data for the object from database for the given name
     *
     * @param string $name
     * @return void
     */
    public function getByName($name) {
        $data = $this->db->fetchRow("SELECT * FROM thumbnails WHERE name = ?", $name);

        if (strlen($data["name"]) < 1) {
            $m = "there is no thumbnail for the requested name " . $name;
            Logger::error($m);
            throw new Exception($m);
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     *
     * @return void
     */
    public function save() {
        if ($this->model->getId()) {
            return $this->update();
        }
        return $this->create();
    }

    /**
     * Create a new record for the object in database
     *
     * @return boolean
     */
    public function create() {
        $this->db->insert("thumbnails", array());
        $this->model->setId($this->db->lastInsertId());

        $this->save();
    }

    /**
     * Save changes to database, it's an good idea to use save() instead
     *
     * @return void
     */
    public function update() {
        $site = get_object_vars($this->model);

        foreach ($site as $key => $value) {
            if (in_array($key, $this->validColumns)) {

                if (is_array($value) || is_object($value)) {
                    $value = serialize($value);
                } else if(is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$key] = $value;
            }
        }

        $this->db->update("thumbnails", $data, $this->db->quoteInto("id = ?", $this->model->getId()));
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("thumbnails", $this->db->quoteInto("id = ?", $this->model->getId()));
    }

}
