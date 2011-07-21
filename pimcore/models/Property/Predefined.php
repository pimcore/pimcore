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

class Property_Predefined extends Pimcore_Model_Abstract {

    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $data;

    /**
     * @var string
     */
    public $config;

    /**
     * @var string
     */
    public $ctype;

    /**
     * @var bool
     */
    public $inheritable = false;


    /**
     * @param integer $id
     * @return Property_Predefined
     */
    public static function getById($id) {
        $property = new self();
        $property->setId($id);
        $property->getResource()->getById();

        return $property;
    }

    /**
     * @param string $key
     * @return Property_Predefined
     */
    public static function getByKey($key) {
        $property = new self();
        $property->setKey($key);
        $property->getResource()->getByKey();

        return $property;
    }

    /**
     * @return Property_Predefined
     */
    public static function create() {
        $type = new self();
        $type->save();

        return $type;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
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
     * @return string
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @param string $key
     * @return void
     */
    public function setKey($key) {
        $this->key = $key;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @param string $data
     * @return void
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
    }

    /**
     * @return string
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @param string $config
     * @return void
     */
    public function setConfig($config) {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getCtype() {
        return $this->ctype;
    }

    /**
     * @param string $ctype
     * @return void
     */
    public function setCtype($ctype) {
        $this->ctype = $ctype;
    }
    
    /**
     * @return string
     */
    public function getInheritable() {
        return (bool) $this->inheritable;
    }

    /**
     * @param string $inheritable
     * @return void
     */
    public function setInheritable($inheritable) {
        $this->inheritable = (bool) $inheritable;
    }

    /**
     * @param string $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
