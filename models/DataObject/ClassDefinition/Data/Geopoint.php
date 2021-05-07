<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Geo\AbstractGeo;
use Pimcore\Normalizer\NormalizerInterface;

class Geopoint extends AbstractGeo implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    EqualComparisonInterface,
    VarExporterInterface,
    NormalizerInterface
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'geopoint';

    /**
     * Type for the column to query
     *
     * @var array
     */
    public $queryColumnType = [
        'longitude' => 'double',
        'latitude' => 'double',
    ];

    /**
     * Type for the column
     *
     * @var array
     */
    public $columnType = [
        'longitude' => 'double',
        'latitude' => 'double',
    ];

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\Geopoint';

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Geopoint) {
            return [
                $this->getName() . '__longitude' => $data->getLongitude(),
                $this->getName() . '__latitude' => $data->getLatitude(),
            ];
        }

        return [
            $this->getName() . '__longitude' => null,
            $this->getName() . '__latitude' => null,
        ];
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\GeoCoordinates|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data[$this->getName() . '__longitude'] && $data[$this->getName() . '__latitude']) {
            $geopoint = new DataObject\Data\GeoCoordinates($data[$this->getName() . '__latitude'], $data[$this->getName() . '__longitude']);

            if (isset($params['owner'])) {
                $geopoint->setOwner($params['owner'], $params['fieldname'], $params['language'] ?? null);
            }

            return $geopoint;
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Geopoint) {
            return [
                'longitude' => $data->getLongitude(),
                'latitude' => $data->getLatitude(),
            ];
        }

        return null;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\GeoCoordinates|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if (is_array($data) && ($data['longitude'] || $data['latitude'])) {
            return new DataObject\Data\GeoCoordinates($data['latitude'], $data['longitude']);
        }

        return null;
    }

    /**
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\GeoCoordinates|null
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param DataObject\Data\Geopoint|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\Geopoint) {
            return $data->getLongitude() . ',' . $data->getLatitude();
        }

        return '';
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\GeoCoordinates) {
            return $data->getLatitude() . ',' . $data->getLongitude();
        }

        return '';
    }

    /**
     * @deprecated
     *
     * @param string $importValue
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return null|DataObject\ClassDefinition\Data|DataObject\Data\GeoCoordinates
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $coords = explode(',', $importValue);

        $value = null;
        if ($coords[0] && $coords[1]) {
            $value = new DataObject\Data\GeoCoordinates($coords[0], $coords[1]);
        }

        return $value;
    }

    /**
     * @param DataObject\Concrete|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
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
     * @deprecated
     *
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data instanceof DataObject\Data\Geopoint) {
            return [
                'longitude' => $data->getLongitude(),
                'latitude' => $data->getLatitude(),
            ];
        } else {
            return null;
        }
    }

    /**
     * @deprecated
     *
     * @param mixed $value
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
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
            if ($value['longitude'] !== null && $value['latitude'] !== null) {
                return new DataObject\Data\GeoCoordinates($value['latitude'], $value['longitude']);
            } else {
                throw new \Exception('cannot get values from web service import - invalid data');
            }
        }
    }

    /** True if change is allowed in edit mode.
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Encode value for packing it into a single column.
     *
     * @deprecated marshal is deprecated and will be removed in Pimcore 10. Use normalize instead.
     *
     * @param mixed $value
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if ($value instanceof DataObject\Data\Geopoint) {
            return [
                'value' => $value->getLatitude(),
                'value2' => $value->getLongitude(),
            ];
        }
    }

    /**
     * { @inheritdoc }
     */
    public function normalize($data, $params = [])
    {
        if ($data instanceof DataObject\Data\Geopoint) {
            return [
                'latitude' => $data->getLatitude(),
                'longitude' => $data->getLongitude(),
            ];
        }

        return null;
    }

    /**
     * { @inheritdoc }
     */
    public function denormalize($data, $params = [])
    {
        if (is_array($data)) {
            return new DataObject\Data\GeoCoordinates($data['latitude'], $data['longitude']);
        }

        return null;
    }

    /** See marshal
     *
     * @deprecated unmarshal is deprecated and will be removed in Pimcore 10. Use denormalize instead.
     *
     * @param mixed $value
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        if (is_array($value)) {
            $data = new DataObject\Data\GeoCoordinates($value['value'], $value['value2']);

            return $data;
        }
    }

    /**
     * @param DataObject\Data\Geopoint|null $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return array
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     *
     * @param DataObject\Data\Geopoint|null $oldValue
     * @param DataObject\Data\Geopoint|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if (!$oldValue instanceof DataObject\Data\Geopoint
            || !$newValue instanceof DataObject\Data\Geopoint) {
            return false;
        }

        return (abs($oldValue->getLongitude() - $newValue->getLongitude()) < 0.000000000001)
            && (abs($oldValue->getLatitude() - $newValue->getLatitude()) < 0.000000000001);
    }
}
