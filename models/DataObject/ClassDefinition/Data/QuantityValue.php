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

use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\QuantityValue\UnitConversionService;
use Pimcore\Normalizer\NormalizerInterface;

class QuantityValue extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use Model\DataObject\Traits\DefaultValueTrait;
    use Model\DataObject\Traits\DataWidthTrait;

    const DECIMAL_SIZE_DEFAULT = 64;

    const DECIMAL_PRECISION_DEFAULT = 0;

    /**
     * @internal
     *
     * @var string|int
     */
    public string|int $unitWidth;

    /**
     * @internal
     *
     * @var float|int|string|null
     */
    public string|int|null|float $defaultValue = null;

    /**
     * @internal
     *
     * @var string|null
     */
    public ?string $defaultUnit = null;

    /**
     * @internal
     *
     * @var array|null
     */
    public ?array $validUnits = null;

    /**
     * @internal
     *
     * @var bool
     */
    public bool $integer = false;

    /**
     * @internal
     *
     * @var bool
     */
    public bool $unsigned = false;

    /**
     * @internal
     *
     * @var float|null
     */
    public ?float $minValue = null;

    /**
     * @internal
     *
     * @var float|null
     */
    public ?float $maxValue = null;

    /**
     * @internal
     */
    public bool $unique = false;

    /**
     * This is the x part in DECIMAL(x, y) and denotes the total amount of digits. In MySQL this is called precision
     * but as decimalPrecision already existed to denote the amount of digits after the point (as it is called on the ExtJS
     * number field), decimalSize was chosen instead.
     *
     * @internal
     *
     * @var int|null
     */
    public ?int $decimalSize = null;

    /**
     * This is the y part in DECIMAL(x, y) and denotes amount of digits after a comma. In MySQL this is called scale. See
     * comment on decimalSize.
     *
     * @internal
     *
     * @var int|null
     */
    public ?int $decimalPrecision = null;

    /**
     * @internal
     */
    public bool $autoConvert = false;

    public function getUnitWidth(): int|string
    {
        return $this->unitWidth;
    }

    public function setUnitWidth(int|string $unitWidth): void
    {
        if (is_numeric($unitWidth)) {
            $unitWidth = (int)$unitWidth;
        }
        $this->unitWidth = $unitWidth;
    }

    public function getDefaultValue(): float|int|string|null
    {
        if ($this->defaultValue !== null) {
            return $this->toNumeric($this->defaultValue);
        }

        return null;
    }

    public function setDefaultValue(float|int|string|null $defaultValue): void
    {
        if ((string)$defaultValue !== '') {
            $this->defaultValue = $defaultValue;
        } else {
            $this->defaultValue = null;
        }
    }

    public function setValidUnits(array $validUnits): void
    {
        $this->validUnits = $validUnits;
    }

    public function getValidUnits(): array
    {
        return $this->validUnits;
    }

    public function getDefaultUnit(): ?string
    {
        return $this->defaultUnit;
    }

    public function setDefaultUnit(string $defaultUnit): void
    {
        $this->defaultUnit = $defaultUnit;
    }

    public function setInteger(bool $integer): void
    {
        $this->integer = $integer;
    }

    public function getInteger(): bool
    {
        return $this->integer;
    }

    public function setMaxValue(?float $maxValue): void
    {
        $this->maxValue = $maxValue;
    }

    public function getMaxValue(): ?float
    {
        return $this->maxValue;
    }

    public function setMinValue(?float $minValue): void
    {
        $this->minValue = $minValue;
    }

    public function getMinValue(): ?float
    {
        return $this->minValue;
    }

    public function setUnsigned(bool $unsigned): void
    {
        $this->unsigned = $unsigned;
    }

    public function getUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function getDecimalSize(): ?int
    {
        return $this->decimalSize;
    }

    public function setDecimalSize(?int $decimalSize): void
    {
        if (!is_numeric($decimalSize)) {
            $decimalSize = null;
        }

        $this->decimalSize = $decimalSize;
    }

    public function setDecimalPrecision(?int $decimalPrecision): void
    {
        if (!is_numeric($decimalPrecision)) {
            $decimalPrecision = null;
        }

        $this->decimalPrecision = $decimalPrecision;
    }

    public function getDecimalPrecision(): ?int
    {
        return $this->decimalPrecision;
    }

    public function getUnique(): bool
    {
        return $this->unique;
    }

    public function setUnique(bool $unique): void
    {
        $this->unique = (bool) $unique;
    }

    public function isAutoConvert(): bool
    {
        return $this->autoConvert;
    }

    public function setAutoConvert(bool $autoConvert): void
    {
        $this->autoConvert = (bool)$autoConvert;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType(): array
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

        return [
            'value' => 'double',
            'unit' => 'varchar(64)',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryColumnType(): array
    {
        return $this->getColumnType();
    }

    private function isDecimalType(): bool
    {
        return null !== $this->getDecimalSize() || null !== $this->getDecimalPrecision();
    }

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
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return array
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): array
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
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return Model\DataObject\Data\QuantityValue|null
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?Model\DataObject\Data\QuantityValue
    {
        $dataValue = $data[$this->getName() . '__value'];
        $dataUnit =  $data[$this->getName() . '__unit'];

        if ($dataValue !== null || $dataUnit) {
            if ($dataValue !== null && !is_numeric($dataValue)) {
                $value = $this->toNumeric($dataValue);
            } else {
                $value = $dataValue;
            }
            $quantityValue = new Model\DataObject\Data\QuantityValue($value === null ? null : (float)$value, $dataUnit);

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
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return array
     */
    public function getDataForQueryResource(mixed $data, Concrete $object = null, array $params = []): array
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return array|null
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        if ($data instanceof Model\DataObject\Data\AbstractQuantityValue) {
            return [
                'value' => $data->getValue(),
                'unit' => $data->getUnitId(),
            ];
        }

        return null;
    }

    public function getDataFromGridEditor(array $data, Concrete $object = null, array $params = []): ?Model\DataObject\Data\QuantityValue
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?Model\DataObject\Data\QuantityValue
    {
        if (strlen((string)$data['value']) > 0 || $data['unit']) {
            if (empty($data['unit']) || $data['unit'] == -1) {
                return new Model\DataObject\Data\QuantityValue($data['value'], null);
            }

            return new Model\DataObject\Data\QuantityValue($data['value'], $data['unit']);
        }

        return null;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        if ($data instanceof Model\DataObject\Data\AbstractQuantityValue) {
            $unit = '';
            if ($data->getUnitId()) {
                $unitDefinition = Model\DataObject\QuantityValue\Unit::getById($data->getUnitId());
                if ($unitDefinition) {
                    $unit = ' ' . $unitDefinition->getAbbreviation();
                }
            }

            return htmlspecialchars((string)$data->getValue() . $unit, ENT_QUOTES, 'UTF-8');
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
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
    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
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
    public function getDataForGrid(?Model\DataObject\Data\QuantityValue $data, Concrete $object = null, array $params = []): ?array
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
    public function configureOptions(): void
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

    private function toNumeric(mixed $value): float|int|string
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

    protected function doGetDefaultValue(Concrete $object, array $context = []): ?Model\DataObject\Data\QuantityValue
    {
        if ($this->getDefaultValue() || $this->getDefaultUnit()) {
            return new Model\DataObject\Data\QuantityValue($this->getDefaultValue(), $this->getDefaultUnit());
        }

        return null;
    }

    public static function __set_state(array $data): static
    {
        $obj = parent::__set_state($data);

        $obj->configureOptions();

        return $obj;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterCondition(mixed $value, string $operator, array $params = []): string
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

    public function isEqual(mixed $oldValue, mixed $newValue): bool
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

    private function prepareUnitIdForComparison(mixed $unitId): string
    {
        $unitId = (string) $unitId;
        if (empty($unitId)) {
            $unitId = '';
        }

        return $unitId;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . Model\DataObject\Data\QuantityValue::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . Model\DataObject\Data\QuantityValue::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . Model\DataObject\Data\QuantityValue::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . Model\DataObject\Data\QuantityValue::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof Model\DataObject\Data\QuantityValue) {
            return [
                'value' => $value->getValue(),
                'unitId' => $value->getUnitId(),
            ];
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?Model\DataObject\Data\QuantityValue
    {
        if (is_array($value)) {
            return new Model\DataObject\Data\QuantityValue($value['value'], $value['unitId']);
        }

        return null;
    }

    public function isEmpty(mixed $data): bool
    {
        if ($data instanceof Model\DataObject\Data\QuantityValue) {
            return empty($data->getValue()) && empty($data->getUnitId());
        }

        return parent::isEmpty($data);
    }

    public function getFieldType(): string
    {
        return 'quantityValue';
    }
}
