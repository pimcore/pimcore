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
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

class Numeric extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface, PreSetDataInterface
{
    use DataObject\Traits\DataWidthTrait;
    use Model\DataObject\Traits\DefaultValueTrait;
    use Model\DataObject\Traits\SimpleNormalizerTrait;
    use Model\DataObject\Traits\SimpleComparisonTrait;

    const DECIMAL_SIZE_DEFAULT = 64;

    const DECIMAL_PRECISION_DEFAULT = 0;

    public static array $validFilterOperators = [
        '=',
        'IS',
        'IS NOT',
        '!=',
        '<',
        '>',
        '>=',
        '<=',
    ];

    /**
     * @internal
     *
     */
    public string|int|null|float $defaultValue = null;

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
     *
     */
    public ?float $minValue = null;

    /**
     * @internal
     *
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
     */
    public ?int $decimalSize = null;

    /**
     * This is the y part in DECIMAL(x, y) and denotes amount of digits after a comma. In MySQL this is called scale. See
     * comment on decimalSize.
     *
     * @internal
     *
     */
    public ?int $decimalPrecision = null;

    private function getPhpdocType(): string
    {
        if ($this->getInteger()) {
            return 'int';
        }

        if ($this->isDecimalType()) {
            return 'string';
        }

        return 'float';
    }

    public function getDefaultValue(): float|int|string|null
    {
        if ($this->defaultValue !== null) {
            return $this->toNumeric($this->defaultValue);
        }

        return null;
    }

    /**
     * @return $this
     */
    public function setDefaultValue(float|int|string|null $defaultValue): static
    {
        if ((string)$defaultValue !== '') {
            $this->defaultValue = $defaultValue;
        }

        return $this;
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
        $this->unique = $unique;
    }

    public function getColumnType(): string
    {
        if ($this->getInteger()) {
            return 'bigint(20)';
        }

        if ($this->isDecimalType()) {
            return $this->buildDecimalColumnType();
        }

        return 'double';
    }

    public function getQueryColumnType(): string
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

    /**
     * @param null|Model\DataObject\Concrete $object
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): float|int|string|null
    {
        $data = $this->handleDefaultValue($data, $object, $params);

        if (is_numeric($data)) {
            return $data;
        }

        return null;
    }

    /**
     * @param null|Model\DataObject\Concrete $object
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): float|int|string|null
    {
        if (is_numeric($data)) {
            return $this->toNumeric($data);
        }

        return null;
    }

    /**
     * @param null|Model\DataObject\Concrete $object
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     */
    public function getDataForQueryResource(mixed $data, Concrete $object = null, array $params = []): float|int|string|null
    {
        //TODO same fallback as above

        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @param null|Model\DataObject\Concrete $object
     *
     * @see Data::getDataForEditmode
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): float|int|string|null
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): float|int|string|null
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     * @param null|Model\DataObject\Concrete $object
     *
     * @see Data::getVersionPreview
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        return (string) $data;
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && $this->isEmpty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (!$this->isEmpty($data) && !is_numeric($data)) {
            throw new Model\Element\ValidationException('field ['.$this->getName().' ] - invalid numeric data [' . $data . '] ');
        }

        if (!$this->isEmpty($data) && !$omitMandatoryCheck) {
            $data = $this->toNumeric($data);

            if ($data >= PHP_INT_MAX) {
                throw new Model\Element\ValidationException('Value exceeds PHP_INT_MAX please use an input data type instead of numeric!');
            }

            if ($this->getInteger() && str_contains((string)$data, '.')) {
                throw new Model\Element\ValidationException('Value in field [ '.$this->getName().' ] is not an integer');
            }

            if ($this->getMinValue() !== null && $this->getMinValue() > $data) {
                throw new Model\Element\ValidationException('Value in field [ '.$this->getName().' ] is not at least ' . $this->getMinValue());
            }

            if ($this->getMaxValue() !== null && $data > $this->getMaxValue()) {
                throw new Model\Element\ValidationException('Value in field [ '.$this->getName().' ] is bigger than ' . $this->getMaxValue());
            }

            if ($this->getUnsigned() && $data < 0) {
                throw new Model\Element\ValidationException('Value in field [ '.$this->getName().' ] is not unsigned (bigger than 0)');
            }
        }
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params) ?? '';

        return (string)$data;
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param array $params optional params used to change the behavior
     *
     */
    public function getFilterConditionExt(mixed $value, string $operator, array $params = []): string
    {
        $db = \Pimcore\Db::get();
        $name = $params['name'] ?: $this->name;
        $key = $db->quoteIdentifier($name);
        if (!empty($params['brickPrefix'])) {
            $key = $params['brickPrefix'].$key;
        }

        if ($value === 'NULL') {
            if ($operator === '=') {
                $operator = 'IS';
            } elseif ($operator === '!=') {
                $operator = 'IS NOT';
            }
        }

        if ((is_numeric($value) || $value === 'NULL') && in_array($operator, self::$validFilterOperators)) {
            return $key . ' ' . $operator . ' ' . $value . ' ';
        }

        if ($operator === 'in') {
            $formattedValues = implode(',', array_map(floatval(...), explode(',', $value)));

            return $key . ' ' . $operator . ' (' . $formattedValues . ')';
        }

        return '';
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    public function isEmpty(mixed $data): bool
    {
        return !is_numeric($data);
    }

    private function toNumeric(mixed $value): float|int|string
    {
        if ($this->getInteger()) {
            return (int) $value;
        }

        $value = str_replace(',', '.', (string) $value);

        if ($this->isDecimalType()) {
            return $value;
        }

        if (!str_contains($value, '.')) {
            return (int) $value;
        }

        return (float) $value;
    }

    public function preSetData(mixed $container, mixed $data, array $params = []): mixed
    {
        if (!is_null($data) && $this->getDecimalPrecision()) {
            $data = round((float) $data, $this->getDecimalPrecision());
        }

        return $data;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    protected function doGetDefaultValue(Concrete $object, array $context = []): float|int|string|null
    {
        return $this->getDefaultValue() ?? null;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return $this->toNumeric($oldValue) == $this->toNumeric($newValue);
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?' . $this->getPhpdocType();
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?' . $this->getPhpdocType();
    }

    public function getPhpdocInputType(): ?string
    {
        return $this->getPhpdocType() . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return $this->getPhpdocType() . '|null';
    }

    public function getFieldType(): string
    {
        return 'numeric';
    }
}
