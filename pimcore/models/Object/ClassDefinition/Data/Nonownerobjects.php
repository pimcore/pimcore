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
 * @package    Object\ClassDefinition
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;

class Nonownerobjects extends Model\Object\ClassDefinition\Data\Objects {

    /**
     * @var bool
     */
    public static $remoteOwner = true;


    /**
     * @return bool
     */
    public function isRemoteOwner(){
        return self::$remoteOwner;
    }

    /**
     * @var string
     */
    public $ownerClassName;


    /**
     * @var string
     */
    public $ownerFieldName;

    /**
     * NonOwnerObjects must be lazy loading!
     * @var boolean
     */
    public $lazyLoading = true;



    /**
     * @param array
     * @return void $classes
     */
    public function setClasses($classes) {
        //dummy, classes are set from owner classId
        return $this;
    }

    /**
     * @return boolean
     */
    public function getLazyLoading(){
        return true;
    }

    /**
     * @param  $lazyLoading
     * @return void
     */
    public function setLazyLoading($lazyLoading){
        //dummy, non owner objects must be lazy loading
        return $this;
    }


    /**
     * @param string $ownerClassName
     * @return void
     */
    public function setOwnerClassName($ownerClassName)
    {
        $this->ownerClassName = $ownerClassName;
        return $this;
    }

    /**
     * @return string
     */
    public function getOwnerClassName()
    {
        //fallback for legacy data
        if(empty($this->ownerClassName)){
            try {
                $class = Object\ClassDefinition::getById($this->ownerClassId);
                $this->ownerClassName =  $class->getName();
            } catch (\Exception $e) {
                \Logger::error($e->getMessage());
            }
        }
        return $this->ownerClassName;
    }

    /**
     * @return string
     */
    public function getOwnerFieldName(){
        return $this->ownerFieldName;
    }

    /**
     * @param  string $fieldName
     * @return void
     */
    public function setOwnerFieldName($fieldName){
        $this->ownerFieldName = $fieldName;
        return $this;
    }


    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @return array
     */
    public function getDataForResource($data, $object = null) {
        return null;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {
        return null;
    }

    /**
     *
     * Checks if an object is an allowed relation
     * @param Model\Object\AbstractObject $object
     * @return boolean
     */
    protected function allowObjectRelation($object) {
        //only relations of owner type are allowed
        $ownerClass = Object\ClassDefinition::getByName($this->getOwnerClassName());
        if($ownerClass->getId()>0 and $ownerClass->getId() == $object->getClassId()){
            $fd = $ownerClass->getFieldDefinition($this->getOwnerFieldName());
            if($fd instanceof Object\ClassDefinition\Data\Objects){
                return $fd->allowObjectRelation($object);
            }
        } else return false;
    }


    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){
           //TODO
        if(!$omitMandatoryCheck and $this->getMandatory() and empty($data)){
            throw new \Exception("Empty mandatory field [ ".$this->getName()." ]");
        }

        if (is_array($data)) {
            foreach ($data as $o) {
                $allowClass = $this->allowObjectRelation($o);
                if (!$allowClass or!($o instanceof Object\Concrete)) {
                    throw new \Exception("Invalid non owner object relation to object [".$o->getId()."]", null, null);
                }
            }
        }
    }

     /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Model\Object\AbstractObject $object
     * @return string
     */
    public function getForCsvExport($object) {
        return "";
    }

    /**
     * fills object field data values from CSV Import String
     * @abstract
     * @param string $importValue
     * @return Object\ClassDefinition\Data
     */
    public function getFromCsvImport($importValue) {
        return null;
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     * @return array
     */
    public function getCacheTags ($data, $tags = array()) {
        return $tags;
    }

    /**
     * @param mixed $data
     * @return array
     */
    public function resolveDependencies ($data) {
         return array();
    }

    /**
     * @param Object\AbstractObject $object
     * @return array|null
     */
    public function getForWebserviceExport ($object) {
        return null;
    }

    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($value, $object = null, $idMapper = null) {
        return null;
    }
}
