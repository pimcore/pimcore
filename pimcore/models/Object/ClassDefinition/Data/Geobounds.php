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

class Geobounds extends Model\Object\ClassDefinition\Data\Geo\AbstractGeo {


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
    public $phpdocType = "\\Pimcore\\Model\\Object\\Data\\Geobounds";


    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param Object\Data\Geobounds $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForResource($data, $object = null) {
        if ($data instanceof Object\Data\Geobounds) {
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
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param array $data
     * @return string 
     */
    public function getDataFromResource($data) {
        if($data[$this->getName() . "__NElongitude"] && $data[$this->getName() . "__NElatitude"] && $data[$this->getName() . "__SWlongitude"] && $data[$this->getName() . "__SWlatitude"]) {
            $ne = new Object\Data\Geopoint($data[$this->getName() . "__NElongitude"], $data[$this->getName() . "__NElatitude"]);
            $sw = new Object\Data\Geopoint($data[$this->getName() . "__SWlongitude"], $data[$this->getName() . "__SWlatitude"]);
            
            return new Object\Data\Geobounds($ne,$sw);
        }
        return;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param Object\Data\Geobounds $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForQueryResource($data, $object = null) {
        return $this->getDataForResource($data, $object);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param Object\Data\Geobounds $data
     * @param null|Model\Object\AbstractObject $object
     * @return array
     */
    public function getDataForEditmode($data, $object = null) {
        if($data instanceof Object\Data\Geobounds) {
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
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @return Object\Data\Geobounds
     */
    public function getDataFromEditmode($data, $object = null) {
        if($data["NElongitude"] !== null && $data["NElatitude"] !== null && $data["SWlongitude"] !== null && $data["SWlatitude"] !== null) {
            $ne = new Object\Data\Geopoint($data["NElongitude"], $data["NElatitude"]);
            $sw = new Object\Data\Geopoint($data["SWlongitude"], $data["SWlatitude"]);
            
            return new Object\Data\Geobounds($ne,$sw);
        }
        return;
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param Object\Data\Geobounds $data
     * @return string
     */
    public function getVersionPreview($data) {
        if($data instanceof Object\Data\Geobounds) {
            return $data->getNorthEast()->getLongitude() . "," . $data->getNorthEast()->getLatitude() . " " . $data->getSouthWest()->getLongitude() . "," . $data->getSouthWest()->getLatitude();
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
        if($data instanceof Object\Data\Geobounds){
            return  $data->getNorthEast()->getLongitude().",".$data->getNorthEast()->getLatitude()."|".$data->getSouthWest()->getLongitude().",".$data->getSouthWest()->getLatitude();
        } else return null;
    }

    /**
     * @param string $importValue
     * @return null|Object\ClassDefinition\Data|Object\Data\Geobounds
     */
    public function getFromCsvImport($importValue) {
        $points = explode("|", $importValue);
        $value = null;
        if(is_array($points) and count($points)==2){
            $northEast = explode(",",$points[0]);
            $southWest = explode(",",$points[1]);
            if ($northEast[0] && $northEast[1] && $southWest[0] && $southWest[1]) {
                $value = new Object\Data\Geobounds(new Object\Data\Geopoint($northEast[0],$northEast[1]),new Object\Data\Geopoint($southWest[0],$southWest[1]));
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
        $data = $this->getDataFromObjectParam($object);
        if ($data instanceof Object\Data\Geobounds) {
            return array(
                "NElongitude" => $data->getNorthEast()->getLongitude(),
                "NElatitude" => $data->getNorthEast()->getLatitude(),
                "SWlongitude" => $data->getSouthWest()->getLongitude(),
                "SWlatitude" => $data->getSouthWest()->getLatitude()
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
            if($value["NElongitude"] !== null && $value["NElatitude"] !== null && $value["SWlongitude"] !== null && $value["SWlatitude"] !== null) {
                $ne = new Object\Data\Geopoint($value["NElongitude"], $value["NElatitude"]);
                $sw = new Object\Data\Geopoint($value["SWlongitude"], $value["SWlatitude"]);
                return new Object\Data\Geobounds($ne,$sw);
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
