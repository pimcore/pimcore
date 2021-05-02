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

use Pimcore\Cache;
use Pimcore\Cache\Runtime;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\QuantityValue\UnitConversionService;
use Pimcore\Normalizer\NormalizerInterface;

class QuantityValue extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    use Model\DataObject\Traits\DefaultValueTrait;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'quantityValue';

    /**
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * @internal
     *
     * @var string|int
     */
    public $unitWidth;

    /**
     * @internal
     *
     * @var float
     */
    public $defaultValue;

    /**
     * @internal
     *
     * @var string
     */
    public $defaultUnit;

    /**
     * @internal
     *
     * @var array
     */
    public $validUnits;

    /**
     * @internal
     *
     * @var int
     */
    public $decimalPrecision;

    /**
     * @internal
     *
     * @var bool
     */
    public $autoConvert;

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var array
     */
    public $queryColumnType = [
        'value' => 'double',
        'unit' => 'varchar(50)',
    ];

    /**
     * Type for the column
     *
     * @internal
     *
     * @var array
     */
    public $columnType = [
        'value' => 'double',
        'unit' => 'varchar(50)',
    ];

    /**
     * @return string|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string|int $width
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;
    }

    /**
     * @return string|int
     */
    public function getUnitWidth()
    {
        return $this->unitWidth;
    }

    /**
     * @param string|int $unitWidth
     */
    public function setUnitWidth($unitWidth)
    {
        if (is_numeric($unitWidth)) {
            $unitWidth = (int)$unitWidth;
        }
        $this->unitWidth = $unitWidth;
    }

    /**
     * @return float|null
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue !== null) {
            return (float) $this->defaultValue;
        }

        return null;
    }

    /**
     * @param int $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        if (strlen((string)$defaultValue) > 0) {
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
     * @return int
     */
    public function getDecimalPrecision()
    {
        return $this->decimalPrecision;
    }

    /**
     * @param int $decimalPrecision
     */
    public function setDecimalPrecision($decimalPrecision)
    {
        $this->decimalPrecision = $decimalPrecision;
    }

    /**
     * @return bool
     */
    public function isAutoConvert(): bool
    {
        return $this->autoConvert;
    }

    /**
     * @param bool $autoConvert
     */
    public function setAutoConvert($autoConvert)
    {
        $this->autoConvert = (bool)$autoConvert;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param Model\DataObject\Data\QuantityValue $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        $data = $this->handleDefaultValue($data, $object, $params);

        if ($data instanceof Model\DataObject\Data\AbstractQuantityValue) {
            return [
                $this->getName() . '__value' => $data->getValue(),
                $this->getName() . '__unit' => $data->getUnitId(),
            ];
        }

        return [
            $this->getName() . '__value' => null,
            $this->getName() . '__unit' => null,
        ];
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param array $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Model\DataObject\Data\QuantityValue|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data[$this->getName() . '__value'] !== null || $data[$this->getName() . '__unit']) {
            $value = $data[$this->getName() . '__value'];
            $quantityValue = new Model\DataObject\Data\QuantityValue($value !== null ? (float)$value : null, $data[$this->getName() . '__unit']);

            if (isset($params['owner'])) {
                $quantityValue->_setOwner($params['owner']);
                $quantityValue->_setOwnerFieldname($params['fieldname']);
                $quantityValue->_setOwnerLanguage($params['language'] ?? null);
            }

            return $quantityValue;
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param Model\DataObject\Data\QuantityValue $data
     * @param null|Model\DataObject\Concrete $object
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
     * @param Model\DataObject\Data\AbstractQuantityValue|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof Model\DataObject\Data\AbstractQuantityValue) {
            return [
                'value' => $data->getValue(),
                'unit' => $data->getUnitId(),
            ];
        }

        return null;
    }

    /**
     * @param float $data
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Model\DataObject\Data\QuantityValue|null
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param array $data
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Model\DataObject\Data\QuantityValue|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if (strlen($data['value']) > 0 || $data['unit']) {
            if ($data['unit']) {
                if ($data['unit'] == -1 || $data['unit'] == null || empty($data['unit'])) {
                    return new Model\DataObject\Data\QuantityValue($data['value'], null);
                }

                return new Model\DataObject\Data\QuantityValue($data['value'], $data['unit']);
            }
        }

        return null;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param Model\DataObject\Data\QuantityValue|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof Model\DataObject\Data\AbstractQuantityValue) {
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
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
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
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof Model\DataObject\Data\AbstractQuantityValue) {
            return (string) $data;
        }

        return '';
    }

    /**
     * display the quantity value field data in the grid
     *
     * @param Model\DataObject\Data\QuantityValue|null $data
     * @param Model\DataObject\Concrete|null $object
     * @param array $params
     *
     * @return array|null
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        if ($data instanceof  Model\DataObject\Data\AbstractQuantityValue) {
            $unit = $data->getUnit();
            $unitAbbreviation = '';

            if ($unit instanceof Model\DataObject\QuantityValue\Unit) {
                $unitAbbreviation = $unit->getAbbreviation();
            }

            return [
                'value' => $data->getValue(),
                'unit' => $unit ? $unit->getId() : null,
                'unitAbbr' => $unitAbbreviation,
            ];
        }

        return null;
    }

    /**
     * @internal
     */
    public function configureOptions()
    {
        if (!$this->validUnits) {
            $table = null;
            try {
                if (Runtime::isRegistered(Model\DataObject\QuantityValue\Unit::CACHE_KEY)) {
                    $table = Runtime::get(Model\DataObject\QuantityValue\Unit::CACHE_KEY);
                }

                if (!is_array($table)) {
                    $table = Cache::load(Model\DataObject\QuantityValue\Unit::CACHE_KEY);
                    if (is_array($table)) {
                        Runtime::set(Model\DataObject\QuantityValue\Unit::CACHE_KEY, $table);
                    }
                }

                if (!is_array($table)) {
                    $table = [];
                    $list = new Model\DataObject\QuantityValue\Unit\Listing();
                    $list->setOrderKey(['baseunit', 'factor', 'abbreviation']);
                    $list->setOrder(['ASC', 'ASC', 'ASC']);
                    foreach ($list->getUnits() as $item) {
                        $table[$item->getId()] = $item;
                    }

                    Cache::save($table, Model\DataObject\QuantityValue\Unit::CACHE_KEY, [], null, 995, true);
                    Runtime::set(Model\DataObject\QuantityValue\Unit::CACHE_KEY, $table);
                }
            } catch (\Exception $e) {
                Logger::error($e);
            }

            if (is_array($table)) {
                $this->validUnits = [];
                /** @var Model\DataObject\QuantityValue\Unit $unit */
                foreach ($table as $unit) {
                    $this->validUnits[] = $unit->getId();
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetDefaultValue($object, $context = [])
    {
        if ($this->getDefaultValue() || $this->getDefaultUnit()) {
            return new Model\DataObject\Data\QuantityValue($this->getDefaultValue(), $this->getDefaultUnit());
        }

        return null;
    }

    /**
     * @param array $data
     *
     * @return static
     */
    public static function __set_state($data)
    {
        $obj = parent::__set_state($data);

        $obj->configureOptions();

        return $obj;
    }

    public function getFilterCondition($value, $operator, $params = [])
    {
        /** @var UnitConversionService $converter */
        $converter = \Pimcore::getContainer()->get(UnitConversionService::class);

        $filterValue = $value[0];
        $filterUnit = Model\DataObject\QuantityValue\Unit::getById($value[1]);

        if (!$filterUnit instanceof Model\DataObject\QuantityValue\Unit) {
            return '0';
        }

        $filterQuantityValue = new Model\DataObject\Data\QuantityValue($filterValue, $filterUnit->getId());

        $baseUnit = $filterUnit->getBaseunit() ?? $filterUnit;

        $unitListing = new Model\DataObject\QuantityValue\Unit\Listing();
        $unitListing->setCondition('baseunit='.Db::get()->quote($baseUnit->getId()).' OR id='.Db::get()->quote($filterUnit->getId()));

        $conditions = [];
        foreach ($unitListing->load() as $unit) {
            $convertedQuantityValue = $converter->convert($filterQuantityValue, $unit);

            $conditions[] = '('.
                $this->getFilterConditionExt(
                    $convertedQuantityValue->getValue(),
                    $operator,
                    ['name' => $this->getName().'__value']
                ).
                ' AND '.
                $this->getFilterConditionExt(
                    $convertedQuantityValue->getUnitId(),
                    '=',
                    ['name' => $this->getName().'__unit']
                ).
            ')';
        }

        return implode(' OR ', $conditions);
    }

    /**
     * @param mixed $oldValue
     * @param mixed $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if (!$oldValue instanceof Model\DataObject\Data\AbstractQuantityValue) {
            return false;
        }

        if (!$newValue instanceof Model\DataObject\Data\AbstractQuantityValue) {
            return false;
        }

        return $oldValue->getValue() === $newValue->getValue()
            && $this->prepareUnitIdForComparison($oldValue->getUnitId()) === $this->prepareUnitIdForComparison($newValue->getUnitId());
    }

    /**
     * @param mixed $unitId
     *
     * @return string
     */
    private function prepareUnitIdForComparison($unitId): string
    {
        $unitId = (string) $unitId;
        if (empty($unitId)) {
            $unitId = '';
        }

        return $unitId;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . Model\DataObject\Data\QuantityValue::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . Model\DataObject\Data\QuantityValue::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . Model\DataObject\Data\QuantityValue::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . Model\DataObject\Data\QuantityValue::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value instanceof Model\DataObject\Data\QuantityValue) {
            return [
                'value' => $value->getValue(),
                'unitId' => $value->getUnitId(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if (is_array($value)) {
            return new Model\DataObject\Data\QuantityValue($value['value'], $value['unitId']);
        }

        return null;
    }
}
