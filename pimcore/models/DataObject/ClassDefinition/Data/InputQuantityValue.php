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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject\Data\InputQuantityValue as InputQuantityValueDataObject;

/**
 * TODO: Refactor - this class is very similar to the parent one so probably we can try to refactor parent and have better results here also
 *
 * Class InputQuantityValue
 * @package Pimcore\Model\DataObject\ClassDefinition\Data
 */
class InputQuantityValue extends QuantityValue
{
    public $fieldtype = 'inputQuantityValue';

    /**
     * This field is extended from the parent but is off
     * (InputQuantityValue SHOULD NOT have default value)
     * For more information please check getter and setter for this field.
     *
     * @var null
     */
    public $defaultValue = null;

    /**
     * Type for the column to query
     *
     * @var array
     */
    public $queryColumnType = [
        'value' => 'varchar(255)',
        'unit'  => 'bigint(20)'
    ];

    /**
     * Type for the column
     *
     * @var array
     */
    public $columnType = [
        'value' => 'varchar(255)',
        'unit'  => 'bigint(20)'
    ];

    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\InputQuantityValue';

    /**
     * @return void
     */
    public function getDefaultValue()
    {
        return;
    }

    public function setDefaultValue($defaultValue)
    {
        return;
    }

    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data[$this->getName() . '__value'] || $data[$this->getName() . '__unit']) {
            return $this->getNewDataObject($data[$this->getName() . '__value'], $data[$this->getName() . '__unit']);
        }

        return;
    }

    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data['value'] || $data['unit']) {
            if (is_numeric($data['unit'])) {
                if ($data['unit'] == -1 || $data['unit'] == null || empty($data['unit'])) {
                    return $this->getNewDataObject($data['value'], null);
                } else {
                    return $this->getNewDataObject($data['value'], $data['unit']);
                }
            }

            return;
        }

        return;
    }

    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if ($omitMandatoryCheck) {
            return;
        }

        if ($this->getMandatory() &&
            ($data === null || $data->getValue() === null || $data->getUnitId() === null)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }

        if (!empty($data)) {
            if (!empty($data->getUnitId())) {
                if (!is_numeric($data->getUnitId())) {
                    throw new Model\Element\ValidationException('Unit id has to be empty or numeric ' . $data->getUnitId());
                }
            }
        }
    }

    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $values = explode('_', $importValue);

        $value = null;
        if ($values[0] && $values[1]) {
            $number = (float) str_replace(',', '.', $values[0]);
            $value = $this->getNewDataObject($number, $values[1]);
        }

        return $value;
    }

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
                    return $this->getNewDataObject($value, $unitId);
                } elseif (!$unit && is_null($value['unit'])) {
                    return $this->getNewDataObject($value);
                } else {
                    throw new \Exception(get_class($this).': cannot get values from web service import - unit id and unit abbreviation do not match with local database');
                }
            } else {
                throw new \Exception(get_class($this).': cannot get values from web service import - invalid data');
            }
        }
    }

    public function unmarshal($value, $object = null, $params = [])
    {
        if ($params['blockmode'] && is_array($value)) {
            return $this->getNewDataObject($value['value'], $value['value2']);
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

    /**
     * @param string $value
     * @param int $unitId
     * @return InputQuantityValueDataObject
     */
    protected function getNewDataObject($value = null, $unitId = null)
    {
        return new InputQuantityValueDataObject($value, $unitId);
    }
}