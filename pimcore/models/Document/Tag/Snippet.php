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

class Document_Tag_Snippet extends Document_Tag {

    /**
     * Contains the ID of the linked snippet
     *
     * @var integer
     */
    public $id;

    /**
     * Contains the object for the snippet
     *
     * @var Document_Snippet
     */
    public $snippet;


    /**
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType() {
        return "snippet";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData() {
        return $this->id;
    }

    /**
     * Converts the data so it's suitable for the editmode
     *
     * @return mixed
     */
    public function getDataEditmode() {
        if ($this->snippet instanceof Document_Snippet) {
            return array(
                "id" => $this->id,
                "path" => $this->snippet->getFullPath()
            );
        }
        return null;
    }

    /**
     * @see Document_Tag_Interface::frontend
     * @return string
     */
    public function frontend() {
        if ($this->getView() instanceof Zend_View) {
            try {
                if ($this->snippet instanceof Document_Snippet) {
                    $params = $this->options;
                    $params["document"] = $this->snippet;

                    if ($this->snippet->isPublished()) {
                        return $this->getView()->action($this->snippet->getAction(), $this->snippet->getController(), null, $params);
                    }
                    return "";
                }
            }
            catch (Exception $e) {
                Logger::warning($e);
            }
        } else {
            return null;
        }
    }

    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        if (intval($data) > 0) {
            $this->id = $data;
            $this->snippet = Document_Snippet::getById($this->id);
        }
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        if (intval($data) > 0) {
            $this->id = $data;
            $this->snippet = Document_Snippet::getById($this->id);
        }
    }
    
    /**
     * @return boolean
     */
    public function isEmpty() {
        if($this->snippet instanceof Document_Snippet) {
            return false;
        }
        return true;
    }
    
    
    /**
     * This is a dummy and is mostly implemented by relation types
     * @param $ownerDocument
     * @param array $blockedTags
     */
    public function getCacheTags ($ownerDocument, $blockedTags = array()) {
        
        $tags = array();
        
        if ($this->snippet instanceof Document_Snippet) {
            if ($this->snippet->getId() != $ownerDocument->getId() and !in_array($this->snippet->getCacheTag(), $blockedTags)) {
                $tags = array_merge($tags, $this->snippet->getCacheTags($blockedTags));
            }
        }
        
        return $tags;
    }
    
    /**
     * @return array
     */
    public function resolveDependencies () {
        
        $dependencies = array();
        
        if ($this->snippet instanceof Document_Snippet) {

            $key = "document_" . $this->snippet->getId();

            $dependencies[$key] = array(
                "id" => $this->snippet->getId(),
                "type" => "document"
            );
        }
        
        return $dependencies;
    }


    /**
     * Receives a Webservice_Data_Document_Element from webservice import and fill the current tag's data
     *
     * @abstract
     * @param  Webservice_Data_Document_Element $data
     * @return void
     */
    public function getFromWebserviceImport($wsElement) {
        $data = $wsElement->value;
        if ($data->id !==null) {

            $this->id = $data->id;
            if (is_numeric($this->id)) {
                $this->snippet = Document_Snippet::getById($this->id);
                if (!$this->snippet instanceof Document_Snippet) {
                    throw new Exception(get_class($this) . ": cannot get values from web service import - referenced snippet with id [ " . $this->id . " ] is unknown");
                }
            } else {
                throw new Exception(get_class($this) . ": cannot get values from web service import - id is not valid");
            }


        }


    }
}
