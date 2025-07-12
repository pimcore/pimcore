<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Document\PageSnippet;

use Pimcore;
use Pimcore\Model;
use Pimcore\Model\Document;

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
    public function deleteAllEditables(): void
    {
        $this->db->delete('documents_editables', ['documentId' => $this->model->getId()]);
    }

    /**
     * Get all editables containing the content from the database
     *
     * @return Document\Editable[]
     */
    public function getEditables(): array
    {
        $editablesRaw = $this->db->fetchAllAssociative('SELECT * FROM documents_editables WHERE documentId = ?', [$this->model->getId()]);

        $editables = [];
        $loader = Pimcore::getContainer()->get(Document\Editable\Loader\EditableLoader::class);

        foreach ($editablesRaw as $editableRaw) {
            /** @var Document\Editable $editable */
            $editable = $loader->build($editableRaw['type']);
            $editable->setName($editableRaw['name']);
            $editable->setDocument($this->model);
            $editable->setDataFromResource($editableRaw['data']);

            $editables[$editableRaw['name']] = $editable;
        }

        return $editables;
    }
}
