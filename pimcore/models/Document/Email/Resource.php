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

namespace Pimcore\Model\Document\Email;

use Pimcore\Model;

class Resource extends Model\Document\PageSnippet\Resource {

    /**
     * Contains the valid database columns
     *
     * @var array
     */
    protected $validColumnsPage = array();

    /**
     * Get the valid columns from the database
     *
     * @return void
     */
    public function init() {
        // document
        parent::init();

        // page
        $this->validColumnsPage = $this->getValidTableColumns("documents_email");
    }

    /**
     * Get the data for the object by the given id, or by the id which is set in the object
     *
     * @param integer $id
     * @return void
     */
    public function getById($id = null) {
        try {
            if ($id != null) {
                $this->model->setId($id);
            }

            $data = $this->db->fetchRow("SELECT documents.*, documents_email.*, tree_locks.locked FROM documents
                LEFT JOIN documents_email ON documents.id = documents_email.id
                LEFT JOIN tree_locks ON documents.id = tree_locks.id AND tree_locks.type = 'document'
                    WHERE documents.id = ?", $this->model->getId());

            if ($data["id"] > 0) {
                $this->assignVariablesToModel($data);
            }
            else {
                throw new \Exception("Email Document with the ID " . $this->model->getId() . " doesn't exists");
            }
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a new record for the object in the database
     *
     * @return void
     */
    public function create() {
        try {
            parent::create();

            $this->db->insert("documents_email", array(
                "id" => $this->model->getId()
            ));
        }
        catch (\Exception $e) {
            throw $e;
        }

    }

    /**
     * Updates the data in the object to the database
     *
     * @return void
     */
    public function update() {
        try {
            $this->model->setModificationDate(time());
            $document = get_object_vars($this->model);

            foreach ($document as $key => $value) {
                // check if the getter exists
                $getter = "get" . ucfirst($key);
                if(!method_exists($this->model,$getter)) {
                    continue;
                }

                // get the value from the getter
                if(in_array($key, $this->validColumnsDocument) || in_array($key, $this->validColumnsPage)) {
                    $value = $this->model->$getter();
                } else {
                    continue;
                }

                if(is_bool($value)) {
                    $value = (int)$value;
                }
                if (in_array($key, $this->validColumnsDocument)) {
                    $dataDocument[$key] = $value;
                }
                if (in_array($key, $this->validColumnsPage)) {
                    $dataPage[$key] = $value;
                }
            }

            $this->db->insertOrUpdate("documents", $dataDocument);
            $this->db->insertOrUpdate("documents_email", $dataPage);

            $this->updateLocks();
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Deletes the object (and data) from database
     *
     * @return void
     */
    public function delete() {
        try {
            $this->deleteAllProperties();

            $this->db->delete("documents_email", $this->db->quoteInto("id = ?", $this->model->getId()));
            //deleting log files
            $this->db->delete("email_log", $this->db->quoteInto("documentId = ?", $this->model->getId()));

            parent::delete();
        }
        catch (\Exception $e) {
            throw $e;
        }
    }
}
