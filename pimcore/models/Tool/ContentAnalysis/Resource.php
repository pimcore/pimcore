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
 * @package    Tool
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\ContentAnalysis;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {

    /**
     * @return string
     */
    public function getTotalIndexChangedItems() {
        return $this->db->fetchOne("SELECT COUNT(*)
        FROM content_index LEFT JOIN content_analysis ON content_analysis.id = content_index.id
        WHERE (content_index.lastUpdate - content_analysis.lastUpdate) > 86400 OR content_analysis.lastUpdate IS NULL");
    }

    /**
     * @return array
     */
    public function getIndexChangedItems() {
        return $this->db->fetchAll("SELECT content_index.*
        FROM content_index LEFT JOIN content_analysis ON content_analysis.id = content_index.id
        WHERE (content_index.lastUpdate - content_analysis.lastUpdate) > 86400 OR content_analysis.lastUpdate IS NULL
        ORDER BY content_index.lastUpdate ASC LIMIT 5");
    }

    /**
     * @param $data
     * @throws \Zend_Db_Adapter_Exception
     */
    public function update($data) {
        $exists = $this->db->fetchOne("SELECT id FROM content_analysis WHERE id = ?", $data["id"]);

        if($exists) {
            $this->db->update("content_analysis", $data, "id = '" . $data["id"] . "'");
        } else {
            $this->db->insert("content_analysis", $data);
        }

        $this->db->update("content_index", array("content" => ""), "id = '" . $data["id"] . "'");
    }
}

