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
use Pimcore\Tool\Serialize;

class Geopolygon extends AbstractGeo implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        return Serialize::serialize($data);
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        $isEmpty = true;

        if ($data) {
            $valid = true;

            if (!is_array($data)) {
                $valid = false;
            } else {
                foreach ($data as $point) {
                    if (!$point instanceof DataObject\Data\GeoCoordinates) {
                        $valid = false;

                        break;
                    }
                }
            }

            if (!$valid) {
                throw new ValidationException('Expected an array of Geopoint');
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
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        return Serialize::unserialize($data);
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): string
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
        if (!empty($data)) {
            if (is_array($data)) {
                $points = [];
                foreach ($data as $point) {
                    $points[] = [
                        'latitude' => $point->getLatitude(),
                        'longitude' => $point->getLongitude(),
                    ];
                }

                return $points;
            }
        }

        return null;
    }

    /**
     *
     * @return DataObject\Data\GeoCoordinates[]|null
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        if (is_array($data)) {
            $points = [];
            foreach ($data as $point) {
                $points[] = new DataObject\Data\GeoCoordinates($point['latitude'], $point['longitude']);
            }

            return $points;
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
        return $this->getDiffVersionPreview($data, $object, $params);
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
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

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param DataObject\Concrete|null $object
     *
     */
    public function getDiffVersionPreview(?array $data, Concrete $object = null, array $params = []): string
    {
        $line = [];

        if (is_array($data)) {
            foreach ($data as $point) {
                $line[] = $point->getLatitude() . ',' . $point->getLongitude();
            }
        }

        return implode(' ', $line);
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if (!is_array($oldValue) || !is_array($newValue)
        || count($oldValue) != count($newValue)) {
            return false;
        }

        $fd = new Geopoint();

        $oldValue = array_values($oldValue);
        $newValue = array_values($newValue);

        foreach ($oldValue as $p => $point) {
            if (!$fd->isEqual($point, $newValue[$p])) {
                return false;
            }
        }

        return true;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?array';
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?array';
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\'.DataObject\Data\GeoCoordinates::class.'[]|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\'.DataObject\Data\GeoCoordinates::class.'[]|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $points = [];
            $fd = new Geopoint();
            foreach ($value as $p) {
                $points[] = $fd->normalize($p, $params);
            }

            return $points;
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?array
    {
        if (is_array($value)) {
            $result = [];
            $fd = new Geopoint();
            foreach ($value as $point) {
                $result[] = $fd->denormalize($point, $params);
            }

            return $result;
        }

        return null;
    }

    public function getColumnType(): string
    {
        return 'longtext';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'geopolygon';
    }
}
