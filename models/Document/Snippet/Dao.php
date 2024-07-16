<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Document\Snippet;

use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Model\Document\Snippet $model
 */
class Dao extends Model\Document\PageSnippet\Dao
{
    /**
     * Get the data for the object by the given id, or by the id which is set in the object
     *
     *
     * @throws Model\Exception\NotFoundException
     */
    public function getById(int $id = null): void
    {
        if ($id != null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchAssociative("SELECT documents.*, documents_snippet.*, tree_locks.locked FROM documents
            LEFT JOIN documents_snippet ON documents.id = documents_snippet.id
            LEFT JOIN tree_locks ON documents.id = tree_locks.id AND tree_locks.type = 'document'
                WHERE documents.id = ?", [$this->model->getId()]);

        if ($data) {
            $data['published'] = (bool)$data['published'];
            $this->assignVariablesToModel($data);
        } else {
            throw new Model\Exception\NotFoundException('Snippet with the ID ' . $this->model->getId() . " doesn't exists");
        }
    }

    public function create(): void
    {
        parent::create();

        $this->db->insert('documents_snippet', [
            'id' => $this->model->getId(),
        ]);
    }
}
