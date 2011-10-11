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

class Document_Tag_Multihref extends Document_Tag implements Iterator{

    /**
     * @var array
     */
    public $elements = array();

     /**
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType() {
        return "multihref";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData() {
        return $this->elements;
    }

    /**
     * @see Document_Tag_Interface::getDataForResource
     * @return void
     */
    public function getDataForResource() {
        $return = array();

        if (is_array($this->elements) && count($this->elements) > 0) {
            foreach ($this->elements as $element) {
                $return[] = array(
                    "id" => $element->getId(),
                    "type" => Element_Service::getElementType($element)
                );
            }
        }

        return $return;
    }

    /**
     * Converts the data so it's suitable for the editmode
     * @return mixed
     */
    public function getDataEditmode() {

        $return = array();

        if (is_array($this->elements) && count($this->elements) > 0) {
            foreach ($this->elements as $element) {
                if ($element instanceof Object_Concrete) {
                    $return[] = array($element->geto_id(), $element->getFullPath(), "object", $element->geto_className());
                }
                else if ($element instanceof Object_Abstract) {
                    $return[] = array($element->geto_id(), $element->getFullPath(), "object", "folder");
                }
                else if ($element instanceof Asset) {
                    $return[] = array($element->getId(), $element->getFullPath(), "asset", $element->getType());
                }
                else if ($element instanceof Document) {
                    $return[] = array($element->getId(), $element->getFullPath(), "document", $element->getType());
                }
            }
        }

        return $return;
    }

    /**
     * @see Document_Tag_Interface::frontend
     * @return void
     */
    public function frontend() {

        $return = "";

        if (is_array($this->elements) && count($this->elements) > 0) {
            foreach ($this->elements as $element) {
                $return .= Element_Service::getElementType($element) . ": " . $element->getFullPath() . "<br />";
            }
        }

        return $return;
    }

    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        if($data = unserialize($data)) {
            $this->setDataFromEditmode($data);
        }
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $element) {

                if ($element["type"] == "object") {
                    $e = Object_Abstract::getById($element["id"]);
                }
                else if ($element["type"] == "asset") {
                    $e = Asset::getById($element["id"]);
                }
                else if ($element["type"] == "document") {
                    $e = Document::getById($element["id"]);
                }

                if ($e instanceof Element_Interface) {
                    $this->elements[] = $e;
                }
            }

        }

    }

    /**
     * @return Element_Interface[]
     */
    public function getElements() {
        return $this->elements;
    }

    /**
     * @return boolean
     */
    public function isEmpty() {
        return count($this->elements) > 0 ? true : false;
    }

    /**
     * @return array
     */
    public function resolveDependencies() {

        $dependencies = array();

        if (is_array($this->elements) && count($this->elements) > 0) {
            foreach ($this->elements as $element) {
                if ($element instanceof Document) {

                    $key = "document_" . $element->getId();

                    $dependencies[$key] = array(
                        "id" => $element->getId(),
                        "type" => "document"
                    );
                }
                else if ($element instanceof Asset) {

                    $key = "asset_" . $element->getId();

                    $dependencies[$key] = array(
                        "id" => $element->getId(),
                        "type" => "asset"
                    );
                }
                else if ($element instanceof Object_Abstract) {

                    $key = "object_" . $element->getO_Id();

                    $dependencies[$key] = array(
                        "id" => $element->getO_Id(),
                        "type" => "object"
                    );
                }
            }
        }

        return $dependencies;
    }

    public function getFromWebserviceImport($wsElement) {
        // currently unsupported
        return array();
    }


    /**
     * @return array
     */
    public function __sleep() {

        $this->__sleepData = $this->getDataForResource();
        $this->elements = array();

        return parent::__sleep();
    }

    /**
     * @return void
     */
    public function load () {
        if(isset($this->__sleepData)) {
            $this->setDataFromEditmode($this->__sleepData);
            unset($this->__sleepData);
        }
    }

    /**
     * Methods for Iterator
     */

    public function rewind() {
        reset($this->elements);
    }

    public function current() {
        $var = current($this->elements);
        return $var;
    }

    public function key() {
        $var = key($this->elements);
        return $var;
    }

    public function next() {
        $var = next($this->elements);
        return $var;
    }

    public function valid() {
        $var = $this->current() !== false;
        return $var;
    }
}
