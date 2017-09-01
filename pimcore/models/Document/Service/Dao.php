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

namespace Pimcore\Model\Document\Service;

use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Site;

/**
 * @property \Pimcore\Model\Document\Service $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @param Site $site
     * @param string $path
     *
     * @return int
     */
    public function getDocumentIdByPrettyUrlInSite(Site $site, $path)
    {
        return (int) $this->db->fetchOne(
            'SELECT documents.id FROM documents
            LEFT JOIN documents_page ON documents.id = documents_page.id
            WHERE documents.path LIKE ? AND documents_page.prettyUrl = ?',
        [$site->getRootPath() . '/%', rtrim($path, '/')]
        );
    }

    /**
     * @param Site $site
     * @param Document $document
     *
     * @return int
     */
    public function getDocumentIdFromHardlinkInSameSite(Site $site, Document $document)
    {
        return $this->db->fetchOne('SELECT documents.id FROM documents
            LEFT JOIN documents_hardlink ON documents.id = documents_hardlink.id
            WHERE documents_hardlink.sourceId = ? AND documents.path LIKE ?', [$document->getId(), $site->getRootPath() . '/%']);
    }

    /**
     * @param Document $document
     *
     * @return int
     */
    public function getTranslationSourceId(Document $document)
    {
        $sourceId = $this->db->fetchOne('SELECT sourceId FROM documents_translations WHERE id = ?', $document->getId());
        if (!$sourceId) {
            $sourceId = $document->getId();
        }

        return $sourceId;
    }

    /**
     * @param Document $document
     *
     * @return array
     */
    public function getTranslations(Document $document)
    {
        $sourceId = $this->getTranslationSourceId($document);

        $data = $this->db->fetchAll('SELECT id,language FROM documents_translations WHERE sourceId = ?', [$sourceId]);

        $translations = [];
        foreach ($data as $translation) {
            $translations[$translation['language']] = $translation['id'];
        }

        // add language from source document
        if (!empty($translations)) {
            $sourceDocument = Document::getById($sourceId);
            $translations[$sourceDocument->getProperty('language')] = $sourceDocument->getId();
        }

        return $translations;
    }

    /**
     * @param Document $document
     * @param Document $translation
     * @param $language
     */
    public function addTranslation(Document $document, Document $translation, $language = null)
    {
        $sourceId = $this->getTranslationSourceId($document);

        if (!$language) {
            $language = $translation->getProperty('language');
        }

        $this->db->insertOrUpdate('documents_translations', [
            'id' => $translation->getId(),
            'sourceId' => $sourceId,
            'language' => $language
        ]);
    }

    /**
     * @param Document $document
     */
    public function removeTranslation(Document $document)
    {
        $this->db->delete('documents_translations', ['id' => $document->getId()]);

        // if $document is a source-document, we need to move them over to a new document
        $newSourceId = $this->db->fetchOne('SELECT id FROM documents_translations WHERE sourceId = ?', $document->getId());
        if ($newSourceId) {
            $this->db->update('documents_translations', ['sourceId' => $newSourceId], ['sourceId' => $document->getId()]);
            $this->db->delete('documents_translations', ['id' => $newSourceId]);
        }
    }
}
