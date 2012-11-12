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

class Object_Class_Data_Multiselect extends Object_Class_Data {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "multiselect";

    /**
     * Available options to select
     *
     * @var array
     */
    public $options;
    
    /**
     * @var integer
     */
    public $width;    
    
    /**
     * @var integer
     */
    public $height; 
    
    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "text";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "text";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "string";

    /**
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * @param array $options
     * @return void
     */
    public function setOptions($options) {
        $this->options = $options;
    }
    
    /**
     * @return integer
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * @param array $width
     * @return void
     */
    public function setWidth($width) {
        $this->width = $this->getAsIntegerCast($width);
    }
    
    /**
     * @return integer
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * @param array $height
     * @return void
     */
    public function setHeight($height) {
        $this->height = $this->getAsIntegerCast($height);
    }

    /**
     * @see Object_Class_Data::getDataForResource
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForResource($data, $object = null) {
        if(is_array($data)) {
            return implode(",",$data);
        }
    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param string $data
     * @return string
     */
    public function getDataFromResource($data) {
        if($data) {
            return explode(",",$data);
        } else {
            return null;
        }
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {
        if(!empty($data) && is_array($data)) {
            return ",".implode(",",$data).",";
        }
        return;
    }


    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null) {
        if(is_array($data)) {
           return implode(",",$data); 
        }
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null) {
        if($data) {
            return explode(",",$data);
        } else {
            return null;
        }
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data) {
        if(is_array($data)) {
            return implode(",",$data);
        }
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        if(!$omitMandatoryCheck and $this->getMandatory() and empty($data)){
            throw new Exception("Empty mandatory field [ ".$this->getName()." ]");
        }

        if(!is_array($data) and !empty($data)){
            throw new Exception("Invalid multiselect data");
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object_Abstract $object
     * @return string
     */
    public function getForCsvExport($object) {
        $key = $this->getName();
        $getter = "get".ucfirst($key);
        if (is_array($object->$getter())) {
            return implode(",", $object->$getter());
        } else return null;
    }

    /**
     * fills object field data values from CSV Import String
     * @abstract
     * @param string $importValue
     * @param Object_Abstract $abstract
     * @return Object_Class_Data
     */
    public function getFromCsvImport($importValue) {
        return explode(",",$importValue);
    }


    /**
     * returns sql query statement to filter according to this data types value(s)
     * @param  $value
     * @param  $operator
     * @return string
     *
     */
    public function getFilterCondition($value,$operator){
        if ($operator == "=") {
            $value = "'%".$value."%'";
            return "`".$this->name."` LIKE ".$value." ";
        }
    }

}
