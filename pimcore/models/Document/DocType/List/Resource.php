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

class Document_DocType_List_Resource extends Pimcore_Model_List_Resource_Abstract {

    /**
     * Loads a list of document-types for the specicifies parameters, returns an array of Document_DocType elements
     *
     * @return array
     */
    public function load() {

        $docTypesData = $this->db->fetchCol("SELECT id FROM documents_doctypes" . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        $docTypes = array();
        foreach ($docTypesData as $docTypeData) {
            $docTypes[] = Document_DocType::getById($docTypeData);
        }

        $this->model->setDocTypes($docTypes);
        return $docTypes;
    }

}
