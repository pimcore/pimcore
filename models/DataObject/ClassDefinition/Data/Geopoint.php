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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Geo\AbstractGeo;
use Pimcore\Model\Element\ValidationException;
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
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'geopoint';

    /**
     * Type for the column to query
     *
     * @internal
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
     * @internal
     *
     * @var array
     */
    public $columnType = [
        'longitude' => 'double',
        'latitude' => 'double',
    ];

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param DataObject\Data\GeoCoordinates|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\GeoCoordinates) {
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
                $geopoint->_setOwner($params['owner']);
                $geopoint->_setOwnerFieldname($params['fieldname']);
                $geopoint->_setOwnerLanguage($params['language'] ?? null);
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
     * @param DataObject\Data\GeoCoordinates|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\GeoCoordinates) {
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
     * @param array|null $data
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
     * @param DataObject\Data\GeoCoordinates|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof DataObject\Data\GeoCoordinates) {
            return $data->getLongitude() . ',' . $data->getLatitude();
        }

        return '';
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($data, $params = [])
    {
        if ($data instanceof DataObject\Data\GeoCoordinates) {
            return [
                'latitude' => $data->getLatitude(),
                'longitude' => $data->getLongitude(),
            ];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $params = [])
    {
        if (is_array($data)) {
            return new DataObject\Data\GeoCoordinates($data['latitude'], $data['longitude']);
        }

        return null;
    }

    /**
     * @param DataObject\Data\GeoCoordinates|null $data
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
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        $isEmpty = true;

        if ($data) {
            if (!$data instanceof DataObject\Data\GeoCoordinates) {
                throw new ValidationException('Expected an instance of GeoCoordinates');
            }
            $isEmpty = false;
        }

        if (!$omitMandatoryCheck && $this->getMandatory() && $isEmpty) {
            throw new ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    /**
     *
     * @param DataObject\Data\GeoCoordinates|null $oldValue
     * @param DataObject\Data\GeoCoordinates|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if (!$oldValue instanceof DataObject\Data\GeoCoordinates
            || !$newValue instanceof DataObject\Data\GeoCoordinates) {
            return false;
        }

        return (abs($oldValue->getLongitude() - $newValue->getLongitude()) < 0.000000000001)
            && (abs($oldValue->getLatitude() - $newValue->getLatitude()) < 0.000000000001);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\GeoCoordinates::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\GeoCoordinates::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\GeoCoordinates::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\GeoCoordinates::class . '|null';
    }
}
