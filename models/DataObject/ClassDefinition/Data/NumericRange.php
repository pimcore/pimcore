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

use Exception;
use InvalidArgumentException;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Extension\ColumnType;
use Pimcore\Model\DataObject\ClassDefinition\Data\Extension\QueryColumnType;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Normalizer\NormalizerInterface;

class NumericRange extends Data implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    EqualComparisonInterface,
    VarExporterInterface,
    NormalizerInterface
{
    use Extension\ColumnType {
        ColumnType::getColumnType as public genericGetColumnType;
    }
    use Extension\QueryColumnType {
        QueryColumnType::getQueryColumnType as public genericGetQueryColumnType;
    }

    public const DECIMAL_SIZE_DEFAULT = 64;
    public const DECIMAL_PRECISION_DEFAULT = 0;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'numericRange';

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var array
     */
    public $queryColumnType = [
        'minimum' => 'double',
        'maximum' => 'double',
    ];

    /**
     * Type for the column
     *
     * @internal
     *
     * @var array
     */
    public $columnType = [
        'minimum' => 'double',
        'maximum' => 'double',
    ];

    /**
     * @internal
     */
    public string|int $width = 0;

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
    public ?float $minValue;

    /**
     * @internal
     */
    public ?float $maxValue;

    /**
     * This is the x part in DECIMAL(x, y) and denotes the total amount of digits. In MySQL this is called precision
     * but as decimalPrecision already existed to denote the amount of digits after the point (as it is called on the ExtJS
     * number field), decimalSize was chosen instead.
     *
     * @internal
     */
    public ?int $decimalSize;

    /**
     * This is the y part in DECIMAL(x, y) and denotes amount of digits after a comma. In MySQL this is called scale. See
     * commend on decimalSize.
     *
     * @internal
     */
    public ?int $decimalPrecision;

    public function getWidth(): int|string
    {
        return $this->width;
    }

    public function setWidth(int|string $width): void
    {
        if (\is_numeric($width)) {
            $width = (int) $width;
        }

        $this->width = $width;
    }

    public function getInteger(): bool
    {
        return $this->integer;
    }

    public function setInteger(bool $integer): void
    {
        $this->integer = $integer;
    }

    public function getMaxValue(): ?float
    {
        return $this->maxValue;
    }

    public function setMaxValue(?float $maxValue): void
    {
        $this->maxValue = $maxValue;
    }

    public function getMinValue(): ?float
    {
        return $this->minValue;
    }

    public function setMinValue(?float $minValue)
    {
        $this->minValue = $minValue;
    }

    public function getUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function setUnsigned(bool $unsigned): void
    {
        $this->unsigned = $unsigned;
    }

    public function getDecimalSize(): ?int
    {
        return $this->decimalSize;
    }

    public function setDecimalSize(?int $decimalSize): void
    {
        if (!\is_numeric($decimalSize)) {
            $decimalSize = null;
        }

        $this->decimalSize = $decimalSize;
    }

    public function getDecimalPrecision(): ?int
    {
        return $this->decimalPrecision;
    }

    public function setDecimalPrecision(?int $decimalPrecision): void
    {
        $this->decimalPrecision = $decimalPrecision;
    }

    /**
     * {@inheritDoc}
     */
    public function getColumnType(): array|string|null
    {
        if ($this->getInteger()) {
            return [
                'minimum' => 'bigint(20)',
                'maximum' => 'bigint(20)',
            ];
        }

        if ($this->isDecimalType()) {
            return $this->buildDecimalColumnType();
        }

        return $this->genericGetColumnType();
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryColumnType(): array|null
    {
        if ($this->getInteger()) {
            return [
                'minimum' => 'bigint(20)',
                'maximum' => 'bigint(20)',
            ];
        }

        if ($this->isDecimalType()) {
            return $this->buildDecimalColumnType();
        }

        return $this->genericGetQueryColumnType();
    }

    private function isDecimalType(): bool
    {
        return null !== $this->getDecimalSize() || null !== $this->getDecimalPrecision();
    }

    private function buildDecimalColumnType(): array
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
            $precision = (int) $this->decimalSize;
        }

        if (null !== $this->decimalPrecision) {
            $scale = (int) $this->decimalPrecision;
        }

        if ($precision < 1 || $precision > 65) {
            throw new InvalidArgumentException('Decimal precision must be a value between 1 and 65');
        }

        if ($scale < 0 || $scale > 30) {
            throw new InvalidArgumentException('Decimal scale must be a value between 0 and 30');
        }

        if ($scale > $precision) {
            throw new InvalidArgumentException(\sprintf(
                'Decimal scale can\'t be larger than precision (%d)',
                $precision
            ));
        }

        return [
            'minimum' => \sprintf('DECIMAL(%d, %d)', $precision, $scale),
            'maximum' => \sprintf('DECIMAL(%d, %d)', $precision, $scale),
        ];
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param DataObject\Data\NumericRange|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = []): array
    {
        if ($data instanceof DataObject\Data\NumericRange) {
            return [
                $this->getName() . '__minimum' => $data->getMinimum(),
                $this->getName() . '__maximum' => $data->getMaximum(),
            ];
        }

        return [
            $this->getName() . '__minimum' => null,
            $this->getName() . '__maximum' => null,
        ];
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\NumericRange|null
     */
    public function getDataFromResource($data, $object = null, $params = []): ?DataObject\Data\NumericRange
    {
        if (isset($data[$this->getName() . '__minimum'], $data[$this->getName() . '__maximum'])) {
            $numericRange = new DataObject\Data\NumericRange(
                $data[$this->getName() . '__minimum'],
                $data[$this->getName() . '__maximum']
            );

            if (isset($params['owner'])) {
                $numericRange->_setOwner($params['owner']);
                $numericRange->_setOwnerFieldname($params['fieldname']);
                $numericRange->_setOwnerLanguage($params['language'] ?? null);
            }

            return $numericRange;
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param DataObject\Data\NumericRange|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForQueryResource($data, $object = null, $params = []): array
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param DataObject\Data\NumericRange|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = []): ?array
    {
        if ($data instanceof DataObject\Data\NumericRange) {
            return [
                'minimum' => $data->getMinimum(),
                'maximum' => $data->getMaximum(),
            ];
        }

        return null;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param array|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Data\NumericRange|null
     */
    public function getDataFromEditmode($data, $object = null, $params = []): ?DataObject\Data\NumericRange
    {
        if (\is_array($data) && (isset($data['minimum']) || isset($data['maximum']))) {
            return new DataObject\Data\NumericRange($data['minimum'], $data['maximum']);
        }

        return null;
    }

    public function getDataFromGridEditor(array $data, DataObject\Concrete $object = null, array $params = []): ?DataObject\Data\NumericRange
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param DataObject\Data\NumericRange|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = []): string
    {
        if ($data instanceof DataObject\Data\NumericRange) {
            return $data->__toString();
        }

        return '';
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function getForCsvExport($object, $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data instanceof DataObject\Data\NumericRange) {
            return $data->getMinimum() . ',' . $data->getMaximum();
        }

        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getDataForSearchIndex($object, $params = []): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function isDiffChangeAllowed($object, $params = []): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value instanceof DataObject\Data\NumericRange) {
            return $value->toArray();
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize($value, $params = [])
    {
        if (\is_array($value)) {
            return new DataObject\Data\NumericRange($value['minimum'], $value['maximum']);
        }

        return null;
    }

    public function getDataForGrid(
        ?DataObject\Data\NumericRange $data,
        DataObject\Concrete $object = null,
        array $params = []
    ): ?array {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = []): void
    {
        $isEmpty = true;

        if ($data) {
            if (!$data instanceof DataObject\Data\NumericRange) {
                throw new ValidationException('Expected an instance of NumericRange');
            }

            $isEmpty = false;
        }

        $fieldName = $this->getName();

        if (true === $isEmpty && false === $omitMandatoryCheck && $this->getMandatory()) {
            throw new ValidationException(\sprintf('Empty mandatory field [ %s ]', $fieldName));
        }

        if (false === $isEmpty && false === $omitMandatoryCheck) {
            $minimum = $data->getMinimum();
            $maximum = $data->getMaximum();

            if ($minimum >= PHP_INT_MAX || $maximum >= PHP_INT_MAX) {
                throw new ValidationException('Value exceeds PHP_INT_MAX');
            }

            if ($this->getInteger() && str_contains((string) $data, '.')) {
                throw new ValidationException(
                    \sprintf('Either the minimum or maximum value in field [ %s ] is not an integer', $fieldName)
                );
            }

            $minimumThreshold = $this->getMinValue();

            if (null !== $minimumThreshold && $minimum < $minimumThreshold) {
                throw new ValidationException(
                    \sprintf('Minimum value in field [ %s ] is not at least %d', $fieldName, $minimumThreshold)
                );
            }

            if (null !== $minimumThreshold && $maximum < $minimumThreshold) {
                throw new ValidationException(
                    \sprintf('Maximum value in field [ %s ] is not at least %d', $fieldName, $minimumThreshold)
                );
            }

            $maximumThreshold = $this->getMaxValue();

            if (null !== $maximumThreshold && $minimum > $maximumThreshold) {
                throw new ValidationException(
                    \sprintf('Minimum value in field [ %s ] is bigger than %d', $fieldName, $maximumThreshold)
                );
            }

            if (null !== $maximumThreshold && $maximum > $maximumThreshold) {
                throw new ValidationException(
                    \sprintf('Maximum value in field [ %s ] is bigger than %s', $fieldName, $maximumThreshold)
                );
            }

            if ($minimum > $maximum) {
                throw new ValidationException(
                    \sprintf('Minimum value in field [ %s ] is bigger than the maximum value', $fieldName)
                );
            }

            if ($minimum < 0 && $this->getUnsigned()) {
                throw new ValidationException(
                    \sprintf('Value in field [ %s ] is not unsigned (bigger than 0)', $fieldName)
                );
            }
        }
    }

    /**
     *
     * @param DataObject\Data\NumericRange|null $oldValue
     * @param DataObject\Data\NumericRange|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if (!$oldValue instanceof DataObject\Data\NumericRange
            || !$newValue instanceof DataObject\Data\NumericRange) {
            return false;
        }

        return (abs($oldValue->getMinimum() - $newValue->getMinimum()) < 0.000000000001)
            && (abs($oldValue->getMaximum() - $newValue->getMaximum()) < 0.000000000001);
    }

    /**
     * {@inheritDoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\NumericRange::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\NumericRange::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\NumericRange::class . '|null';
    }

    /**
     * {@inheritDoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\NumericRange::class . '|null';
    }
}
