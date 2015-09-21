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

namespace Pimcore\Model\Document\Snippet;

use Pimcore\Model;

class Resource extends Model\Document\PageSnippet\Resource {

    /**
     * Get the data for the object by the given id, or by the id which is set in the object
     *
     * @param integer $id
     * @throws \Exception
     */
    public function getById($id = null) {
        try {

            if ($id != null) {
                $this->model->setId($id);
            }

            $data = $this->db->fetchRow("SELECT documents.*, documents_snippet.*, tree_locks.locked FROM documents
                LEFT JOIN documents_snippet ON documents.id = documents_snippet.id
                LEFT JOIN tree_locks ON documents.id = tree_locks.id AND tree_locks.type = 'document'
                    WHERE documents.id = ?", $this->model->getId());

            if ($data["id"] > 0) {
                $this->assignVariablesToModel($data);
            } else {
                throw new \Exception("Snippet with the ID " . $this->model->getId() . " doesn't exists");
            }

            $this->assignVariablesToModel($data);

            //$this->getElements();
        }
        catch (\Exception $e) {
        }
    }

    /**
     * Create a new record for the object in the database
     *
     * @throws \Exception
     */
    public function create() {
        try {
            parent::create();

            $this->db->insert("documents_snippet", array(
                "id" => $this->model->getId()
            ));
        }
        catch (\Exception $e) {
            throw $e;
        }

    }

    /**
     * Deletes the object from database
     *
     * @throws \Exception
     */
    public function delete() {
        try {
            $this->db->delete("documents_snippet", $this->db->quoteInto("id = ?", $this->model->getId()));
            parent::delete();
        }
        catch (\Exception $e) {
            throw $e;
        }
    }


}
