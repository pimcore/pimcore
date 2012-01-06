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
 * @package    Site
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Site_Resource extends Pimcore_Model_Resource_Abstract {

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
        $this->validColumns = $this->getValidTableColumns("sites");
    }

    /**
     * Get the data for the object from database for the given id
     *
     * @param integer $id
     * @return void
     */
    public function getById($id) {
        $data = $this->db->fetchRow("SELECT * FROM sites WHERE id = ?", $id);
        if (!$data["id"]) {
            throw new Exception("there is no site for the requested id");
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Get the data for the object from database for the given root-id (which is a document-id)
     *
     * @param integer $id
     * @return void
     */
    public function getByRootId($id) {
        $data = $this->db->fetchRow("SELECT * FROM sites WHERE rootId = ?", $id);
        if (!$data["id"]) {
            throw new Exception("there is no site for the requested rootId");
        }
        $this->assignVariablesToModel($data);
    }

    /**
     * Get the data for the object from database for the given domain
     *
     * @param string $domain
     * @return void
     */
    public function getByDomain($domain) {
        $data = $this->db->fetchRow("SELECT * FROM sites WHERE domains LIKE ?", "%\"" . $domain . "\"%");
        if (!$data["id"]) {
            throw new Exception("there is no site for the requested domain");
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
        $this->db->insert("sites", array("rootId" => $this->model->getRootId()));
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
                    $value = Pimcore_Tool_Serialize::serialize($value);
                }
                $data[$key] = $value;
            }
        }

        $this->db->update("sites", $data, $this->db->quoteInto("id = ?", $this->model->getId()));
        
        $this->model->clearDependedCache();
    }

    /**
     * Deletes object from database
     *
     * @return void
     */
    public function delete() {
        $this->db->delete("sites", $this->db->quoteInto("id = ?", $this->model->getId()));
        
        $this->model->clearDependedCache();
    }
}
