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
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model;

class Resource extends Model\Resource\AbstractResource {

    /**
     *
     */
    public function save() {

        $data = $this->model->getDataForResource();
        
        if (is_array($data) or is_object($data)) {
            $data = \Pimcore\Tool\Serialize::serialize($data);
        }

        $element = array(
            "data" => $data,
            "documentId" => $this->model->getDocumentId(),
            "name" => $this->model->getName(),
            "type" => $this->model->getType()
        );

        $this->db->insertOrUpdate("documents_elements", $element);
    }

    /**
     *
     */
    public function delete () {
        $this->db->delete("documents_elements", $this->db->quoteInto("documentId = ?", $this->model->getDocumentId()) . " AND " . $this->db->quoteInto("name = ?", $this->model->getName()));
    }

}
