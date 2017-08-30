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

class QuantityValue extends Model\DataObject\ClassDefinition\Data
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'quantityValue';

    /**
     * @var float
     */
    public $width;

    /**
     * @var float
     */
    public $defaultValue;

    /**
     * @var string
     */
    public $defaultUnit;

    /**
     * @var array
     */
    public $validUnits;

    /**
     * Type for the column to query
     *
     * @var int
     */
    public $queryColumnType = [
        'value' => 'double',
        'unit' => 'bigint(20)'
    ];

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = [
        'value' => 'double',
        'unit' => 'bigint(20)'
    ];

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\QuantityValue';

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue !== null) {
            return (float) $this->defaultValue;
        }
    }

    /**
     * @param int $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        if (strlen(strval($defaultValue)) > 0) {
            $this->defaultValue = $defaultValue;
        }
    }

    /**
     * @param  array $validUnits
     */
    public function setValidUnits($validUnits)
    {
        $this->validUnits = $validUnits;
    }

    /**
     * @return array
     */
    public function getValidUnits()
    {
        return $this->validUnits;
    }

    /**
     * @return string
     */
    public function getDefaultUnit()
    {
        return $this->defaultUnit;
    }

    /**
     * @param string $defaultUnit
     */
    public function setDefaultUnit($defaultUnit)
    {
        $this->defaultUnit = $defaultUnit;
    }

    /**
     * @see Object_Class_Data::getDataForResource
     *
     * @param float $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof \Pimcore\Model\DataObject\Data\QuantityValue) {
            return [
                $this->getName() . '__value' => $data->getValue(),
                $this->getName() . '__unit' => $data->getUnitId()
            ];
        }

        return [
            $this->getName() . '__value' => null,
            $this->getName() . '__unit' => null
        ];
    }

    /**
     * @see Object_Class_Data::getDataFromResource
     *
     * @param float $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data[$this->getName() . '__value'] || $data[$this->getName() . '__unit']) {
            return new  \Pimcore\Model\DataObject\Data\QuantityValue($data[$this->getName() . '__value'], $data[$this->getName() . '__unit']);
        }

        return;
    }

    /**
     * @see Object_Class_Data::getDataForQueryResource
     *
     * @param float $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data);
    }

    /**
     * @see Object_Class_Data::getDataForEditmode
     *
     * @param float $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof  \Pimcore\Model\DataObject\Data\QuantityValue) {
            return [
                'value' => $data->getValue(),
                'unit' => $data->getUnitId()
            ];
        }

        return;
    }

    /**
     * @param float $data
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Object_Class_Data::getDataFromEditmode
     *
     * @param float $data
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data['value'] || $data['unit']) {
            if (is_numeric($data['unit'])) {
                if ($data['unit'] == -1 || $data['unit'] == null || empty($data['unit'])) {
                    return new \Pimcore\Model\DataObject\Data\QuantityValue($data['value'], null);
                } else {
                    return new \Pimcore\Model\DataObject\Data\QuantityValue($data['value'], $data['unit']);
                }
            }

            return;
        }

        return;
    }

    /**
     * @see Object_Class_Data::getVersionPreview
     *
     * @param float $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof \Pimcore\Model\DataObject\Data\QuantityValue) {
            $unit = '';
            if ($data->getUnitId()) {
                $unitDefinition = Model\DataObject\QuantityValue\Unit::getById($data->getUnitId());
                if ($unitDefinition) {
                    $unit = ' ' . $unitDefinition->getAbbreviation();
                }
            }

            return $data->getValue() . $unit;
        }

        return '';
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if ($omitMandatoryCheck) {
            return;
        }

        if ($this->getMandatory() &&
            ($data === null || $data->getValue() === null || $data->getUnitId() === null)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (!empty($data)) {
            $value = $data->getValue();
            if ((!empty($value) && !is_numeric($data->getValue()))) {
                throw new Model\Element\ValidationException('Invalid dimension unit data ' . $this->getName());
            }

            if (!empty($data->getUnitId())) {
                if (!is_numeric($data->getUnitId())) {
                    throw new Model\Element\ValidationException('Unit id has to be empty or numeric ' . $data->getUnitId());
                }
            }
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param Model\DataObject\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $key = $this->getName();
        $getter = 'get'.ucfirst($key);
        if ($object->$getter() instanceof \Pimcore\Model\DataObject\Data\QuantityValue) {
            return $object->$getter()->getValue() . '_' . $object->$getter()->getUnitId();
        } else {
            return null;
        }
    }

    /**
     * fills object field data values from CSV Import String
     *
     * @param string $importValue
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $values = explode('_', $importValue);

        $value = null;
        if ($values[0] && $values[1]) {
            $number = (float) str_replace(',', '.', $values[0]);
            $value = new  \Pimcore\Model\DataObject\Data\QuantityValue($number, $values[1]);
        }

        return $value;
    }

    /**
     * display the quantity value field data in the grid
     *
     * @param $data
     * @param null $object
     * @param array $params
     *
     * @return array
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        if ($data instanceof  \Pimcore\Model\DataObject\Data\QuantityValue) {
            $unit = $data->getUnit();
            $unitAbbreviation = '';

            if ($unit instanceof Model\DataObject\QuantityValue\Unit) {
                $unitAbbreviation = $unit->getAbbreviation();
            }

            return [
                'value' => $data->getValue(),
                'unit' => $unit ? $unit->getId() : null,
                'unitAbbr' => $unitAbbreviation
            ];
        }

        return;
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

        if ($data instanceof \Pimcore\Model\DataObject\Data\QuantityValue) {
            return [
                'value' => $data->getValue(),
                'unit' => $data->getUnitId(),
                'unitAbbreviation' => is_object($data->getUnit()) ? $data->getUnit()->getAbbreviation() : ''
            ];
        } else {
            return null;
        }
    }

    /**
     * converts data to be imported via webservices
     *
     * @param mixed $value
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     * @param $idMapper
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        if (empty($value)) {
            return null;
        } else {
            $value = (array) $value;
            if (array_key_exists('value', $value) && array_key_exists('unit', $value) && array_key_exists('unitAbbreviation', $value)) {
                $unitId = $value['unit'];
                if ($idMapper) {
                    $unitId = $idMapper->getMappedId('unit', $unitId);
                }

                $unit = Model\DataObject\QuantityValue\Unit::getById($unitId);
                if ($unit && $unit->getAbbreviation() == $value['unitAbbreviation']) {
                    return new \Pimcore\Model\DataObject\Data\QuantityValue($value['value'], $unitId);
                } elseif (!$unit && is_null($value['unit'])) {
                    return new \Pimcore\Model\DataObject\Data\QuantityValue($value['value']);
                } else {
                    throw new \Exception(get_class($this).': cannot get values from web service import - unit id and unit abbreviation do not match with local database');
                }
            } else {
                throw new \Exception(get_class($this).': cannot get values from web service import - invalid data');
            }
        }
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
        if ($params['blockmode'] && $value instanceof Model\DataObject\Data\QuantityValue) {
            return [
                'value' => $value->getValue(),
                'value2' => $value->getUnitId()
            ];
        } elseif ($params['simple']) {
            if (is_array($value)) {
                return [$value[$this->getName() . '__value'], $value[$this->getName() . '__unit']];
            } else {
                return null;
            }
        } else {
            if (is_array($value)) {
                return [
                    'value' => $value[$this->getName() . '__value'],
                    'value2' => $value[$this->getName() . '__unit']
                ];
            } else {
                return [
                    'value' => null,
                    'value2' => null
                ];
            }
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
        if ($params['blockmode'] && is_array($value)) {
            return new Model\DataObject\Data\QuantityValue($value['value'], $value['value2']);
        } elseif ($params['simple']) {
            return $value;
        } elseif (is_array($value)) {
            return [
                $this->getName() . '__value' => $value['value'],
                $this->getName() . '__unit' => $value['value2'],

            ];
        } else {
            return null;
        }
    }

    public function configureOptions()
    {
        if (!$this->validUnits) {
            $list = new \Pimcore\Model\DataObject\QuantityValue\Unit\Listing();
            $units = $list->getUnits();
            if (is_array($units)) {
                $this->validUnits = [];
                /** @var $unit Model\DataObject\QuantityValue\Unit */
                foreach ($units as $unit) {
                    $this->validUnits[] = $unit->getId();
                }
            }
        }
    }

    /**
     * @param $data
     *
     * @return static
     */
    public static function __set_state($data)
    {
        $obj = parent::__set_state($data);
        $obj->configureOptions();

        return $obj;
    }
}
