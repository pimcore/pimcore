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

use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Normalizer\NormalizerInterface;

class Numeric extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface, PreSetDataInterface
{
    use Model\DataObject\Traits\DefaultValueTrait;
    use Model\DataObject\Traits\SimpleNormalizerTrait;
    use Model\DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType {
        getColumnType as public genericGetColumnType;
    }
    use Extension\QueryColumnType {
        getQueryColumnType as public genericGetQueryColumnType;
    }

    const DECIMAL_SIZE_DEFAULT = 64;

    const DECIMAL_PRECISION_DEFAULT = 0;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'numeric';

    /**
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * @internal
     *
     * @var float|int|string|null
     */
    public $defaultValue;

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var string
     */
    public $queryColumnType = 'double';

    /**
     * Type for the column
     *
     * @internal
     *
     * @var string
     */
    public $columnType = 'double';

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
     * @return string
     */
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

    /**
     * @return string|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string|int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
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
     *
     * @return $this
     */
    public function setDefaultValue($defaultValue)
    {
        if ((string)$defaultValue !== '') {
            $this->defaultValue = $defaultValue;
        }

        return $this;
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
     * {@inheritdoc}
     */
    public function getColumnType()
    {
        if ($this->getInteger()) {
            return 'bigint(20)';
        }

        if ($this->isDecimalType()) {
            return $this->buildDecimalColumnType();
        }

        return $this->genericGetColumnType();
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryColumnType()
    {
        if ($this->getInteger()) {
            return 'bigint(20)';
        }

        if ($this->isDecimalType()) {
            return $this->buildDecimalColumnType();
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
     * @param float|int|string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float|int|string|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        $data = $this->handleDefaultValue($data, $object, $params);

        if (is_numeric($data)) {
            return $data;
        }

        return null;
    }

    /**
     * @param float|int|string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float|int|string|null
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (is_numeric($data)) {
            return $this->toNumeric($data);
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param float|int|string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float|int|string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        //TODO same fallback as above

        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param float|int|string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float|int|string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param float|int|string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float|int|string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param float|int|string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float|int|string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
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

            if ($this->getInteger() && strpos((string) $data, '.') !== false) {
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

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        return (string)$data;
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /**
     * @param string|null $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        return !is_numeric($data);
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
     * { @inheritdoc }
     */
    public function preSetData(/** mixed */ $container, /**  mixed */ $data, /** array */ $params = []) // : mixed
    {
        if (!is_null($data) && $this->getDecimalPrecision()) {
            $data = round($data, $this->getDecimalPrecision());
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetDefaultValue($object, $context = [])
    {
        return $this->getDefaultValue() ?? null;
    }

    /**
     * @param float|int|string $oldValue
     * @param float|int|string $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        return $this->toNumeric($oldValue) == $this->toNumeric($newValue);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?' . $this->getPhpdocType();
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?' . $this->getPhpdocType();
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return $this->getPhpdocType() . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return $this->getPhpdocType() . '|null';
    }
}
