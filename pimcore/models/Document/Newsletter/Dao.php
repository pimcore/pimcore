<?php 
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Newsletter;

use Pimcore\Model;

/**
 * @property \Pimcore\Model\Document\Newsletter $model
 */
class Dao extends Model\Document\PageSnippet\Dao
{

    /**
     * Get the data for the object by the given id, or by the id which is set in the object
     *
     * @param integer $id
     * @return void
     */
    public function getById($id = null)
    {
        try {
            if ($id != null) {
                $this->model->setId($id);
            }

            $data = $this->db->fetchRow("SELECT documents.*, documents_newsletter.*, tree_locks.locked FROM documents
                LEFT JOIN documents_newsletter ON documents.id = documents_newsletter.id
                LEFT JOIN tree_locks ON documents.id = tree_locks.id AND tree_locks.type = 'document'
                    WHERE documents.id = ?", $this->model->getId());

            if ($data["id"] > 0) {
                $this->assignVariablesToModel($data);
            } else {
                throw new \Exception("Newsletter Document with the ID " . $this->model->getId() . " doesn't exists");
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a new record for the object in the database
     *
     * @return void
     */
    public function create()
    {
        try {
            parent::create();

            $this->db->insert("documents_newsletter", [
                "id" => $this->model->getId()
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Deletes the object (and data) from database
     *
     * @return void
     */
    public function delete()
    {
        try {
            $this->deleteAllProperties();

            $this->db->delete("documents_newsletter", $this->db->quoteInto("id = ?", $this->model->getId()));
            //deleting log files
            $this->db->delete("email_log", $this->db->quoteInto("documentId = ?", $this->model->getId()));

            parent::delete();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
