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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Document_Service_Resource extends Pimcore_Model_Resource_Abstract {

    /**
     * @param Site $site
     * @param string $path
     * @return int
     */
    public function getDocumentIdByPrettyUrlInSite(Site $site, $path) {
        return (int) $this->db->fetchOne("SELECT documents.id FROM documents
            LEFT JOIN documents_page ON documents.id = documents_page.id
            WHERE documents.path LIKE ? AND documents_page.prettyUrl = ?",
        array($site->getRootPath() . "/%", rtrim($path, "/")));
    }

    /**
     * @param Site $site
     * @param Document $document
     * @return int
     */
    public function getDocumentIdFromHardlinkInSameSite(Site $site, Document $document) {
        return $this->db->fetchOne("SELECT documents.id FROM documents
            LEFT JOIN documents_hardlink ON documents.id = documents_hardlink.id
            WHERE documents_hardlink.sourceId = ? AND documents.path LIKE ?", array($document->getId(), $site->getRootPath() . "/%"));
    }
}
