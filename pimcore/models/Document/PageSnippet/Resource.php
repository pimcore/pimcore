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

abstract class Document_PageSnippet_Resource extends Document_Resource {

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
     * Get available versions fot the object and return an array of them
     *
     * @return array
     */
    public function getVersions() {
        $versionIds = $this->db->fetchCol("SELECT id FROM versions WHERE cid = ? AND ctype='document' ORDER BY `id` DESC", $this->model->getId());

        $versions = array();
        foreach ($versionIds as $versionId) {
            $versions[] = Version::getById($versionId);
        }

        $this->model->setVersions($versions);

        return $versions;
    }
    
    
    /**
     * Get latest available version, using $force always returns a version no matter if it is the same as the published one
     * @param bool $force
     * @return array
     */
    public function getLatestVersion($force = false) {
        $versionData = $this->db->fetchRow("SELECT id,date FROM versions WHERE cid = ? AND ctype='document' ORDER BY `id` DESC LIMIT 1", $this->model->getId());
        
        if(($versionData["id"] && $versionData["date"] > $this->model->getModificationDate()) || $force) {
            $version = Version::getById($versionData["id"]);
            return $version;  
        }
        return;
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
