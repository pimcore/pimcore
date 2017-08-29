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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Tool\Serialize;

class Geopolygon extends Model\DataObject\ClassDefinition\Data\Geo\AbstractGeo
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'geopolygon';

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'longtext';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'longtext';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = 'array';

    /**
     * @see DataObject\ClassDefinition\Data::getDataForResource
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        return Serialize::serialize($data);
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataFromResource
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        return Serialize::unserialize($data);
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForQueryResource
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see DataObject\ClassDefinition\Data::getDataForEditmode
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params     *
     *
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if (!empty($data)) {
            if (is_array($data)) {
                $points = [];
                foreach ($data as $point) {
                    $points[] = [
                        'latitude' => $point->getLatitude(),
                        'longitude' => $point->getLongitude()
                    ];
                }

                return $points;
            }
        }

        return;
    }

    /**
     * @see Model\DataObject\ClassDefinition\Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if (is_array($data)) {
            $points = [];
            foreach ($data as $point) {
                $points[] = new DataObject\Data\Geopoint($point['longitude'], $point['latitude']);
            }

            return $points;
        }

        return;
    }

    /**
     * @see DataObject\ClassDefinition\Data::getVersionPreview
     *
     * @param string $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return '';
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (!empty($data)) {
            $dataArray = $this->getDataForEditmode($data, $object, $params);
            $rows = [];
            if (is_array($dataArray)) {
                foreach ($dataArray as $point) {
                    $rows[] = implode(';', $point);
                }

                return implode('|', $rows);
            }
        }

        return null;
    }

    /**
     * @param $importValue
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array|mixed
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $rows = explode('|', $importValue);
        $points = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $coords = explode(';', $row);
                $points[] = new  DataObject\Data\Geopoint($coords[1], $coords[0]);
            }
        }

        return $points;
    }

    /**
     * @param $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return '';
    }

    /**
     * converts data to be exposed via webservices
     *
     * @param string $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
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
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     * @param null $idMapper
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        if (empty($value)) {
            return null;
        } elseif (is_array($value)) {
            $points = [];
            foreach ($value as $point) {
                $point = (array) $point;
                if ($point['longitude'] != null and $point['latitude'] != null) {
                    $points[] = new DataObject\Data\Geopoint($point['longitude'], $point['latitude']);
                } else {
                    throw new \Exception('cannot get values from web service import - invalid data');
                }
            }

            return $points;
        } else {
            throw new \Exception('cannot get values from web service import - invalid data');
        }
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the ObjectMerger plugin documentation for details
     *
     * @param $data
     * @param null $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        if (!empty($data)) {
            $line = '';
            $isFirst = true;
            if (is_array($data)) {
                foreach ($data as $point) {
                    if (!$isFirst) {
                        $line .= ' ';
                    }
                    $line .= $point->getLatitude() . ',' . $point->getLongitude();
                    $isFirst = false;
                }

                return $line;
            }
        }

        return;
    }

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if ($value) {
            $value = Serialize::unserialize($value);
            $result = [];
            if (is_array($value)) {
                /** @var $point DataObject\Data\Geopoint */
                foreach ($value as $point) {
                    $result[] = [
                            $point->getLatitude(),
                            $point->getLongitude()
                        ];
                }
            }

            return [
                'value' => json_encode($result)
            ];
        }
    }

    /** See marshal
     * @param mixed $value
     * @param Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        if ($value && $value['value']) {
            $value = json_decode($value['value']);
            $result = [];
            if (is_array($value)) {
                foreach ($value as $point) {
                    $newPoint = new DataObject\Data\Geopoint($point[1], $point[1]);
                    $newPoint->setLatitude($point[0]);
                    $newPoint->setLongitude($point[1]);
                    $result[] = $newPoint;
                }
            }
            $result = Serialize::serialize($result);

            return $result;
        }
    }
}
