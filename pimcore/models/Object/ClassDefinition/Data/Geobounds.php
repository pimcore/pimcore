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

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;

class Geobounds extends Model\Object\ClassDefinition\Data\Geo\AbstractGeo
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'geobounds';

    /**
     * Type for the column to query
     *
     * @var array
     */
    public $queryColumnType = [
        'NElongitude' => 'double',
        'NElatitude' => 'double',
        'SWlongitude' => 'double',
        'SWlatitude' => 'double'
    ];

    /**
     * Type for the column
     *
     * @var array
     */
    public $columnType = [
        'NElongitude' => 'double',
        'NElatitude' => 'double',
        'SWlongitude' => 'double',
        'SWlatitude' => 'double'
    ];

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\Object\\Data\\Geobounds';

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     *
     * @param Object\Data\Geobounds $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof Object\Data\Geobounds) {
            return [
                $this->getName() . '__NElongitude' => $data->getNorthEast()->getLongitude(),
                $this->getName() . '__NElatitude' => $data->getNorthEast()->getLatitude(),
                $this->getName() . '__SWlongitude' => $data->getSouthWest()->getLongitude(),
                $this->getName() . '__SWlatitude' => $data->getSouthWest()->getLatitude()
            ];
        }

        return [
            $this->getName() . '__NElongitude' => null,
            $this->getName() . '__NElatitude' => null,
            $this->getName() . '__SWlongitude' => null,
            $this->getName() . '__SWlatitude' => null
        ];
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     *
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data[$this->getName() . '__NElongitude'] && $data[$this->getName() . '__NElatitude'] && $data[$this->getName() . '__SWlongitude'] && $data[$this->getName() . '__SWlatitude']) {
            $ne = new Object\Data\Geopoint($data[$this->getName() . '__NElongitude'], $data[$this->getName() . '__NElatitude']);
            $sw = new Object\Data\Geopoint($data[$this->getName() . '__SWlongitude'], $data[$this->getName() . '__SWlatitude']);

            return new Object\Data\Geobounds($ne, $sw);
        }

        return;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     *
     * @param Object\Data\Geobounds $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     *
     * @param Object\Data\Geobounds $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof Object\Data\Geobounds) {
            return [
                'NElongitude' => $data->getNorthEast()->getLongitude(),
                'NElatitude' => $data->getNorthEast()->getLatitude(),
                'SWlongitude' => $data->getSouthWest()->getLongitude(),
                'SWlatitude' => $data->getSouthWest()->getLatitude()
            ];
        }

        return;
    }

    /**
     * @param $data
     * @param null $object
     * @param array $params
     *
     * @return array
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return Object\Data\Geobounds
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data['NElongitude'] !== null && $data['NElatitude'] !== null && $data['SWlongitude'] !== null && $data['SWlatitude'] !== null) {
            $ne = new Object\Data\Geopoint($data['NElongitude'], $data['NElatitude']);
            $sw = new Object\Data\Geopoint($data['SWlongitude'], $data['SWlatitude']);

            return new Object\Data\Geobounds($ne, $sw);
        }

        return;
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     *
     * @param Object\Data\Geobounds $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof Object\Data\Geobounds) {
            return $data->getNorthEast()->getLongitude() . ',' . $data->getNorthEast()->getLatitude() . ' ' . $data->getSouthWest()->getLongitude() . ',' . $data->getSouthWest()->getLatitude();
        }

        return '';
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param Object\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Object\Data\Geobounds) {
            return  $data->getNorthEast()->getLongitude().','.$data->getNorthEast()->getLatitude().'|'.$data->getSouthWest()->getLongitude().','.$data->getSouthWest()->getLatitude();
        } else {
            return null;
        }
    }

    /**
     * @param string $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return null|Object\ClassDefinition\Data|Object\Data\Geobounds
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $points = explode('|', $importValue);
        $value = null;
        if (is_array($points) and count($points) == 2) {
            $northEast = explode(',', $points[0]);
            $southWest = explode(',', $points[1]);
            if ($northEast[0] && $northEast[1] && $southWest[0] && $southWest[1]) {
                $value = new Object\Data\Geobounds(new Object\Data\Geopoint($northEast[0], $northEast[1]), new Object\Data\Geopoint($southWest[0], $southWest[1]));
            }
        }

        return $value;
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
        if ($data instanceof Object\Data\Geobounds) {
            return [
                'NElongitude' => $data->getNorthEast()->getLongitude(),
                'NElatitude' => $data->getNorthEast()->getLatitude(),
                'SWlongitude' => $data->getSouthWest()->getLongitude(),
                'SWlatitude' => $data->getSouthWest()->getLatitude()
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
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        if (empty($value)) {
            return null;
        } else {
            $value = (array) $value;
            if ($value['NElongitude'] !== null && $value['NElatitude'] !== null && $value['SWlongitude'] !== null && $value['SWlatitude'] !== null) {
                $ne = new Object\Data\Geopoint($value['NElongitude'], $value['NElatitude']);
                $sw = new Object\Data\Geopoint($value['SWlongitude'], $value['SWlatitude']);

                return new Object\Data\Geobounds($ne, $sw);
            } else {
                throw new \Exception('cannot get values from web service import - invalid data');
            }
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

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if ($value) {
            return [
                'value' =>  json_encode([$value[$this->getName() . '__NElatitude'], $value[$this->getName() . '__NElongitude']]),
                'value2' => json_encode([$value[$this->getName() . '__SWlatitude'], $value[$this->getName() . '__SWlongitude']])
            ];
        }
    }

    /** See marshal
     * @param mixed $value
     * @param Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        if ($value && $value['value'] && $value['value2']) {
            $dataNE = json_decode($value['value']);
            $dataSW = json_decode($value['value2']);

            $result = [];
            $result[$this->getName() . '__NElatitude'] = $dataNE[0];
            $result[$this->getName() . '__NElongitude'] = $dataNE[1];
            $result[$this->getName() . '__SWlatitude'] = $dataSW[0];
            $result[$this->getName() . '__SWlongitude'] = $dataSW[1];

            return $result;
        }
    }
}
