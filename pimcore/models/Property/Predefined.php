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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Property;

use Pimcore\Model;

class Predefined extends Model\AbstractModel {

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
     * @var integer
     */
    public $creationDate;

    /**
     * @var integer
     */
    public $modificationDate;



    /**
     * @param integer $id
     * @return self
     */
    public static function getById($id) {
        $property = new self();
        $property->setId($id);
        $property->getResource()->getById();

        return $property;
    }

    /**
     * @param string $key
     * @return self
     */
    public static function getByKey($key) {

        $cacheKey = "property_predefined_" . $key;

        try {
            $property = \Zend_Registry::get($cacheKey);
            if(!$property) {
                throw new \Exception("Predefined property in registry is null");
            }
        } catch (\Exception $e) {
            $property = new self();
            $property->setKey($key);
            $property->getResource()->getByKey();

            \Zend_Registry::set($cacheKey, $property);
        }

        return $property;
    }

    /**
     * @return self
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
        return $this;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * @param string $data
     * @return void
     */
    public function setData($data) {
        $this->data = $data;
        return $this;
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
        return $this;
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
        return $this;
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
        return $this;
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
        return $this;
    }

    /**
     * @param string $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param int $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param int $modificationDate
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }


}
