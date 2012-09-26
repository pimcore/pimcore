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
 * @package    Property
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Property extends Pimcore_Model_Abstract {

    /**
     * @var string
     */
    public $name;

    /**
     * @var mixed
     */
    public $data;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $ctype;

    /**
     * @var string
     */
    public $cpath;

    /**
     * @var integer
     */
    public $cid;

    /**
     * @var boolean
     */
    public $inheritable;

    /**
     * @var boolean
     */
    public $inherited = false;

    /**
     * @var string
     */
    public $config;

    /**
     * Takes data from editmode and convert it to internal objects
     *
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        // IMPORTANT: if you use this method be sure that the type of the property is already set

        if ($this->type == "document") {
            $this->data = Document::getByPath($data);
        }
        else if ($this->type == "asset") {
            $this->data = Asset::getByPath($data);
        }
        else if ($this->type == "object") {
            $this->data = Object_Abstract::getByPath($data);
        }
        else if ($this->type == "date") {
            $this->data = new Zend_Date($data);
        }
        else if ($this->type == "bool") {
            $this->data = false;
            if (!empty($data)) {
                $this->data = true;
            }
        }
        else {
            // plain text
            $this->data = $data;
        }
    }

    /**
     * Takes data from resource and convert it to internal objects
     *
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        // IMPORTANT: if you use this method be sure that the type of the property is already set

        if ($this->type == "document") {
            $this->data = Document::getById(intval($data));
        }
        else if ($this->type == "asset") {
            $this->data = Asset::getById(intval($data));
        }
        else if ($this->type == "object") {
            $this->data = Object_Abstract::getById(intval($data));
        }
        else if ($this->type == "date") {
            $this->data = Pimcore_Tool_Serialize::unserialize($data);
        }
        else if ($this->type == "bool") {
            $this->data = false;
            if (!empty($data)) {
                $this->data = true;
            }
        }
        else {
            // plain text
            $this->data = $data;
        }
    }

    /**
     * get the config from an predefined property-set (eg. select)
     *
     * @return void
     */
    public function setConfigFromPredefined() {
        if ($this->getName() && $this->getType()) {
            $predefined = Property_Predefined::getByKey($this->getName());

            if ($predefined->getType() == $this->getType()) {
                $this->config = $predefined->getConfig();
            }
        }
    }

    /**
     * @return integer
     */
    public function getCid() {
        return $this->cid;
    }

    /**
     * @return string
     */
    public function getCtype() {
        return $this->ctype;
    }

    /**
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param integer $cid
     * @return void
     */
    public function setCid($cid) {
        $this->cid = (int) $cid;
    }

    /**
     * @param string $ctype
     * @return void
     */
    public function setCtype($ctype) {
        $this->ctype = $ctype;
    }

    /**
     * @param mixed $data
     * @return void
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
        $this->setConfigFromPredefined();
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType($type) {
        $this->type = $type;
        $this->setConfigFromPredefined();
    }

    /**
     * @return string
     */
    public function getCpath() {
        return $this->cpath;
    }

    /**
     * @return boolean
     */
    public function getInherited() {
        return $this->inherited;
    }

    /**
     * Alias for getInherited()
     *
     * @return boolean
     */
    public function isInherited() {
        return $this->getInherited();
    }

    /**
     * @param string $cpath
     * @return void
     */
    public function setCpath($cpath) {
        $this->cpath = $cpath;
    }

    /**
     * @param boolean $inherited
     * @return void
     */
    public function setInherited($inherited) {
        $this->inherited = (bool) $inherited;
    }

    /**
     * @return boolean
     */
    public function getInheritable() {
        return $this->inheritable;
    }

    /**
     * @param boolean $inheritable
     * @return void
     */
    public function setInheritable($inheritable) {
        $this->inheritable = (bool) $inheritable;
    }
    
    /**
     * @return array
     */
    public function resolveDependencies () {
        
        $dependencies = array();
        
        if ($this->getType() == "document") {
            if ($this->getData() instanceof Document) {
                $key = "document_" . $this->getData()->getId();

                $dependencies[$key] = array(
                    "id" => $this->getData()->getId(),
                    "type" => "document"
                );
            }
        }
        if ($this->getType() == "asset") {
            if ($this->getData() instanceof Asset) {
                $key = "asset_" . $this->getData()->getId();

                $dependencies[$key] = array(
                    "id" => $this->getData()->getId(),
                    "type" => "asset"
                );
            }
        }
        if ($this->getType() == "object") {
            if ($this->getData() instanceof Object_Abstract) {
                $key = "object_" . $this->getData()->getO_Id();

                $dependencies[$key] = array(
                    "id" => $this->getData()->getO_Id(),
                    "type" => "object"
                );
            }
        }
        
        return $dependencies;
    }
}
