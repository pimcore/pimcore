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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Document\DocType;

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
        $this->validColumns = $this->getValidTableColumns("documents_doctypes");
    }

    /**
     * Get the data for the object from database for the given id
     * @param null $id
     * @throws \Exception
     */
    public function getById($id = null) {

        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow("SELECT * FROM documents_doctypes WHERE id = ?", $this->model->getId());
        if($data["id"]) {
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception("doc-type with id " . $this->model->getId() . " doesn't exist");
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
        $this->db->delete("documents_doctypes", $this->db->quoteInto("id = ?", $this->model->getId()));
    }

    /**
     * Save changes to database, it's a good idea to use save() instead
     *
     * @throw \Exception
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

            $this->db->update("documents_doctypes", $data, $this->db->quoteInto("id = ?", $this->model->getId()));
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

        $this->db->insert("documents_doctypes", array());

        $this->model->setId($this->db->lastInsertId());

        return $this->save();
    }
}
