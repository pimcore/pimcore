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

use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\QuantityValue\UnitConversionService;
use Pimcore\Normalizer\NormalizerInterface;

class QuantityValue extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use Extension\ColumnType {
        getColumnType as public genericGetColumnType;
    }
    use Extension\QueryColumnType {
        getQueryColumnType as public genericGetQueryColumnType;
    }
    use Model\DataObject\Traits\DefaultValueTrait;

    const DECIMAL_SIZE_DEFAULT = 64;

    const DECIMAL_PRECISION_DEFAULT = 0;

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
     * @var float|int|string|null
     */
    public $defaultValue;

    /**
     * @internal
     *
     * @var string|null
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
     * @var bool
     */
    public $integer = false;

    /**
     * @internal
     *
     * @var bool
     */
    public $unsigned = false;

    /**
     * @internal
     *
     * @var float|null
     */
    public $minValue;

    /**
     * @internal
     *
     * @var float|null
     */
    public $maxValue;

    /**
     * @internal
     *
     * @var bool
     */
    public $unique;

    /**
     * This is the x part in DECIMAL(x, y) and denotes the total amount of digits. In MySQL this is called precision
     * but as decimalPrecision already existed to denote the amount of digits after the point (as it is called on the ExtJS
     * number field), decimalSize was chosen instead.
     *
     * @internal
     *
     * @var int|null
     */
    public $decimalSize;

    /**
     * This is the y part in DECIMAL(x, y) and denotes amount of digits after a comma. In MySQL this is called scale. See
     * comment on decimalSize.
     *
     * @internal
     *
     * @var int|null
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
        'unit' => 'varchar(64)',
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
        'unit' => 'varchar(64)',
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
     * @return float|int|string|null
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue !== null) {
            return $this->toNumeric($this->defaultValue);
        }

        return null;
    }

    /**
     * @param float|int|string|null $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        if ((string)$defaultValue !== '') {
            $this->defaultValue = $defaultValue;
        } else {
            $this->defaultValue = null;
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
     * @return string|null
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
     * @param bool $integer
     */
    public function setInteger($integer)
    {
        $this->integer = $integer;
    }

    /**
     * @return bool
     */
    public function getInteger()
    {
        return $this->integer;
    }

    /**
     * @param float|null $maxValue
     */
    public function setMaxValue($maxValue)
    {
        $this->maxValue = $maxValue;
    }

    /**
     * @return float|null
     */
    public function getMaxValue()
    {
        return $this->maxValue;
    }

    /**
     * @param float|null $minValue
     */
    public function setMinValue($minValue)
    {
        $this->minValue = $minValue;
    }

    /**
     * @return float|null
     */
    public function getMinValue()
    {
        return $this->minValue;
    }

    /**
     * @param bool $unsigned
     */
    public function setUnsigned($unsigned)
    {
        $this->unsigned = $unsigned;
    }

    /**
     * @return bool
     */
    public function getUnsigned()
    {
        return $this->unsigned;
    }

    /**
     * @return int|null
     */
    public function getDecimalSize()
    {
        return $this->decimalSize;
    }

    /**
     * @param int|null $decimalSize
     */
    public function setDecimalSize($decimalSize)
    {
        if (!is_numeric($decimalSize)) {
            $decimalSize = null;
        }

        $this->decimalSize = $decimalSize;
    }

    /**
     * @param int|null $decimalPrecision
     */
    public function setDecimalPrecision($decimalPrecision)
    {
        if (!is_numeric($decimalPrecision)) {
            $decimalPrecision = null;
        }

        $this->decimalPrecision = $decimalPrecision;
    }

    /**
     * @return int|null
     */
    public function getDecimalPrecision()
    {
        return $this->decimalPrecision;
    }

    /**
     * @return bool
     */
    public function getUnique()
    {
        return $this->unique;
    }

    /**
     * @param bool $unique
     */
    public function setUnique($unique)
    {
        $this->unique = (bool) $unique;
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
     * {@inheritdoc}
     */
    public function getColumnType()
    {
        if ($this->getInteger()) {
            return [
                'value' => 'bigint(20)',
                'unit' => 'varchar(64)',
            ];
        }

        if ($this->isDecimalType()) {
            return [
                'value' => $this->buildDecimalColumnType(),
                'unit' => 'varchar(64)',
            ];
        }

        return $this->genericGetColumnType();
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryColumnType()
    {
        if ($this->getInteger()) {
            return [
                'value' => 'bigint(20)',
                'unit' => 'varchar(64)',
            ];
        }

        if ($this->isDecimalType()) {
            return [
                'value' => $this->buildDecimalColumnType(),
                'unit' => 'varchar(64)',
            ];
        }

        return $this->genericGetQueryColumnType();
    }

    /**
     * @return bool
     */
    private function isDecimalType(): bool
    {
        return null !== $this->getDecimalSize() || null !== $this->getDecimalPrecision();
    }

    /**
     * @return string
     */
    private function buildDecimalColumnType(): string
    {
        // decimalPrecision already existed in earlier versions to denote the amount of digits after the
        // comma (and is used in ExtJS). To avoid migrations, decimalSize was chosen to denote the total amount
        // of supported digits despite the confusing naming.
        //
        // The two properties used in the class definition translate to the following MySQL naming:
        //
        // DECIMAL(precision, scale) = DECIMAL(decimalSize, decimalPrecision)

        // these are named after what MySQL expects - DECIMAL(precision, scale)
        $precision = self::DECIMAL_SIZE_DEFAULT;
        $scale = self::DECIMAL_PRECISION_DEFAULT;

        if (null !== $this->decimalSize) {
            $precision = (int)$this->decimalSize;
        }

        if (null !== $this->decimalPrecision) {
            $scale = (int)$this->decimalPrecision;
        }

        if ($precision < 1 || $precision > 65) {
            throw new \InvalidArgumentException('Decimal precision must be a value between 1 and 65');
        }

        if ($scale < 0 || $scale > 30 || $scale > $precision) {
            throw new \InvalidArgumentException('Decimal scale must be a value between 0 and 30');
        }

        if ($scale > $precision) {
            throw new \InvalidArgumentException(sprintf(
                'Decimal scale can\'t be larger than precision (%d)',
                $precision
            ));
        }

        return sprintf('DECIMAL(%d, %d)', $precision, $scale);
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
            if (!is_numeric($data[$this->getName() . '__value'])) {
                $value = $this->toNumeric($data[$this->getName() . '__value']);
            } else {
                $value = $data[$this->getName() . '__value'];
            }
            $quantityValue = new Model\DataObject\Data\QuantityValue((float) $value, $data[$this->getName() . '__unit']);

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
     * @param array $data
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
            if ($data['unit'] == -1 || $data['unit'] == null || empty($data['unit'])) {
                return new Model\DataObject\Data\QuantityValue($data['value'], null);
            }

            return new Model\DataObject\Data\QuantityValue($data['value'], $data['unit']);
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

            return htmlspecialchars($data->getValue() . $unit, ENT_QUOTES, 'UTF-8');
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if (
            !$omitMandatoryCheck
            && $this->getMandatory()
            && ($data === null || $data->getValue() === null || $data->getUnitId() === null)
        ) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if ($data !== null && !$this->isEmpty($data->getValue()) && !is_numeric($data->getValue())) {
            throw new Model\Element\ValidationException('field ['.$this->getName().' ] - invalid numeric data [' . $data->getValue() . '] ');
        }

        if (!empty($data) && !$omitMandatoryCheck) {
            $value = $data->getValue();
            if ((!empty($value) && !is_numeric($data->getValue()))) {
                throw new Model\Element\ValidationException('Invalid dimension unit data ' . $this->getName());
            }
        }

        if ($data !== null && !$this->isEmpty($data->getValue())) {
            $value = $this->toNumeric($data->getValue());

            if ($value >= PHP_INT_MAX) {
                throw new Model\Element\ValidationException('Value exceeds PHP_INT_MAX please use an input data type instead of numeric!');
            }

            if ($this->getInteger() && strpos((string) $value, '.') !== false) {
                throw new Model\Element\ValidationException('Value in field [ '.$this->getName().' ] is not an integer');
            }

            if ($this->getMinValue() !== null && $this->getMinValue() > $value) {
                throw new Model\Element\ValidationException('Value in field [ '.$this->getName().' ] is not at least ' . $this->getMinValue());
            }

            if ($this->getMaxValue() !== null && $value > $this->getMaxValue()) {
                throw new Model\Element\ValidationException('Value in field [ '.$this->getName().' ] is bigger than ' . $this->getMaxValue());
            }

            if ($this->getUnsigned() && $value < 0) {
                throw new Model\Element\ValidationException('Value in field [ '.$this->getName().' ] is not unsigned (bigger than 0)');
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
                if (RuntimeCache::isRegistered(Model\DataObject\QuantityValue\Unit::CACHE_KEY)) {
                    $table = RuntimeCache::get(Model\DataObject\QuantityValue\Unit::CACHE_KEY);
                }

                if (!is_array($table)) {
                    $table = Cache::load(Model\DataObject\QuantityValue\Unit::CACHE_KEY);
                    if (is_array($table)) {
                        RuntimeCache::set(Model\DataObject\QuantityValue\Unit::CACHE_KEY, $table);
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
                    RuntimeCache::set(Model\DataObject\QuantityValue\Unit::CACHE_KEY, $table);
                }
            } catch (\Exception $e) {
                Logger::error((string) $e);
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
     * @param mixed $value
     *
     * @return float|int|string
     */
    private function toNumeric($value)
    {
        $value = str_replace(',', '.', (string) $value);

        if ($this->isDecimalType()) {
            return $value;
        }

        if (strpos($value, '.') === false) {
            return (int) $value;
        }

        return (float) $value;
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

    /**
     * {@inheritdoc}
     */
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

        return $this->toNumeric($oldValue->getValue()) === $this->toNumeric($newValue->getValue())
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

    /**
     * {@inheritdoc}
     */
    public function isEmpty($data)
    {
        if ($data instanceof Model\DataObject\Data\QuantityValue) {
            return empty($data->getValue()) && empty($data->getUnitId());
        }

        return parent::isEmpty($data);
    }
}
