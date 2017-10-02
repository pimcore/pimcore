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
 * @property \Pimcore\Model\Document\PageSnippet $model
 */
abstract class Dao extends Model\Document\Dao
{
    /**
     * Delete all elements containing the content (tags) from the database
     */
    public function deleteAllElements()
    {
        $this->db->delete('documents_elements', ['documentId' => $this->model->getId()]);
    }

    /**
     * Get all elements containing the content (tags) from the database
     *
     * @return Document\Tag[]
     */
    public function getElements()
    {
        $elementsRaw = $this->db->fetchAll('SELECT * FROM documents_elements WHERE documentId = ?', [$this->model->getId()]);

        $elements = [];
        $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.document.tag');

        foreach ($elementsRaw as $elementRaw) {
            /** @var Document\Tag $element */
            $element = $loader->build($elementRaw['type']);
            $element->setName($elementRaw['name']);
            $element->setDocumentId($this->model->getId());
            $element->setDataFromResource($elementRaw['data']);

            $elements[$elementRaw['name']] = $element;
            $this->model->setElement($elementRaw['name'], $element);
        }

        return $elements;
    }

    /**
     * Get available versions fot the object and return an array of them
     *
     * @return array
     */
    public function getVersions()
    {
        $versionIds = $this->db->fetchCol("SELECT id FROM versions WHERE cid = ? AND ctype='document' ORDER BY `id` DESC", $this->model->getId());

        $versions = [];
        foreach ($versionIds as $versionId) {
            $versions[] = Version::getById($versionId);
        }

        $this->model->setVersions($versions);

        return $versions;
    }

    /**
     * Get latest available version, using $force always returns a version no matter if it is the same as the published one
     *
     * @param bool $force
     *
     * @return array
     */
    public function getLatestVersion($force = false)
    {
        $versionData = $this->db->fetchRow("SELECT id,date FROM versions WHERE cid = ? AND ctype='document' ORDER BY `id` DESC LIMIT 1", $this->model->getId());

        if ($versionData && $versionData['id'] && ($versionData['date'] > $this->model->getModificationDate() || $force)) {
            $version = Version::getById($versionData['id']);

            return $version;
        }

        return;
    }

    /**
     * Delete the object from database
     *
     * @throws \Exception
     */
    public function delete()
    {
        try {
            parent::delete();
            $this->db->delete('documents_elements', ['documentId' => $this->model->getId()]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
