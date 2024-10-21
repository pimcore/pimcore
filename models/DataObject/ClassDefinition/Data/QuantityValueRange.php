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

use Exception;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Normalizer\NormalizerInterface;

class QuantityValueRange extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use DataObject\Traits\DataWidthTrait;

    /**
     * @internal
     */
    public string|int $unitWidth = 0;

    /**
     * @internal
     */
    public ?string $defaultUnit = null;

    /**
     * @internal
     */
    public array $validUnits = [];

    /**
     * @internal
     */
    public ?int $decimalPrecision = null;

    /**
     * @internal
     */
    public bool $autoConvert = false;

    public function getUnitWidth(): string|int
    {
        return $this->unitWidth;
    }

    public function setUnitWidth(string|int $unitWidth): void
    {
        if (is_numeric($unitWidth)) {
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

    public function setAutoConvert(bool $autoConvert): void
    {
        $this->autoConvert = $autoConvert;
    }

    /**
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): array
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
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?DataObject\Data\QuantityValueRange
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
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): array
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
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
     *
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?DataObject\Data\QuantityValueRange
    {
        if (is_array($data) && (isset($data['minimum']) || isset($data['maximum']) || isset($data['unit']))) {
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
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        if ($data instanceof DataObject\Data\QuantityValueRange) {
            return $data->__toString();
        }

        return '';
    }

    /**
     *
     *
     * @throws Exception
     */
    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
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

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof DataObject\Data\QuantityValueRange) {
            return $value->toArray();
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?DataObject\Data\QuantityValueRange
    {
        if (is_array($value)) {
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

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        $fieldName = $this->getName();

        if ($data && !$data instanceof DataObject\Data\QuantityValueRange) {
            throw new ValidationException('Expected an instance of QuantityValueRange');
        }

        $minimum = $data?->getMinimum();
        $maximum = $data?->getMaximum();

        if ($omitMandatoryCheck === false && $this->getMandatory()
            && ($data === null
                || $minimum === null
                || $maximum === null
                || $data->getUnitId() === null
            )
        ) {
            throw new ValidationException(sprintf('Empty mandatory field [ %s ]', $fieldName));
        }

        if ($minimum || $maximum) {

            if (!is_numeric($minimum) || !is_numeric($maximum)) {
                throw new ValidationException(sprintf('Invalid dimension unit data: %s', $fieldName));
            }

            if ($minimum > $maximum) {
                throw new ValidationException(
                    sprintf('Minimum value in field [ %s ] is bigger than the maximum value', $fieldName)
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

        $table = DataObject\QuantityValue\Service::getQuantityValueUnitsTable();

        if (is_array($table)) {
            $this->validUnits = [];
            foreach ($table as $unit) {
                $this->validUnits[] = $unit->getId();
            }
        }
    }

    public static function __set_state(array $data): static
    {
        $obj = parent::__set_state($data);
        $obj->configureOptions();

        return $obj;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
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

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\QuantityValueRange::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Data\QuantityValueRange::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\' . DataObject\Data\QuantityValueRange::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Data\QuantityValueRange::class . '|null';
    }

    public function isEmpty(mixed $data): bool
    {
        if ($data instanceof DataObject\Data\QuantityValueRange) {
            return empty($data->getValue()) && empty($data->getUnitId());
        }

        return parent::isEmpty($data);
    }

    public function getColumnType(): array
    {
        return [
            'minimum' => 'double',
            'maximum' => 'double',
            'unit' => 'varchar(64)',
        ];
    }

    public function getQueryColumnType(): array
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'quantityValueRange';
    }
}
