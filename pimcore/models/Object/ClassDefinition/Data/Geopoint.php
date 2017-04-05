<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;

class Geopoint extends Model\Object\ClassDefinition\Data\Geo\AbstractGeo
{

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
    public $queryColumnType = [
        "longitude" => "double",
        "latitude" => "double"
    ];

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = [
        "longitude" => "double",
        "latitude" => "double"
    ];

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
     * @param mixed $params
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof Object\Data\Geopoint) {
            return [
                $this->getName() . "__longitude" => $data->getLongitude(),
                $this->getName() . "__latitude" => $data->getLatitude()
            ];
        }

        return [
            $this->getName() . "__longitude" => null,
            $this->getName() . "__latitude" => null
        ];
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data[$this->getName() . "__longitude"] && $data[$this->getName() . "__latitude"]) {
            return new Object\Data\Geopoint($data[$this->getName() . "__longitude"], $data[$this->getName() . "__latitude"]);
        }

        return;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof Object\Data\Geopoint) {
            return [
                "longitude" => $data->getLongitude(),
                "latitude" => $data->getLatitude()
            ];
        }

        return;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if (is_array($data) && ($data["longitude"] || $data["latitude"])) {
            return new Object\Data\Geopoint($data["longitude"], $data["latitude"]);
        }

        return;
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param string $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof Object\Data\Geopoint) {
            return $data->getLongitude() . "," . $data->getLatitude();
        }

        return "";
    }



    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object\AbstractObject $object
     * @param array $params
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Object\Data\Geopoint) {
            //TODO latitude and longitude should be switched - but doing this we will loose compatitbilty to old export files
            return $data->getLatitude() . "," . $data->getLongitude();
        } else {
            return null;
        }
    }

    /**
     * @param string $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return null|Object\ClassDefinition\Data|Object\Data\Geopoint
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $coords = explode(",", $importValue);

        $value = null;
        if ($coords[1] && $coords[0]) {
            //TODO latitude and longitude should be switched - but doing this we will loose compatitbilty to old export files
            $value = new Object\Data\Geopoint($coords[1], $coords[0]);
        }

        return $value;
    }

    /**
     * @param $object
     * @param mixed $params
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return "";
    }

    /**
     * converts data to be exposed via webservices
     * @param string $object
     * @param mixed $params
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data instanceof Object\Data\Geopoint) {
            return [
                "longitude" => $data->getLongitude(),
                "latitude" => $data->getLatitude()
            ];
        } else {
            return null;
        }
    }

    /**
     * @param mixed $value
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @param null $idMapper
     * @return mixed|void
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        if (empty($value)) {
            return null;
        } else {
            $value = (array) $value;
            if ($value["longitude"] !== null && $value["latitude"] !== null) {
                return new Object\Data\Geopoint($value["longitude"], $value["latitude"]);
            } else {
                throw new \Exception("cannot get values from web service import - invalid data");
            }
        }
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param Model\Object\AbstractObject $object
     * @param mixed $params
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if ($value instanceof Object\Data\Geopoint) {
            return [
                "value" => $value->getLatitude(),
                "value2" => $value->getLongitude()
            ];
        }
    }

    /** See marshal
     * @param mixed $value
     * @param Model\Object\AbstractObject $object
     * @param mixed $params
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        if (is_array($value)) {
            $data = new Object\Data\Geopoint($value["value2"], $value["value"]);
            return $data;
        }
    }
}
