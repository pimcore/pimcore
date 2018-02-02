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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\PrintAbstract;

use Pimcore\Model\Document;

/**
 * @property \Pimcore\Model\Document\PrintAbstract $model
 */
class Dao extends Document\PageSnippet\Dao
{
    /**
     * Contains the valid database columns
     *
     * @var array
     */
    protected $validColumnsPage = [];

    /**
     * Get the valid columns from the database
     */
    public function init()
    {
        // page
        $this->validColumnsPage = $this->getValidTableColumns('documents_printpage');
    }

    /**
     * Get the data for the object by the given id, or by the id which is set in the object
     *
     * @param int $id
     *
     * @throws \Exception
     */
    public function getById($id = null)
    {
        try {
            if ($id != null) {
                $this->model->setId($id);
            }

            $data = $this->db->fetchRow("SELECT documents.*, documents_printpage.*, tree_locks.locked FROM documents
                LEFT JOIN documents_printpage ON documents.id = documents_printpage.id
                LEFT JOIN tree_locks ON documents.id = tree_locks.id AND tree_locks.type = 'document'
                    WHERE documents.id = ?", $this->model->getId());

            if ($data['id'] > 0) {
                $this->assignVariablesToModel($data);
            } else {
                throw new \Exception('Print Document with the ID ' . $this->model->getId() . " doesn't exists");
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a new record for the object in the database
     *
     * @throws \Exception
     */
    public function create()
    {
        try {
            parent::create();

            $this->db->insert('documents_printpage', [
                'id' => $this->model->getId()
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Updates the data in the object to the database
     *
     * @throws \Exception
     */
    public function update()
    {
        try {
            $this->model->setModificationDate(time());
            $document = get_object_vars($this->model);

            foreach ($document as $key => $value) {
                // check if the getter exists
                $getter = 'get' . ucfirst($key);
                if (!method_exists($this->model, $getter)) {
                    continue;
                }

                // get the value from the getter
                if (in_array($key, $this->getValidTableColumns('documents')) || in_array($key, $this->validColumnsPage)) {
                    $value = $this->model->$getter();
                } else {
                    continue;
                }

                if (is_bool($value)) {
                    $value = (int)$value;
                }
                if (in_array($key, $this->getValidTableColumns('documents'))) {
                    $dataDocument[$key] = $value;
                }
                if (in_array($key, $this->validColumnsPage)) {
                    $dataPage[$key] = $value;
                }
            }

            $this->db->insertOrUpdate('documents', $dataDocument);
            $this->db->insertOrUpdate('documents_printpage', $dataPage);

            $this->updateLocks();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Deletes the object (and data) from database
     *
     * @throws \Exception
     */
    public function delete()
    {
        try {
            $this->deleteAllProperties();

            $this->db->delete('documents_page', ['id' => $this->model->getId()]);
            $this->db->delete('documents_printpage', ['id' => $this->model->getId()]);
            parent::delete();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
