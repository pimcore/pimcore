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

class Document_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of objects (all are an instance of Document) for the given parameters an return them
     *
     * @return array
     */
    public function load() {
        $documents = array();
        $documentsData = $this->db->fetchAll("SELECT id,type FROM documents" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($documentsData as $documentData) {
            if($documentData["type"]) {
                if($doc = Document::getById($documentData["id"])) {
                    $documents[] = $doc;
                }
            }
        }

        $this->model->setDocuments($documents);
        return $documents;
    }

    /**
     * Loads a list of document ids for the specicifies parameters, returns an array of ids
     *
     * @return array
     */
    public function loadIdList() {
        $documentIds = $this->db->fetchCol("SELECT id FROM documents" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $documentIds;
    }

    protected function getCondition() {
        if ($cond = $this->model->getCondition()) {
            if (Document::doHideUnpublished() && !$this->model->getUnpublished()) {
                return " WHERE (" . $cond . ") AND published = 1";
            }
            return " WHERE " . $cond . " ";
        }
        else if (Document::doHideUnpublished() && !$this->model->getUnpublished()) {
            return " WHERE published = 1";
        }
        return "";
    }

    public function getCount() {
        $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM documents" . $this->getCondition() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        return $amount;
    }

    public function getTotalCount() {
        $amount = (int) $this->db->fetchOne("SELECT COUNT(*) as amount FROM documents" . $this->getCondition(), $this->model->getConditionVariables());
        return $amount;
    }
}
