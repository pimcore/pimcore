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

use InvalidArgumentException;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;

class QuantityValue extends AbstractQuantityValue
{
    const DECIMAL_SIZE_DEFAULT = 64;

    const DECIMAL_PRECISION_DEFAULT = 0;

    /**
     * @internal
     */
    public float|int|string|null $defaultValue = null;

    /**
     * @internal
     */
    public bool $integer = false;

    /**
     * @internal
     */
    public bool $unsigned = false;

    /**
     * @internal
     */
    public ?float $minValue = null;

    /**
     * @internal
     */
    public ?float $maxValue = null;

    /**
     * This is the x part in DECIMAL(x, y) and denotes the total amount of digits. In MySQL this is called precision
     * but as decimalPrecision already existed to denote the amount of digits after the point (as it is called on the ExtJS
     * number field), decimalSize was chosen instead.
     *
     * @internal
     */
    public ?int $decimalSize = null;

    /**
     * This is the y part in DECIMAL(x, y) and denotes amount of digits after a comma. In MySQL this is called scale. See
     * comment on decimalSize.
     *
     * @internal
     */
    public ?int $decimalPrecision = null;

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
            throw new InvalidArgumentException('Decimal precision must be a value between 1 and 65');
        }

        if ($scale < 0 || $scale > 30 || $scale > $precision) {
            throw new InvalidArgumentException('Decimal scale must be a value between 0 and 30');
        }

        if ($scale > $precision) {
            throw new InvalidArgumentException(sprintf(
                'Decimal scale can\'t be larger than precision (%d)',
                $precision
            ));
        }

        return sprintf('DECIMAL(%d, %d)', $precision, $scale);
    }

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

            if ($this->getInteger() && str_contains((string)$value, '.')) {
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

    private function toNumeric(mixed $value): float|int|string
    {
        $value = str_replace(',', '.', (string) $value);

        if ($this->isDecimalType()) {
            return $value;
        }

        if (!str_contains($value, '.')) {
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

    public function denormalize(mixed $value, array $params = []): ?Model\DataObject\Data\QuantityValue
    {
        if (is_array($value)) {
            return new Model\DataObject\Data\QuantityValue($value['value'], $value['unitId']);
        }

        return null;
    }

    public function getFieldType(): string
    {
        return 'quantityValue';
    }
}
