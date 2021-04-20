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

namespace Pimcore\Model\Document\PageSnippet;

use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Version;

/**
 * @internal
 *
 * @property \Pimcore\Model\Document\PageSnippet $model
 */
abstract class Dao extends Model\Document\Dao
{
    use Model\Element\Traits\VersionDaoTrait;

    /**
     * Delete all editables containing the content from the database
     */
    public function deleteAllEditables()
    {
        $this->db->delete('documents_editables', ['documentId' => $this->model->getId()]);
    }

    /**
     * Get all editables containing the content from the database
     *
     * @return Document\Editable[]
     */
    public function getEditables()
    {
        $editablesRaw = $this->db->fetchAll('SELECT * FROM documents_editables WHERE documentId = ?', [$this->model->getId()]);

        $editables = [];
        $loader = \Pimcore::getContainer()->get(Document\Editable\Loader\EditableLoader::class);

        foreach ($editablesRaw as $editableRaw) {
            /** @var Document\Editable $editable */
            $editable = $loader->build($editableRaw['type']);
            $editable->setName($editableRaw['name']);
            $editable->setDocument($this->model);
            $editable->setDataFromResource($editableRaw['data']);

            $editables[$editableRaw['name']] = $editable;
        }

        $this->model->setEditables($editables);

        return $editables;
    }

    /**
     * Delete the object from database
     *
     * @throws \Exception
     */
    public function delete()
    {
        parent::delete();
        $this->db->delete('documents_editables', ['documentId' => $this->model->getId()]);
    }
}
