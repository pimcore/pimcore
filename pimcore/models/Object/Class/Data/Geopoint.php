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

class Object_Class_Data_Geopoint extends Object_Class_Data_Geo_Abstract {


    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "geopoint";

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = array(
        "longitude" => "double",
        "latitude" => "double"
    );

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = array(
        "longitude" => "double",
        "latitude" => "double"
    );

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "Object_Data_Geopoint";


    /**
     * @see Object_Class_Data::getDataForResource
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForResource($data, $object = null) {
        if ($data instanceof Object_Data_Geopoint) {
            return array(
                $this->getName() . "__longitude" => $data->getLongitude(),
                $this->getName() . "__latitude" => $data->getLatitude()
            );
        }
        return array(
            $this->getName() . "__longitude" => null,
            $this->getName() . "__latitude" => null
        );
    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param string $data
     * @return string
     */
    public function getDataFromResource($data) {
        if($data[$this->getName() . "__longitude"] && $data[$this->getName() . "__latitude"]) {
            return new Object_Data_Geopoint($data[$this->getName() . "__longitude"], $data[$this->getName() . "__latitude"]);
        }
        return;
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null) {
        if ($data instanceof Object_Data_Geopoint) {
            return array(
                "longitude" => $data->getLongitude(),
                "latitude" => $data->getLatitude()
            );
        }
        
        return;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param string $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null) {
        if($data["longitude"] || $data["latitude"] ) {
            return new Object_Data_Geopoint($data["longitude"], $data["latitude"]);
        }
        return;
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data) {
        if($data instanceof Object_Data_Geopoint) {
            return $data->getLongitude() . "," . $data->getLatitude();
        }
        return "";
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
        if($object->$getter() instanceof Object_Data_Geopoint){
            //TODO latitude and longitude should be switched - but doing this we will loose compatitbilty to old export files
            return $object->$getter()->getLatitude() . "," . $object->$getter()->getLongitude();
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
        $coords = explode(",", $importValue);

        $value = null;
        if ($coords[1] && $coords[0]) {
            //TODO latitude and longitude should be switched - but doing this we will loose compatitbilty to old export files 
            $value = new Object_Data_Geopoint($coords[1], $coords[0]);
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
        
        if ($object->$getter() instanceof Object_Data_Geopoint) {
            return array(
                "longitude" => $object->$getter()->getLongitude(),
                "latitude" => $object->$getter()->getLatitude()
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
        }else if($value["longitude"] !== null && $value["latitude"] !== null ) {
            return new Object_Data_Geopoint($value["longitude"], $value["latitude"]);
        } else {
            throw new Exception("cannot get values from web service import - invalid data");
        }
    }
}
