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

namespace Pimcore\Model\Document\Page;

use Pimcore\Model;
use Pimcore\Model\Document\Targeting\TargetingDocumentDaoInterface;

/**
 * @property \Pimcore\Model\Document\Page $model
 */
class Dao extends Model\Document\PageSnippet\Dao implements TargetingDocumentDaoInterface
{
    use Model\Document\Targeting\TargetingDocumentDaoTrait;

    /**
     * Get the data for the object by the given id, or by the id which is set in the object
     *
     * @param int $id
     *
     * @throws \Exception
     */
    public function getById($id = null)
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchRow("SELECT documents.*, documents_page.*, tree_locks.locked FROM documents
            LEFT JOIN documents_page ON documents.id = documents_page.id
            LEFT JOIN tree_locks ON documents.id = tree_locks.id AND tree_locks.type = 'document'
                WHERE documents.id = ?", [$this->model->getId()]);

        if (!empty($data['id'])) {
            $data['metaData'] = @unserialize($data['metaData']);
            if (!is_array($data['metaData'])) {
                $data['metaData'] = [];
            }
            $this->assignVariablesToModel($data);
        } else {
            throw new \Exception('Page with the ID ' . $this->model->getId() . " doesn't exists");
        }
    }

    public function create()
    {
        parent::create();

        $this->db->insert('documents_page', [
            'id' => $this->model->getId(),
        ]);
    }

    /**
     * @throws \Exception
     */
    public function delete()
    {
        $this->deleteAllProperties();

        $this->db->delete('documents_page', ['id' => $this->model->getId()]);
        parent::delete();
    }
}
