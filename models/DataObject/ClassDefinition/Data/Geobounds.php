<?php
declare(strict_types=1);

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

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Geo\AbstractGeo;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Normalizer\NormalizerInterface;

class Geobounds extends AbstractGeo implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    EqualComparisonInterface,
    VarExporterInterface,
    NormalizerInterface
{
    /**
     * @param null|DataObject\Concrete $object
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, Concrete $object = null, array $params = []): array
    {
        if ($data instanceof DataObject\Data\Geobounds) {
            return [
                $this->getName() . '__NElongitude' => $data->getNorthEast()->getLongitude(),
                $this->getName() . '__NElatitude' => $data->getNorthEast()->getLatitude(),
                $this->getName() . '__SWlongitude' => $data->getSouthWest()->getLongitude(),
                $this->getName() . '__SWlatitude' => $data->getSouthWest()->getLatitude(),
            ];
        }

        return [
            $this->getName() . '__NElongitude' => null,
            $this->getName() . '__NElatitude' => null,
            $this->getName() . '__SWlongitude' => null,
            $this->getName() . '__SWlatitude' => null,
        ];
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        $isEmpty = true;

        if ($data) {
            if (!$data instanceof DataObject\Data\Geobounds) {
                throw new ValidationException('Expected an instance of Geobounds');
            }
            $isEmpty = false;
        }

        if (!$omitMandatoryCheck && $this->getMandatory() && $isEmpty) {
            throw new ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?DataObject\Data\Geobounds
    {
        if ($data[$this->getName() . '__NElongitude'] && $data[$this->getName() . '__NElatitude'] && $data[$this->getName() . '__SWlongitude'] && $data[$this->getName() . '__SWlatitude']) {
            $ne = new DataObject\Data\GeoCoordinates($data[$this->getName() . '__NElatitude'], $data[$this->getName() . '__NElongitude']);
            $sw = new DataObject\Data\GeoCoordinates($data[$this->getName() . '__SWlatitude'], $data[$this->getName() . '__SWlongitude']);

            $geobounds = new DataObject\Data\Geobounds($ne, $sw);

            if (isset($params['owner'])) {
                $geobounds->_setOwner($params['owner']);
                $geobounds->_setOwnerFieldname($params['fieldname']);
                $geobounds->_setOwnerLanguage($params['language'] ?? null);
            }

            return $geobounds;
        }

        return null;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        if ($data instanceof DataObject\Data\Geobounds) {
            return [
                'NElongitude' => $data->getNorthEast()->getLongitude(),
                'NElatitude' => $data->getNorthEast()->getLatitude(),
                'SWlongitude' => $data->getSouthWest()->getLongitude(),
                'SWlatitude' => $data->getSouthWest()->getLatitude(),
            ];
        }

        return null;
    }

    /**
     * @param DataObject\Concrete|null $object
     *
     */
    public function getDataForGrid(?DataObject\Data\Geobounds $data, Concrete $object = null, array $params = []): ?array
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?DataObject\Data\Geobounds
    {
        if (is_array($data) && $data['NElongitude'] !== null && $data['NElatitude'] !== null && $data['SWlongitude'] !== null && $data['SWlatitude'] !== null) {
            $ne = new DataObject\Data\GeoCoordinates($data['NElatitude'], $data['NElongitude']);
            $sw = new DataObject\Data\GeoCoordinates($data['SWlatitude'], $data['SWlongitude']);

            return new DataObject\Data\Geobounds($ne, $sw);
        }

        return null;
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        if ($data instanceof DataObject\Data\Geobounds) {
            return $data->getNorthEast()->getLongitude() . ',' . $data->getNorthEast()->getLatitude() . ' ' . $data->getSouthWest()->getLongitude() . ',' . $data->getSouthWest()->getLatitude();
        }

        return '';
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof DataObject\Data\Geobounds) {
            return  $data->getNorthEast()->getLongitude().','.$data->getNorthEast()->getLatitude().'|'.$data->getSouthWest()->getLongitude().','.$data->getSouthWest()->getLatitude();
        }

        return '';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof DataObject\Data\Geobounds) {
            return [
                'northEast' => ['latitude' => $value->getNorthEast()->getLatitude(), 'longitude' => $value->getNorthEast()->getLongitude()],
                'southWest' => ['latitude' => $value->getSouthWest()->getLatitude(), 'longitude' => $value->getSouthWest()->getLongitude()],
            ];
        } elseif (is_array($value)) {
            //TODO kick this as soon as classification store is implemented
            return [
                'northEast' => ['latitude' => $value[$this->getName() . '__NElatitude'], 'longitude' => $value[$this->getName() . '__NElongitude']],
                'southWest' => ['latitude' => $value[$this->getName() . '__SWlatitude'], 'longitude' => $value[$this->getName() . '__SWlongitude']],
            ];
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?DataObject\Data\Geobounds
    {
        if (is_array($value)) {
            $ne = new DataObject\Data\GeoCoordinates($value['northEast']['latitude'], $value['northEast']['longitude']);
            $sw = new DataObject\Data\GeoCoordinates($value['southWest']['latitude'], $value['southWest']['longitude']);

            $geoBounds = new DataObject\Data\Geobounds($ne, $sw);
            $geoBounds->_setOwnerFieldname($params['fieldname'] ?? null);
            $geoBounds->_setOwner($params['owner'] ?? null);
            $geoBounds->_setOwnerLanguage($params['language'] ?? null);

            return $geoBounds;
        }

        return null;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if (!$oldValue instanceof DataObject\Data\Geobounds
            || !$newValue instanceof DataObject\Data\Geobounds) {
            return false;
        }

        $oldValue = [
            'NElongitude' => $oldValue->getNorthEast()->getLongitude(),
            'NElatitude' => $oldValue->getNorthEast()->getLatitude(),
            'SWlongitude' => $oldValue->getSouthWest()->getLongitude(),
            'SWlatitude' => $oldValue->getSouthWest()->getLatitude(),
        ];

        $newValue = [
            'NElongitude' => $newValue->getNorthEast()->getLongitude(),
            'NElatitude' => $newValue->getNorthEast()->getLatitude(),
            'SWlongitude' => $newValue->getSouthWest()->getLongitude(),
            'SWlatitude' => $newValue->getSouthWest()->getLatitude(),
        ];

        foreach ($oldValue as $key => $oValue) {
            if ($oValue !== $newValue[$key]) {
                return false;
            }
        }

        return true;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\Geobounds::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\Geobounds::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\Geobounds::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\Geobounds::class . '|null';
    }

    public function getColumnType(): array
    {
        return [
            'NElongitude' => 'double',
            'NElatitude' => 'double',
            'SWlongitude' => 'double',
            'SWlatitude' => 'double',
        ];
    }

    public function getQueryColumnType(): array
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'geobounds';
    }
}
