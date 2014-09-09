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

class Object_Class_Data_IndexFieldSelection extends Object_Class_Data {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "indexFieldSelection";

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = array(
        "tenant" => "varchar(100)",
        "field" => "varchar(200)",
        "preSelect" => "text"
    );

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = array(
        "tenant" => "varchar(100)",
        "field" => "varchar(200)",
        "preSelect" => "text"
    );

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "Object_Data_IndexFieldSelection";

    public $considerTenants = false;
    public $multiPreSelect = false;
    public $filterGroups = "";
    public $predefinedPreSelectOptions = array();


    public function __construct() {

//        $indexColumns = array();
//        try {
//            $indexService = OnlineShop_Framework_Factory::getInstance()->getIndexService();
//            $indexColumns = $indexService->getIndexColumns(true);
//        } catch (Exception $e) {
//            Logger::err($e);
//        }

        /*$options = array();

        foreach ($indexColumns as $c) {
            $options[] = array(
                "key" => $c,
                "value" => $c
            );
        }

//        p_r($options); die();
        $this->setOptions($options);*/
    }

    public function setConsiderTenants($considerTenants) {
        $this->considerTenants = $considerTenants;
    }

    public function getConsiderTenants() {
        return $this->considerTenants;
    }

    public function setFilterGroups($filterGroups)
    {
        $this->filterGroups = $filterGroups;
    }

    public function getFilterGroups()
    {
        return $this->filterGroups;
    }

    /**
     * @param boolean $multiPreSelect
     */
    public function setMultiPreSelect($multiPreSelect)
    {
        $this->multiPreSelect = $multiPreSelect;
    }

    /**
     * @return boolean
     */
    public function getMultiPreSelect()
    {
        return $this->multiPreSelect;
    }

    /**
     * @param array $predefinedPreSelectOptions
     */
    public function setPredefinedPreSelectOptions($predefinedPreSelectOptions)
    {
        $this->predefinedPreSelectOptions = $predefinedPreSelectOptions;
    }

    /**
     * @return array
     */
    public function getPredefinedPreSelectOptions()
    {
        return $this->predefinedPreSelectOptions;
    }


    /**
     * @see Object_Class_Data::getDataForResource
     * @param float $data
     * @return float
     */
    public function getDataForResource($data, $object = null) {
        if ($data instanceof Object_Data_IndexFieldSelection) {
            return array(
                $this->getName() . "__tenant" => $data->getTenant(),
                $this->getName() . "__field" => $data->getField(),
                $this->getName() . "__preSelect" => $data->getPreSelect()
            );
        }
        return array(
            $this->getName() . "__tenant" => null,
            $this->getName() . "__field" => null,
            $this->getName() . "__preSelect" => null
        );

    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param float $data
     * @return float
     */
    public function getDataFromResource($data) {
        if($data[$this->getName() . "__field"]) {
            return new Object_Data_IndexFieldSelection($data[$this->getName() . "__tenant"], $data[$this->getName() . "__field"], $data[$this->getName() . "__preSelect"]);
        }
        return;
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param float $data
     * @return float
     */
    public function getDataForQueryResource($data, $object = null) {
        return $this->getDataForResource($data);
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param float $data
     * @return float
     */
    public function getDataForEditmode($data, $object = null) {
        if ($data instanceof Object_Data_IndexFieldSelection) {
            return array(
                "tenant" => $data->getTenant(),
                "field" => $data->getField(),
                "preSelect" => $data->getPreSelect()
            );
        }

        return;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param float $data
     * @return float
     */
    public function getDataFromEditmode($data, $object = null) {
        if($data["field"]) {
            return new Object_Data_IndexFieldSelection($data["tenant"], $data["field"], $data["preSelect"]);
        }
        return;
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param float $data
     * @return float
     */
    public function getVersionPreview($data) {
        if($data instanceof Object_Data_IndexFieldSelection) {
            return $data->getTenant() . " " . $data->getField() . " " . $data->getPreSelect();
        }
        return "";
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false){

        if(!$omitMandatoryCheck && $this->getMandatory() &&
            ($data === NULL || $data->getField() === NULL)){
            throw new Exception(get_class($this).": Empty mandatory field [ ".$this->getName()." ]");
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
        if($object->$getter() instanceof Object_Data_IndexFieldSelection){
            $preSelect = $object->$getter()->getPreSelect();
            if(is_array($preSelect)) {
                $preSelect = implode("%%", $preSelect);
            }
            return $object->$getter()->getTenant() . "%%%%" . $object->$getter()->getField() . "%%%%" . $preSelect ;
        } else return null;
    }


    /**
     * fills object field data values from CSV Import String
     * @param string $importValue
     * @return double
     */
    public function getFromCsvImport($importValue) {
        $values = explode("%%%%", $importValue);

        $value = null;
        if ($values[0] && $values[1] && $values[2]) {
            $preSelect = explode("%%", $value[2]);
            $value = new Object_Data_IndexFieldSelection($value[0], $values[1], $preSelect);
        }
        return $value;
    }


    /**
     * converts data to be exposed via webservices
     * @param string $object
     * @return mixed
     */
    public function getForWebserviceExport ($object) {

        $key = $this->getName();
        $getter = "get".ucfirst($key);

        if ($object->$getter() instanceof Object_Data_IndexFieldSelection) {
            return array(
                "tenant" => $object->$getter()->getTenant(),
                "field" => $object->$getter()->getField(),
                "preSelect" => implode("%%", $object->$getter()->getPreSelect())
            );
        } else return null;
    }

    /**
     * converts data to be imported via webservices
     * @param mixed $value
     * @return mixed
     */
    public function getFromWebserviceImport ($value) {
        if(empty($value)){
            return null;
        }else if($value["field"] !== null) {
            return new Object_Data_DimensionUnitField($value["tenant"], $value["field"], explode("%%", $value["preSelect"]));
        } else {
            throw new Exception(get_class($this).": cannot get values from web service import - invalid data");
        }
    }


    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return false;
    }

}
