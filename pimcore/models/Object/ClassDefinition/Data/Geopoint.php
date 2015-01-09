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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;

class Geopoint extends Model\Object\ClassDefinition\Data\Geo\AbstractGeo {

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
    public $phpdocType = "\\Pimcore\\Model\\Object\\Data\\Geopoint";


    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForResource($data, $object = null) {
        if ($data instanceof Object\Data\Geopoint) {
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
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param string $data
     * @return string
     */
    public function getDataFromResource($data) {
        if($data[$this->getName() . "__longitude"] && $data[$this->getName() . "__latitude"]) {
            return new Object\Data\Geopoint($data[$this->getName() . "__longitude"], $data[$this->getName() . "__latitude"]);
        }
        return;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null) {
        if ($data instanceof Object\Data\Geopoint) {
            return array(
                "longitude" => $data->getLongitude(),
                "latitude" => $data->getLatitude()
            );
        }
        
        return;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataFromEditmode($data, $object = null) {
        if($data["longitude"] || $data["latitude"] ) {
            return new Object\Data\Geopoint($data["longitude"], $data["latitude"]);
        }
        return;
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data) {
        if($data instanceof Object\Data\Geopoint) {
            return $data->getLongitude() . "," . $data->getLatitude();
        }
        return "";
    }

   

     /**
      * converts object data to a simple string value or CSV Export
      * @abstract
      * @param Model\Object\AbstractObject $object
      * @return string
      */
    public function getForCsvExport($object) {
        $data = $this->getDataFromObjectParam($object);
        if($data instanceof Object\Data\Geopoint){
            //TODO latitude and longitude should be switched - but doing this we will loose compatitbilty to old export files
            return $data->getLatitude() . "," . $data->getLongitude();
        } else return null;
    }

    /**
     * @param string $importValue
     * @return null|Object\ClassDefinition\Data|Object\Data\Geopoint
     */
    public function getFromCsvImport($importValue) {
        $coords = explode(",", $importValue);

        $value = null;
        if ($coords[1] && $coords[0]) {
            //TODO latitude and longitude should be switched - but doing this we will loose compatitbilty to old export files 
            $value = new Object\Data\Geopoint($coords[1], $coords[0]);
        }
        return $value;
    }


       /**
     * converts data to be exposed via webservices
     * @param string $object
     * @return mixed
     */
    public function getForWebserviceExport ($object) {

        $data = $this->getDataFromObjectParam($object);
        
        if ($data instanceof Object\Data\Geopoint) {
            return array(
                "longitude" => $data->getLongitude(),
                "latitude" => $data->getLatitude()
            );
        } else return null;
    }

    /**
     * @param mixed $value
     * @param null $object
     * @param null $idMapper
     * @return mixed|void
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $idMapper = null) {
        if(empty($value)){
            return null;   
        } else {
            $value = (array) $value;
            if($value["longitude"] !== null && $value["latitude"] !== null ) {
                return new Object\Data\Geopoint($value["longitude"], $value["latitude"]);
            } else {
                throw new \Exception("cannot get values from web service import - invalid data");
            }
        }
    }

    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return true;
    }
}
