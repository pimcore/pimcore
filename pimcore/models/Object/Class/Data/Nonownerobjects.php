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
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_Data_Nonownerobjects extends Object_Class_Data_Objects {

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
    }


    /**
     * @param string $ownerClassName
     * @return void
     */
    public function setOwnerClassName($ownerClassName)
    {
        $this->ownerClassName = $ownerClassName;
    }

    /**
     * @return string
     */
    public function getOwnerClassName()
    {
        //fallback for legacy data
        if(empty($this->ownerClassName)){
            $class = Object_Class::getById($this->ownerClassId);
            $this->ownerClassName =  $class->getName();
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
    }


    /**
     * @see Object_Class_Data::getDataForResource
     * @param array $data
     * @param null|Object_Abstract $object
     * @return array
     */
    public function getDataForResource($data, $object = null) {
        return null;
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param array $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {
        return null;
    }

    /**
     *
     * Checks if an object is an allowed relation
     * @param Object_Abstract $object
     * @return boolean
     */
    protected function allowObjectRelation($object) {
        //only relations of owner type are allowed
        $ownerClass = Object_Class::getByName($this->getOwnerClassName());
        if($ownerClass->getId()>0 and $ownerClass->getId() == $object->getO_classId()){
            $fd = $ownerClass->getFieldDefinition($this->getOwnerFieldName());
            if($fd instanceof Object_Class_Data_Objects){
                return $fd->allowObjectRelation($object);
            }
        } else return false;
    }


    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){
           //TODO
        if(!$omitMandatoryCheck and $this->getMandatory() and empty($data)){
            throw new Exception("Empty mandatory field [ ".$this->getName()." ]");
        }

        if (is_array($data)) {
            foreach ($data as $o) {
                $allowClass = $this->allowObjectRelation($o);
                if (!$allowClass or!($o instanceof Object_Concrete)) {
                    throw new Exception("Invalid non owner object relation to object [".$o->getId()."]", null, null);
                }
            }
        }
    }

     /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object_Abstract $object
     * @return string
     */
    public function getForCsvExport($object) {
        return "";
    }

    /**
     * fills object field data values from CSV Import String
     * @abstract
     * @param string $importValue
     * @param Object_Abstract $abstract
     * @return Object_Class_Data
     */
    public function getFromCsvImport($importValue) {
        return null;
    }
    

    /**
     * @param mixed $data
     * @param Object_Concrete $ownerObject
     * @param array $blockedTags
     */
    public function getCacheTags ($data, $ownerObject, $tags = array()) {
        return $tags;
    }

    /**
     * @param mixed $data
     */
    public function resolveDependencies ($data) {
         return array();
    }
    
    
    
    public function getForWebserviceExport ($object) {
        return null;
    }


    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport ($value) {
        return null;
    }




}
