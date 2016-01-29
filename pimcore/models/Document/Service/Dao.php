<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Document\Service;

use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Site;

class Dao extends Model\Dao\AbstractDao
{

    /**
     * @param Site $site
     * @param string $path
     * @return int
     */
    public function getDocumentIdByPrettyUrlInSite(Site $site, $path)
    {
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
    public function getDocumentIdFromHardlinkInSameSite(Site $site, Document $document)
    {
        return $this->db->fetchOne("SELECT documents.id FROM documents
            LEFT JOIN documents_hardlink ON documents.id = documents_hardlink.id
            WHERE documents_hardlink.sourceId = ? AND documents.path LIKE ?", array($document->getId(), $site->getRootPath() . "/%"));
    }
}
