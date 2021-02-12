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
 *
 * @package Pimcore\Model\DataObject\ClassDefinition\Data
 */
class InputQuantityValue extends QuantityValue
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    /**
     * @var string
     */
    public $fieldtype = 'inputQuantityValue';

    /**
     * Type for the column to query
     *
     * @var array
     */
    public $queryColumnType = [
        'value' => 'varchar(255)',
        'unit' => 'varchar(50)',
    ];

    /**
     * Type for the column
     *
     * @var array
     */
    public $columnType = [
        'value' => 'varchar(255)',
        'unit' => 'varchar(50)',
    ];

    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\InputQuantityValue';

    /**
     * @param array $data
     * @param Model\DataObject\Concrete|null $object
     * @param array $params
     *
     * @return InputQuantityValueDataObject|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data[$this->getName() . '__value'] || $data[$this->getName() . '__unit']) {
            $dataObject = $this->getNewDataObject($data[$this->getName() . '__value'], $data[$this->getName() . '__unit']);

            if (isset($params['owner'])) {
                $dataObject->setOwner($params['owner'], $params['fieldname'], $params['language'] ?? null);
            }

            return $dataObject;
        }

        return null;
    }

    /**
     * @param array $data
     * @param Model\DataObject\Concrete|null $object
     * @param array $params
     *
     * @return InputQuantityValueDataObject|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data['value'] || $data['unit']) {
            if ($data['unit']) {
                if ($data['unit'] == -1 || $data['unit'] == null || empty($data['unit'])) {
                    return $this->getNewDataObject($data['value'], null);
                }

                return $this->getNewDataObject($data['value'], $data['unit']);
            }
        }

        return null;
    }

    /**
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws Model\Element\ValidationException
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if ($omitMandatoryCheck) {
            return;
        }

        if ($this->getMandatory() &&
            ($data === null || $data->getValue() === null || $data->getUnitId() === null)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    /**
     * @param string $importValue
     * @param null|Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return null|InputQuantityValueDataObject
     */
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

    /**
     * @deprecated
     *
     * @param mixed $value
     * @param Model\DataObject\Concrete|null $object
     * @param array $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @return null|InputQuantityValueDataObject
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

    /**
     * @param mixed $value
     * @param Model\DataObject\Concrete|null $object
     * @param array $params
     *
     * @return array|mixed|null|InputQuantityValueDataObject
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        if (($params['blockmode'] ?? false) && is_array($value)) {
            return $this->getNewDataObject($value['value'], $value['value2']);
        } elseif ($params['simple'] ?? false) {
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
     *
     * @return InputQuantityValueDataObject
     */
    protected function getNewDataObject($value = null, $unitId = null)
    {
        return new InputQuantityValueDataObject($value, $unitId);
    }
}
