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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

abstract class Document_PageSnippet_Resource extends Document_Versionable_Resource {

    /**
     * Delete all elements containing the content (tags) from the database
     *
     * @return void
     */
    public function deleteAllElements() {
        $this->db->delete("documents_elements", $this->db->quoteInto("documentId = ?", $this->model->getId() ));
    }

    /**
     * Get all elements containing the content (tags) from the database
     *
     * @return void
     */
    public function getElements() {
        $elementsRaw = $this->db->fetchAll("SELECT * FROM documents_elements WHERE documentId = ?", $this->model->getId());

        $elements = array();

        foreach ($elementsRaw as $elementRaw) {
            $class = "Document_Tag_" . ucfirst($elementRaw["type"]);
            $element = new $class();
            $element->setDataFromResource($elementRaw["data"]);
            $element->setName($elementRaw["name"]);
            $element->setDocumentId($this->model->getId());

            $elements[$elementRaw["name"]] = $element;
            $this->model->setElement($elementRaw["name"], $element);
        }
        return $elements;
    }    

    /**
     * Delete the object from database
     *
     * @return void
     */
    public function delete() {
        try {
            parent::delete();
            $this->db->delete("documents_elements", $this->db->quoteInto("documentId = ?", $this->model->getId() ));
        }
        catch (Exception $e) {
            throw $e;
        }
    }

}
