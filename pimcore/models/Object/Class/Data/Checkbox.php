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

class Object_Class_Data_Checkbox extends Object_Class_Data
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "checkbox";

    /**
     * @var bool
     */
    public $defaultValue = 0;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "tinyint(1)";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "tinyint(1)";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "boolean";


    /**
     * @return integer
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param integer $defaultValue
     * @return void
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = (int)$defaultValue;
    }


    /**
     * @see Object_Class_Data::getDataForResource
     * @param boolean $data
     * @param null|Object_Abstract $object
     * @return int
     */
    public function getDataForResource($data, $object = null)
    {

        if (is_bool($data)) {
            $data = (int)$data;
        }


        return $data;
    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param boolean $data
     * @return boolean
     */
    public function getDataFromResource($data)
    {
        return $data;
}

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param boolean $data
     * @param null|Object_Abstract $object
     * @return boolean
     */
    public function getDataForQueryResource($data, $object = null)
    {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param boolean $data
     * @param null|Object_Abstract $object
     * @return boolean
     */
    public function getDataForEditmode($data, $object = null)
    {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param boolean $data
     * @param null|Object_Abstract $object
     * @return boolean
     */
    public function getDataFromEditmode($data, $object = null)
    {
        if ($data === "false") {
            return false;
        }
        return (bool)$this->getDataFromResource($data);
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param boolean $data
     * @return boolean
     */
    public function getVersionPreview($data)
    {
        return $data;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {

        if (!$omitMandatoryCheck and $this->getMandatory() and $data === null) {
            throw new Exception("Empty mandatory field [ " . $this->getName() . " ]");
        }

        /* @todo seems to cause problems with old installations
        if(!is_bool($data) and $data !== 1 and $data !== 0){
        throw new Exception(get_class($this).": invalid data");
        }*/
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object_Abstract $object
     * @return string
     */
    public function getForCsvExport($object)
    {
        $key = $this->getName();
        $getter = "get" . ucfirst($key);
        return strval($object->$getter());
    }

    /**
     * fills object field data values from CSV Import String
     * @abstract
     * @param string $importValue
     * @param Object_Abstract $abstract
     * @return Object_Class_Data
     */
    public function getFromCsvImport($importValue)
    {
        return (bool)$importValue;
    }

    public function getForWebserviceExport($object)
    {
        $key = $this->getName();
        $getter = "get" . ucfirst($key);
        return (bool)$object->$getter();
    }

    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport($value)
    {
        return (bool)$value;
    }


}
