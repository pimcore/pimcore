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

class Object_Class_Data_Table extends Object_Class_Data {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "table";

    /**
     * @var integer
     */
    public $width;

    /**
     * @var integer
     */
    public $height;

    /**
     * @var integer
     */
    public $cols;


    /**
     * @var integer
     */
    public $rows;

    /**
     * Default data
     * @var integer
     */
    public $data;


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
    public $phpdocType = "array";

    /**
     * @return integer
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * @param integer $width
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
     * @param integer $height
     * @return void
     */
    public function setHeight($height) {
        $this->height = $this->getAsIntegerCast($height);
    }

    /**
     * @return integer
     */
    public function getCols() {
        return $this->cols;
    }

    /**
     * @param integer $cols
     * @return void
     */
    public function setCols($cols) {
        $this->cols = $this->getAsIntegerCast($cols);
    }

    /**
     * @return integer
     */
    public function getRows() {
        return $this->rows;
    }

    /**
     * @param integer $rows
     * @return void
     */
    public function setRows($rows) {
        $this->rows = $this->getAsIntegerCast($rows);
    }


    /**
     * @return integer
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @param integer $data
     * @return void
     */
    public function setData($data) {
        $this->data = $data;
    }


    /**
     * @see Object_Class_Data::getDataForResource
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForResource($data, $object = null) {
        return Pimcore_Tool_Serialize::serialize($data);
    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param string $data
     * @return string
     */
    public function getDataFromResource($data) {
        return Pimcore_Tool_Serialize::unserialize((string) $data);
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {

        if (!empty($data)) {
            $tmpLine = array();
            if (is_array($data)) {
                foreach ($data as $row) {
                    if (is_array($row)) {
                        $tmpLine[] = implode("|", $row);
                    }
                }
            }
            return implode("\n", $tmpLine);
        }
        return "";
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null) {
        return $data;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null) {
        
        // check for empty data
        $checkData = "";
        if (is_array($data)) {
            foreach ($data as $row) {
                if (is_array($row)) {
                    $checkData .= implode("", $row);
                }
            }
        }
        $checkData = str_replace(" ","",$checkData);
        
        if(empty($checkData)) {
            return null;
        }
        return $data;
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data) {
        return $data;
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

       if(!empty($data) and !is_array($data)){
            throw new Exception("invalid table data");
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
            return base64_encode(Pimcore_Tool_Serialize::serialize($object->$getter()));
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

       $value = Pimcore_Tool_Serialize::unserialize(base64_decode($importValue));
        Logger::log("table data");
        Logger::log($value);
        if (is_array($value)) {
            return $value;
        } else return null;

    }
}
