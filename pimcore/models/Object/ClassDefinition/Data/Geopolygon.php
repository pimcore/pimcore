<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Tool\Serialize;

class Geopolygon extends Model\Object\ClassDefinition\Data\Geo\AbstractGeo
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "geopolygon";

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "longtext";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "longtext";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "array";

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = array())
    {
        return Serialize::serialize($data);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = array())
    {
        return Serialize::unserialize($data);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = array())
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params     *
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = array())
    {
        if (!empty($data)) {
            if (is_array($data)) {
                $points = array();
                foreach ($data as $point) {
                    $points[] = array(
                        "latitude" => $point->getLatitude(),
                        "longitude" => $point->getLongitude()
                    );
                }
                return $points;
            }
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
    public function getDataFromEditmode($data, $object = null, $params = array())
    {
        if (is_array($data)) {
            $points = array();
            foreach ($data as $point) {
                $points[] = new Object\Data\Geopoint($point["longitude"], $point["latitude"]);
            }
            return $points;
        }
        return;
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data)
    {
        return "";
    }

    /**
     * converts object data to a simple string value or CSV Export
     * @abstract
     * @param Object\AbstractObject $object
     * @param array $params
     * @return string
     */
    public function getForCsvExport($object, $params = array())
    {
        $data = $this->getDataFromObjectParam($object);
        if (!empty($data)) {
            $dataArray = $this->getDataForEditmode($data, $object, $params);
            $rows = array();
            if (is_array($dataArray)) {
                foreach ($dataArray as $point) {
                    $rows[] = implode(";", $point);
                }
                return implode("|", $rows);
            }
        }
        return null;
    }

    /**
     * @param $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return array|mixed
     */
    public function getFromCsvImport($importValue, $object = null, $params = array())
    {
        $rows = explode("|", $importValue);
        $points = array();
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $coords = explode(";", $row);
                $points[] = new  Object\Data\Geopoint($coords[1], $coords[0]);
            }
        }
        return $points;
    }

    /**
     * converts data to be exposed via webservices
     * @param string $object
     * @param mixed $params
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = array())
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (!empty($data)) {
            return $this->getDataForEditmode($data, $object, $params);
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
    public function getFromWebserviceImport($value, $object = null, $params = array(), $idMapper = null)
    {
        if (empty($value)) {
            return null;
        } elseif (is_array($value)) {
            $points = array();
            foreach ($value as $point) {
                $point = (array) $point;
                if ($point["longitude"]!=null and  $point["latitude"]!=null) {
                    $points[] = new Object\Data\Geopoint($point["longitude"], $point["latitude"]);
                } else {
                    throw new \Exception("cannot get values from web service import - invalid data");
                }
            }
            return $points;
        } else {
            throw new \Exception("cannot get values from web service import - invalid data");
        }
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = array())
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     * @param $data
     * @param null $object
     * @param mixed $params
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = array())
    {
        if (!empty($data)) {
            $line = "";
            $isFirst = true;
            if (is_array($data)) {
                $points = array();
                foreach ($data as $point) {
                    if (!$isFirst) {
                        $line .= " ";
                    }
                    $line .= $point->getLatitude() . "," . $point->getLongitude();
                    $isFirst = false;
                }


                return $line;
            }
        }
        return;
    }
}
