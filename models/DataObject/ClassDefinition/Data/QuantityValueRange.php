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
use Pimcore\Cache;
use Pimcore\Cache\Runtime;
use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Normalizer\NormalizerInterface;

class QuantityValueRange extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'quantityValueRange';

    /**
     * @internal
     */
    public string|int $width = 0;

    /**
     * @internal
     */
    public string|int $unitWidth;

    /**
     * @internal
     */
    public ?string $defaultUnit;

    /**
     * @internal
     */
    public array $validUnits;

    /**
     * @internal
     */
    public ?int $decimalPrecision;

    /**
     * @internal
     */
    public bool $autoConvert;

    /**
     * Type for the column to query
     *
     * @internal
     */
    public array $queryColumnType = [
        'minimum' => 'double',
        'maximum' => 'double',
        'unit' => 'varchar(64)',
    ];

    /**
     * Type for the column
     *
     * @internal
     */
    public array $columnType = [
        'minimum' => 'double',
        'maximum' => 'double',
        'unit' => 'varchar(64)',
    ];

    public function getWidth(): string|int
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

    public function getUnitWidth(): string|int
    {
        return $this->unitWidth;
    }

    public function setUnitWidth(string|int $unitWidth): void
    {
        if (\is_numeric($unitWidth)) {
            $unitWidth = (int) $unitWidth;
        }

        $this->unitWidth = $unitWidth;
    }

    public function getValidUnits(): array
    {
        return $this->validUnits;
    }

    public function setValidUnits(array $validUnits): void
    {
        $this->validUnits = $validUnits;
    }

    public function getDefaultUnit(): ?string
    {
        return $this->defaultUnit;
    }

    public function setDefaultUnit(?string $defaultUnit): void
    {
        $this->defaultUnit = $defaultUnit;
    }

    public function getDecimalPrecision(): ?int
    {
        return $this->decimalPrecision;
    }

    public function setDecimalPrecision(?int $decimalPrecision): void
    {
        $this->decimalPrecision = $decimalPrecision;
    }

    public function isAutoConvert(): bool
    {
        return $this->autoConvert;
    }

    public function setAutoConvert($autoConvert): void
    {
        $this->autoConvert = (bool) $autoConvert;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param DataObject\Data\QuantityValueRange|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     */
    public function getDataForResource($data, $object = null, $params = []): array
    {
        if ($data instanceof DataObject\Data\QuantityValueRange) {
            return [
                $this->getName() . '__minimum' => $data->getMinimum(),
                $this->getName() . '__maximum' => $data->getMaximum(),
                $this->getName() . '__unit' => $data->getUnitId(),
            ];
        }

        return [
            $this->getName() . '__minimum' => null,
            $this->getName() . '__maximum' => null,
            $this->getName() . '__unit' => null,
        ];
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param array $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     */
    public function getDataFromResource($data, $object = null, $params = []): ?DataObject\Data\QuantityValueRange
    {
        if (isset($data[$this->getName() . '__minimum'], $data[$this->getName() . '__maximum'], $data[$this->getName() . '__unit'])) {
            $quantityValueRange = new DataObject\Data\QuantityValueRange(
                $data[$this->getName() . '__minimum'],
                $data[$this->getName() . '__maximum'],
                $data[$this->getName() . '__unit']
            );

            if (isset($params['owner'])) {
                $quantityValueRange->_setOwner($params['owner']);
                $quantityValueRange->_setOwnerFieldname($params['fieldname']);
                $quantityValueRange->_setOwnerLanguage($params['language'] ?? null);
            }

            return $quantityValueRange;
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param DataObject\Data\QuantityValueRange $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     */
    public function getDataForQueryResource($data, $object = null, $params = []): array
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param DataObject\Data\QuantityValueRange|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     */
    public function getDataForEditmode($data, $object = null, $params = []): ?array
    {
        if ($data instanceof DataObject\Data\QuantityValueRange) {
            return [
                'minimum' => $data->getMinimum(),
                'maximum' => $data->getMaximum(),
                'unit' => $data->getUnitId(),
            ];
        }

        return null;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param array $data
     * @param DataObject\Concrete $object
     * @param mixed $params
     */
    public function getDataFromEditmode($data, $object = null, $params = []): ?DataObject\Data\QuantityValueRange
    {
        if (\is_array($data) && (isset($data['minimum']) || isset($data['maximum']) || isset($data['unit']))) {
            if ($data['unit'] === -1 || empty($data['unit'])) {
                $data['unit'] = null;
            }

            return new DataObject\Data\QuantityValueRange($data['minimum'], $data['maximum'], $data['unit']);
        }

        return null;
    }

    public function getDataFromGridEditor(array $data, DataObject\Concrete $object = null, array $params = []): ?DataObject\Data\QuantityValueRange
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param DataObject\Data\QuantityValueRange|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     */
    public function getVersionPreview($data, $object = null, $params = []): string
    {
        if ($data instanceof DataObject\Data\QuantityValueRange) {
            return $data->__toString();
        }

        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function getForCsvExport($object, $params = []): string
    {
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data instanceof DataObject\Data\QuantityValueRange) {
            $export = $data->getMinimum() . ',' . $data->getMaximum();
            $unit = $data->getUnit();

            if ($unit instanceof DataObject\QuantityValue\Unit) {
                $export .= ' ' . $unit->getAbbreviation();
            }

            return $export;
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value instanceof DataObject\Data\QuantityValueRange) {
            return $value->toArray();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if (\is_array($value)) {
            return new DataObject\Data\QuantityValueRange($value['minimum'], $value['maximum'], $value['unitId']);
        }

        return null;
    }

    public function getDataForGrid(
        ?DataObject\Data\QuantityValueRange $data,
        DataObject\Concrete $object = null,
        array $params = []
    ): ?array {
        $gridData = $this->getDataForEditmode($data, $object, $params);

        if ($data instanceof DataObject\Data\QuantityValueRange) {
            $unit = $data->getUnit();

            if ($unit instanceof DataObject\QuantityValue\Unit) {
                $unitAbbreviation = $unit->getAbbreviation();
            }

            $gridData['unitAbbr'] = $unitAbbreviation ?? '';

            return $gridData;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = []): void
    {
        $fieldName = $this->getName();

        if ($data && !$data instanceof DataObject\Data\QuantityValueRange) {
            throw new ValidationException('Expected an instance of QuantityValueRange');
        }

        if ($omitMandatoryCheck === false && $this->getMandatory()
            && ($data === null
                || $data->getMinimum() === null
                || $data->getMaximum() === null
                || $data->getUnitId() === null
            )
        ) {
            throw new ValidationException(\sprintf('Empty mandatory field [ %s ]', $fieldName));
        }

        if (!empty($data)) {
            $minimum = $data->getMinimum();
            $maximum = $data->getMaximum();

            if ($minimum !== null && (!\is_numeric($minimum) || !\is_numeric($maximum))) {
                throw new ValidationException(sprintf('Invalid dimension unit data: %s', $fieldName));
            }

            if ($minimum > $maximum) {
                throw new ValidationException(
                    \sprintf('Minimum value in field [ %s ] is bigger than the maximum value', $fieldName)
                );
            }
        }
    }

    /**
     * @internal
     */
    public function configureOptions(): void
    {
        if ($this->validUnits) {
            return;
        }

        $table = null;

        try {
            if (Runtime::isRegistered(DataObject\QuantityValue\Unit::CACHE_KEY)) {
                $table = Runtime::get(DataObject\QuantityValue\Unit::CACHE_KEY);
            }

            if (!\is_array($table)) {
                $table = Cache::load(DataObject\QuantityValue\Unit::CACHE_KEY);

                if (\is_array($table)) {
                    Runtime::set(DataObject\QuantityValue\Unit::CACHE_KEY, $table);
                }
            }

            if (!\is_array($table)) {
                $table = [];
                $list = new DataObject\QuantityValue\Unit\Listing();
                $list->setOrderKey(['baseunit', 'factor', 'abbreviation']);
                $list->setOrder(['ASC', 'ASC', 'ASC']);

                foreach ($list->getUnits() as $item) {
                    $table[$item->getId()] = $item;
                }

                Cache::save($table, DataObject\QuantityValue\Unit::CACHE_KEY, [], null, 995, true);
                Runtime::set(DataObject\QuantityValue\Unit::CACHE_KEY, $table);
            }
        } catch (Exception $e) {
            Logger::error((string) $e);
        }

        if (\is_array($table)) {
            $this->validUnits = [];

            /** @var DataObject\QuantityValue\Unit $unit */
            foreach ($table as $unit) {
                $this->validUnits[] = $unit->getId();
            }
        }
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
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    public function isEqual($oldValue, $newValue): bool
    {
        if ($oldValue === null && $newValue === null) {
            return true;
        }

        if (!$oldValue instanceof DataObject\Data\QuantityValueRange) {
            return false;
        }

        if (!$newValue instanceof DataObject\Data\QuantityValueRange) {
            return false;
        }

        return $oldValue->getValue() === $newValue->getValue()
            && (string) $oldValue->getUnitId() === (string) $newValue->getUnitId();
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\QuantityValueRange::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\QuantityValueRange::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\QuantityValueRange::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\QuantityValueRange::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty($data): bool
    {
        if ($data instanceof DataObject\Data\QuantityValueRange) {
            return empty($data->getValue()) && empty($data->getUnitId());
        }

        return parent::isEmpty($data);
    }
}
