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

class Object_Class_Data_Geobounds extends Object_Class_Data_Geo_Abstract {


    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "geobounds";

    /**
     * Type for the column to query
     *
     * @var array
     */
    public $queryColumnType = array(
        "NElongitude" => "double",
        "NElatitude" => "double",
        "SWlongitude" => "double",
        "SWlatitude" => "double"
    );

    /**
     * Type for the column
     *
     * @var array
     */
    public $columnType = array(
        "NElongitude" => "double",
        "NElatitude" => "double",
        "SWlongitude" => "double",
        "SWlatitude" => "double"
    );

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "Object_Data_Geobounds";


    /**
     * @see Object_Class_Data::getDataForResource
     * @param Object_Data_Geobounds $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForResource($data, $object = null) {
        if ($data instanceof Object_Data_Geobounds) {
            return array(
                $this->getName() . "__NElongitude" => $data->getNorthEast()->getLongitude(),
                $this->getName() . "__NElatitude" => $data->getNorthEast()->getLatitude(),
                $this->getName() . "__SWlongitude" => $data->getSouthWest()->getLongitude(),
                $this->getName() . "__SWlatitude" => $data->getSouthWest()->getLatitude()
            );
        }
        return array(
            $this->getName() . "__NElongitude" => null,
            $this->getName() . "__NElatitude" => null,
            $this->getName() . "__SWlongitude" => null,
            $this->getName() . "__SWlatitude" => null
        );
    }

    /**
     * @see Object_Class_Data::getDataFromResource
     * @param array $data
     * @return string 
     */
    public function getDataFromResource($data) {
        if($data[$this->getName() . "__NElongitude"] && $data[$this->getName() . "__NElatitude"] && $data[$this->getName() . "__SWlongitude"] && $data[$this->getName() . "__SWlatitude"]) {
            $ne = new Object_Data_Geopoint($data[$this->getName() . "__NElongitude"], $data[$this->getName() . "__NElatitude"]);
            $sw = new Object_Data_Geopoint($data[$this->getName() . "__SWlongitude"], $data[$this->getName() . "__SWlatitude"]);
            
            return new Object_Data_Geobounds($ne,$sw);
        }
        return;
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     * @param Object_Data_Geobounds $data
     * @param null|Object_Abstract $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     * @param Object_Data_Geobounds $data
     * @param null|Object_Abstract $object
     * @return array
     */
    public function getDataForEditmode($data, $object = null) {
        if($data instanceof Object_Data_Geobounds) {
            return array(
                "NElongitude" => $data->getNorthEast()->getLongitude(),
                "NElatitude" => $data->getNorthEast()->getLatitude(),
                "SWlongitude" => $data->getSouthWest()->getLongitude(),
                "SWlatitude" => $data->getSouthWest()->getLatitude()
            );
        }
        return;
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     * @param array $data
     * @param null|Object_Abstract $object
     * @return Object_Data_Geobounds
     */
    public function getDataFromEditmode($data, $object = null) {
        if($data["NElongitude"] !== null && $data["NElatitude"] !== null && $data["SWlongitude"] !== null && $data["SWlatitude"] !== null) {
            $ne = new Object_Data_Geopoint($data["NElongitude"], $data["NElatitude"]);
            $sw = new Object_Data_Geopoint($data["SWlongitude"], $data["SWlatitude"]);
            
            return new Object_Data_Geobounds($ne,$sw);
        }
        return;
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     * @param Object_Data_Geobounds $data
     * @return string
     */
    public function getVersionPreview($data) {
        if($data instanceof Object_Data_Geobounds) {
            return $data->getNorthEast()->getLongitude() . "," . $data->getNorthEast()->getLatitude() . " " . $data->getSouthWest()->getLongitude() . "," . $data->getSouthWest()->getLatitude();
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
        if($object->$getter() instanceof Object_Data_Geobounds){
            return  $object->$getter()->getNorthEast()->getLongitude().",".$object->$getter()->getNorthEast()->getLatitude()."|".$object->$getter()->getSouthWest()->getLongitude().",".$object->$getter()->getSouthWest()->getLatitude();
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
        $points = explode("|", $importValue);
        $value = null;
        if(is_array($points) and count($points)==2){
            $northEast = explode(",",$points[0]);
            $southWest = explode(",",$points[1]);
            if ($northEast[0] && $northEast[1] && $southWest[0] && $southWest[1]) {
                $value = new Object_Data_Geobounds(new Object_Data_Geopoint($northEast[0],$northEast[1]),new Object_Data_Geopoint($southWest[0],$southWest[1]));
            }
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
        if ($object->$getter() instanceof Object_Data_Geobounds) {
            return array(
                "NElongitude" => $object->$getter()->getNorthEast()->getLongitude(),
                "NElatitude" => $object->$getter()->getNorthEast()->getLatitude(),
                "SWlongitude" => $object->$getter()->getSouthWest()->getLongitude(),
                "SWlatitude" => $object->$getter()->getSouthWest()->getLatitude()
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
        } else if($value["NElongitude"] !== null && $value["NElatitude"] !== null && $value["SWlongitude"] !== null && $value["SWlatitude"] !== null) {
            $ne = new Object_Data_Geopoint($value["NElongitude"], $value["NElatitude"]);
            $sw = new Object_Data_Geopoint($value["SWlongitude"], $value["SWlatitude"]);
            return new Object_Data_Geobounds($ne,$sw);
        } else {
            throw new Exception("cannot get values from web service import - invalid data");
        }
    }
}
