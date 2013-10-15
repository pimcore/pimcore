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

class Document_Tag_Renderlet extends Document_Tag {

    /**
     * Contains the ID of the linked object
     *
     * @var integer
     */
    public $id;

    /**
     * Contains the object
     *
     * @var Document | Asset | Object_Abstract
     */
    public $o;


    /**
     * Contains the type
     *
     * @var string
     */
    public $type;


    /**
     * Contains the subtype
     *
     * @var string
     */
    public $subtype;

    /**
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType() {
        return "renderlet";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData() {
        return array(
            "id" => $this->id,
            "type" => $this->getObjectType(),
            "subtype" => $this->subtype
        );
    }

    /**
     * Converts the data so it's suitable for the editmode
     *
     * @return mixed
     */
    public function getDataEditmode() {
        if ($this->o instanceof Element_Interface) {
            return array(
                "id" => $this->id,
                "type" => $this->getObjectType(),
                "subtype" => $this->subtype
            );
        }
        return null;
    }

    /**
     * @see Document_Tag_Interface::frontend
     * @return string
     */
    public function frontend() {

        if (!$this->options["controller"] && !$this->options["action"]) {
            $this->options["controller"] = Pimcore_Config::getSystemConfig()->documents->default_controller;
            $this->options["action"] = Pimcore_Config::getSystemConfig()->documents->default_action;
        }

        $document = null;
        if ($this->o instanceof Document) {
            $document = $this->o;
        }

        if(method_exists($this->o, "isPublished")) {
            if(!$this->o->isPublished()) {
                return "";
            }
        }

        if ($this->o instanceof Element_Interface) {

            $blockparams = array("action", "controller", "module", "template");

            $params = array(
                "template" => $this->options["template"],
                "object" => $this->o,
                "element" => $this->o,
                "document" => $document,
                "id" => $this->id,
                "type" => $this->type,
                "subtype" => $this->subtype,
                "pimcore_request_source" => "renderlet",
                "disableBlockClearing" => true
            );

            foreach ($this->options as $key => $value) {
                if (!array_key_exists($key, $params) && !in_array($key, $blockparams)) {
                    $params[$key] = $value;
                }
            }

            if ($this->getView() != null) {
                try {
                    return $this->getView()->action($this->options["action"], $this->options["controller"], $this->options["module"], $params);
                } catch (Exception $e) {
                    if(Pimcore::inDebugMode()) {
                        return "ERROR: " . $e->getMessage() . " (for details see debug.log)";
                    }
                    Logger::error($e);
                }
            }
        }
    }

    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {

        $data = Pimcore_Tool_Serialize::unserialize($data);

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
    public function setElement() {
        $this->o = Element_Service::getElementById($this->type, $this->id);
        return $this;
    }

    /**
     * @return array
     */
    public function resolveDependencies() {

        $this->load();

        $dependencies = array();

        if ($this->o instanceof Element_Interface) {

            $elementType = Element_Service::getElementType($this->o);
            $key = $elementType . "_" . $this->o->getId();

            $dependencies[$key] = array(
                "id" => $this->o->getId(),
                "type" => $elementType
            );
        }

        return $dependencies;
    }

    /**
     * get correct type of object as string
     * @param mixed $data
     * @return void
     */
    public function getObjectType($object = null) {

        $this->load();

        if (!$object) {
            $object = $this->o;
        }
        if($object instanceof Element_Interface){
            return Element_Service::getType($object);
        } else {
            return false;
        } 
    }


    /**
     * @return boolean
     */
    public function isEmpty () {

        $this->load();

        if($this->o instanceof Element_Interface) {
            return false;
        }
        return true;
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
            if (is_numeric($this->id)) {
                if ($idMapper) {
                    $id = $idMapper->getMappedId($this->type, $this->id);
                }

                if ($this->type == "asset") {
                    $this->o = Asset::getById($id);
                    if(!$this->o instanceof Asset){
                        if ($idMapper && $idMapper->ignoreMappingFailures()) {
                            $idMapper->recordMappingFailure($this->getDocumentId(),$this->type, $this->id);
                        } else {
                            throw new Exception("cannot get values from web service import - referenced asset with id [ ".$this->id." ] is unknown");
                        }
                    }
                } else if ($this->type == "document") {
                    $this->o = Document::getById($id);
                    if(!$this->o instanceof Document){
                        if ($idMapper && $idMapper->ignoreMappingFailures()) {
                            $idMapper->recordMappingFailure($this->getDocumentId(),$this->type, $this->id);
                        } else {
                            throw new Exception("cannot get values from web service import - referenced document with id [ ".$this->id." ] is unknown");
                        }
                    }
                } else if ($this->type == "object") {
                    $this->o = Object_Abstract::getById($id);
                    if(!$this->o instanceof Object_Abstract){
                        if ($idMapper && $idMapper->ignoreMappingFailures()) {
                            $idMapper->recordMappingFailure($this->getDocumentId(),$this->type, $this->id);
                        } else {
                            throw new Exception("cannot get values from web service import - referenced object with id [ ".$this->id." ] is unknown");
                        }
                    }
                } else {
                    p_r($this);
                    throw new Exception("cannot get values from web service import - type is not valid");
                }
            } else {
                throw new Exception("cannot get values from web service import - id is not valid");
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
                $this->o=null;
                $this->subtype=null;
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
        $blockedVars = array("o");
        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }


    /**
     * this method is called by Document_Service::loadAllDocumentFields() to load all lazy loading fields
     *
     * @return void
     */
    public function load () {
        if(!$this->o) {
            $this->setElement();
        }
    }

    /**
     * @param int $id
     * @return Document_Tag_Renderlet
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
     * @param \Asset|\Document|\Object_Abstract $o
     * @return Document_Tag_Renderlet
     */
    public function setO($o)
    {
        $this->o = $o;
        return $this;
    }

    /**
     * @return \Asset|\Document|\Object_Abstract
     */
    public function getO()
    {
        return $this->o;
    }

    /**
     * @param string $subtype
     * @return Document_Tag_Renderlet
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
        $type = (string) $this->type;
        if($type && array_key_exists($this->type, $idMapping) and array_key_exists($this->getId(), $idMapping[$this->type])) {
            $this->setId($idMapping[$this->type][$this->getId()]);
            $this->setO(null);
        }
    }
}
