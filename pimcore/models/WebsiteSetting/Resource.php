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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\WebsiteSetting;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {

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
        $this->validColumns = $this->getValidTableColumns("website_settings");
    }

    /**
     * @param null $id
     * @throws \Exception
     */
    public function getById($id = null) {

        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow("SELECT * FROM website_settings WHERE id = ?", $this->model->getId());
        $this->assignVariablesToModel($data);
        
        if($data["id"]) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("Website Setting with id: " . $this->model->getId() . " does not exist");
        }
    }

    /**
     * @param null $name
     * @param null $siteId
     * @throws \Exception
     */
    public function getByName($name = null, $siteId = null) {

        if ($name != null) {
            $this->model->setName($name);
        }
        $data = $this->db->fetchRow("SELECT id, data FROM website_settings WHERE name = ? AND (siteId IS NULL OR siteId = '' OR siteId = ?) ORDER BY siteId DESC", array($this->model->getName(), $siteId));
        
        if($data["id"]) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("Website Setting with name: " . $this->model->getName() . " does not exist");
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
        $this->db->delete("website_settings", $this->db->quoteInto("id = ?", $this->model->getId()));
        
        $this->model->clearDependentCache();
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
                    $data[$key] = $value;
                }
            }


            $this->db->update("website_settings", $data, $this->db->quoteInto("id = ?", $this->model->getId()));
        }
        catch (\Exception $e) {
            throw $e;
        }
        
        $this->model->clearDependentCache();
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

        $this->db->insert("website_settings", array("name" => $this->model->getName(), "siteId" => $this->model->getSiteId()));

        $this->model->setId($this->db->lastInsertId());

        return $this->save();
    }
}
