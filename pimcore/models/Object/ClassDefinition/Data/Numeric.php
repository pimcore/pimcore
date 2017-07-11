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

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;

class Numeric extends Model\Object\ClassDefinition\Data
{
    const DECIMAL_SIZE_DEFAULT = 64;
    const DECIMAL_PRECISION_DEFAULT = 0;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'numeric';

    /**
     * @var float
     */
    public $width;

    /**
     * @var float
     */
    public $defaultValue;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'double';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'double';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = 'float';

    /**
     * @var bool
     */
    public $integer = false;

    /**
     * @var bool
     */
    public $unsigned = false;

    /**
     * @var float
     */
    public $minValue;

    /**
     * @var float
     */
    public $maxValue;

    /**
     * This is the x part in DECIMAL(x, y) and denotes the total amount of digits. In MySQL this is called precision
     * but as decimalPrecision already existed to denote the amount of digits after the point (as it is called on the ExtJS
     * number field), decimalSize was chosen instead.
     *
     * @var int
     */
    public $decimalSize;

    /**
     * This is the y part in DECIMAL(x, y) and denotes amount of digits after a comma. In MySQL this is called scale. See
     * commend on decimalSize.
     *
     * @var int
     */
    public $decimalPrecision;

    /**
     * @inheritDoc
     */
    public function getPhpdocType(): string
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
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
    }

    /**
     * @return int
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue !== null) {
            return $this->toNumeric($this->defaultValue);
        }
    }

    /**
     * @param int $defaultValue
     *
     * @return $this
     */
    public function setDefaultValue($defaultValue)
    {
        if (strlen(strval($defaultValue)) > 0) {
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
     * @param float $maxValue
     */
    public function setMaxValue($maxValue)
    {
        $this->maxValue = $maxValue;
    }

    /**
     * @return float
     */
    public function getMaxValue()
    {
        return $this->maxValue;
    }

    /**
     * @param float $minValue
     */
    public function setMinValue($minValue)
    {
        $this->minValue = $minValue;
    }

    /**
     * @return float
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
     * @return int
     */
    public function getDecimalSize()
    {
        return $this->decimalSize;
    }

    /**
     * @param int $decimalSize
     */
    public function setDecimalSize($decimalSize)
    {
        $this->decimalSize = $decimalSize;
    }

    /**
     * @param int $decimalPrecision
     */
    public function setDecimalPrecision($decimalPrecision)
    {
        $this->decimalPrecision = $decimalPrecision;
    }

    /**
     * @return int
     */
    public function getDecimalPrecision()
    {
        return $this->decimalPrecision;
    }

    /**
     * @return string
     */
    public function getColumnType()
    {
        if ($this->getInteger()) {
            return 'bigint(20)';
        }

        if ($this->isDecimalType()) {
            return $this->buildDecimalColumnType();
        }

        return parent::getColumnType();
    }

    /**
     * @return string
     */
    public function getQueryColumnType()
    {
        if ($this->getInteger()) {
            return 'bigint(20)';
        }

        if ($this->isDecimalType()) {
            return $this->buildDecimalColumnType();
        }

        return parent::getQueryColumnType();
    }

    public function isDecimalType(): bool
    {
        return (null !== $this->getDecimalSize() || null !== $this->getDecimalPrecision());
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
        $scale     = self::DECIMAL_PRECISION_DEFAULT;

        if (null !== $this->decimalSize) {
            $precision = intval($this->decimalSize);
        }

        if (null !== $this->decimalPrecision) {
            $scale = intval($this->decimalPrecision);
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
     * @see Model\Object\ClassDefinition\Data::getDataForResource
     *
     * @param float|int|string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return float|int|string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if (is_numeric($data)) {
            return $data;
        }

        return null;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromResource
     *
     * @param float|int|string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return float|int|string
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (is_numeric($data)) {
            return $this->toNumeric($data);
        }

        return $data;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataForQueryResource
     *
     * @param float|int|string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return float|int|string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataForEditmode
     *
     * @param float|int|string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return float|int|string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     *
     * @param float|int|string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return float|int|string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getVersionPreview
     *
     * @param float|int|string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return float|int|string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return $data;
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
        if (!$omitMandatoryCheck && $this->getMandatory() && $this->isEmpty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (!$this->isEmpty($data) && !is_numeric($data)) {
            throw new Model\Element\ValidationException('invalid numeric data [' . $data . ']');
        }

        if (!$this->isEmpty($data) && !$omitMandatoryCheck) {
            $data = $this->toNumeric($data);

            if ($data >= PHP_INT_MAX) {
                throw new Model\Element\ValidationException('Value exceeds PHP_INT_MAX please use an input data type instead of numeric!');
            }

            if ($this->getInteger() && strpos((string) $data, '.') !== false) {
                throw new Model\Element\ValidationException('Value in field [ '.$this->getName().' ] is not an integer');
            }

            if (strlen($this->getMinValue()) && $this->getMinValue() > $data) {
                throw new Model\Element\ValidationException('Value in field [ '.$this->getName().' ] is not at least ' . $this->getMinValue());
            }

            if (strlen($this->getMaxValue()) && $data > $this->getMaxValue()) {
                throw new Model\Element\ValidationException('Value in field [ '.$this->getName().' ] is bigger than ' . $this->getMaxValue());
            }

            if ($this->getUnsigned() && $data < 0) {
                throw new Model\Element\ValidationException('Value in field [ '.$this->getName().' ] is not unsigned (bigger than 0)');
            }
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param Model\Object\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        return strval($data);
    }

    /**
     * fills object field data values from CSV Import String
     *
     * @param string $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     *
     * @return float|int|string
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $value = $this->toNumeric(str_replace(',', '.', $importValue));

        return $value;
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        return strlen($data) < 1;
    }

    /**
     * @param $value
     *
     * @return float|int|string
     */
    protected function toNumeric($value)
    {
        if ($this->isDecimalType()) {
            return (string) $value;
        }

        if (strpos((string) $value, '.') === false) {
            return (int) $value;
        }

        return (float) $value;
    }
}
