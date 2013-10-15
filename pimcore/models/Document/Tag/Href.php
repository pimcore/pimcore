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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Document_Tag_Href extends Document_Tag {

    /**
     * ID of the source object
     *
     * @var integer
     */
    public $id;

    /**
     * Type of the source object (document, asset, object)
     *
     * @var string
     */
    public $type;

    /**
     * Subtype of the source object (eg. page, link, video, news, ...)
     *
     * @var string
     */
    public $subtype;

    /**
     * Contains the source object
     *
     * @var mixed
     */
    public $element;

    /**
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType() {

        //TODO: getType != $type ... that might be dangerous
        return "href";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData() {
        return array(
            "id" => $this->id,
            "type" => $this->type,
            "subtype" => $this->subtype
        );
    }

    /**
     * Converts the data so it's suitable for the editmode
     *
     * @return mixed
     */
    public function getDataEditmode() {
	
		$this->setElement();
	
        if ($this->element instanceof Element_Interface) {
            return array(
                "id" => $this->id,
                "path" => $this->element->getFullPath(),
                "elementType" => $this->type,
                "subtype" => $this->subtype
            );
        }

        return null;
    }

    /**
     * @see Document_Tag_Interface::frontend
     * @return void
     */
    public function frontend() {

		$this->setElement();
	
        //don't give unpublished elements in frontend
        if (Document::doHideUnpublished() and !Element_Service::isPublished($this->element)) {
            return "";
        }

        if ($this->element instanceof Element_Interface) {
            return $this->element->getFullPath();
        }

        return "";
        // return "Please use method getElement() to retrieve the linked object";
        //return $this->getElement();
        // there is no direct output to the frontend
        // use ::getElement to get the linked element of the href
    }

    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {

        if (!empty($data)) {
            $data = Pimcore_Tool_Serialize::unserialize($data);
        }

        $this->id = $data["id"];
        $this->type = $data["type"];
        $this->subtype = $data["subtype"];

        $this->setElement();
        return $this;
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {

        $this->id = $data["id"];
        $this->type = $data["type"];
        $this->subtype = $data["subtype"];

        $this->setElement();
        return $this;
    }

    /**
     * Sets the element by the data stored for the object
     *
     * @return void
     */
    private function setElement() {
		if(!$this->element) {
			$this->element = Element_Service::getElementById($this->type, $this->id);
		}
        return $this;
    }

    /**
     * Returns one of them: Document, Object, Asset
     *
     * @return mixed
     */
    public function getElement() {

		$this->setElement();
	
        //don't give unpublished elements in frontend
        if (Document::doHideUnpublished() and !Element_Service::isPublished($this->element)) {
            return false;
        }

        return $this->element;
    }

    /**
     * Returns teh path of the linked element
     *
     * @return mixed
     */
    public function getFullPath() {

		$this->setElement();
	
        //don't give unpublished elements in frontend
        if (Document::doHideUnpublished() and !Element_Service::isPublished($this->element)) {
            return false;
        }
        if ($this->element instanceof Element_Interface) {
            return $this->element->getFullPath();
        }
        return;
    }

    /**
     * @return boolean
     */
    public function isEmpty() {
		
		$this->setElement();
	
        if ($this->getElement() instanceof Element_Interface) {
            return false;
        }
        return true;
    }


    /**
     * @return array
     */
    public function resolveDependencies() {

        $dependencies = array();
		$this->setElement();

        if ($this->element instanceof Element_Interface) {
            $elementType = Element_Service::getElementType($this->element);
            $key = $elementType . "_" . $this->element->getId();
            $dependencies[$key] = array(
                "id" => $this->element->getId(),
                "type" => $elementType
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
    public function getFromWebserviceImport($wsElement, $idMapper = null) {
        $data = $wsElement->value;
        if ($data->id !==null) {

            $this->type = $data->type;
            $this->subtype = $data->subtype;
            $this->id = $data->id;

            if (!is_numeric($this->id)) {
                throw new Exception("cannot get values from web service import - id is not valid");
            }

            if ($idMapper) {
                $this->id = $idMapper->getMappedId($this->type, $data->id);
            }


            if ($this->type == "asset") {
                $this->element = Asset::getById($this->id);
                if(!$this->element instanceof Asset){
                    if ($idMapper && $idMapper->ignoreMappingFailures()) {
                        $idMapper->recordMappingFailure("document", $this->getDocumentId(), $data->type, $data->id);
                    } else {
                        throw new Exception("cannot get values from web service import - referenced asset with id [ ".$data->id." ] is unknown");
                    }
                }
            } else if ($this->type == "document") {
                $this->element = Document::getById($this->id);
                if(!$this->element instanceof Document){
                    if ($idMapper && $idMapper->ignoreMappingFailures()) {
                        $idMapper->recordMappingFailure("document", $this->getDocumentId(), $data->type, $data->id);
                    } else {
                        throw new Exception("cannot get values from web service import - referenced document with id [ ".$data->id." ] is unknown");
                    }
                }
            } else if ($this->type == "object") {
                $this->element = Object_Abstract::getById($this->id);
                if(!$this->element instanceof Object_Abstract){
                    if ($idMapper && $idMapper->ignoreMappingFailures()) {
                        $idMapper->recordMappingFailure("document", $this->getDocumentId(), $data->type, $data->id);
                    } else {
                        throw new Exception("cannot get values from web service import - referenced object with id [ ".$data->id." ] is unknown");
                    }
                }
            } else {
                if ($idMapper && $idMapper->ignoreMappingFailures()) {
                    $idMapper->recordMappingFailure("document", $this->getDocumentId(), $data->type, $data->id);
                } else {
                    throw new Exception("cannot get values from web service import - type is not valid");
                }
            }
        }
    }


    /**
     * @return bool
     */
    public function checkValidity() {
        $sane = true;
        if($this->id){
            $el = Element_Service::getElementById($this->type, $this->id);
            if(!$el instanceof Element_Interface){
                $sane = false;
                Logger::notice("Detected insane relation, removing reference to non existent ".$this->type." with id [".$this->id."]");
                $this->id = null;
                $this->type = null;
                $this->subtype=null;
                $this->element=null;
            }
        }
        return $sane;
    
    }

    /**
     * @return array
     */
    public function __sleep() {

        $finalVars = array();
        $parentVars = parent::__sleep();
        $blockedVars = array("element");
        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }


    /**
     * this method is called by Document_Service::loadAllDocumentFields() to load all lazy loading fields
     * @return void
     */
    public function load () {
        if(!$this->element) {
            $this->setElement();
        }
    }

    /**
     * @param int $id
     * @return Document_Tag_Href
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int) $this->id;
    }

    /**
     * @param string $subtype
     * @return Document_Tag_Href
     */
    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     * @param array $idMapping
     * @return void
     */
    public function rewriteIds($idMapping) {
        if(array_key_exists($this->type, $idMapping) and array_key_exists($this->getId(), $idMapping[$this->type])) {
            $this->id = $idMapping[$this->type][$this->getId()];
        }
    }

}
