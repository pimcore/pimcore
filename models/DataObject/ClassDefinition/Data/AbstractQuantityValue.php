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

use Pimcore;
use Pimcore\Db;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\QuantityValue\UnitConversionService;
use Pimcore\Normalizer\NormalizerInterface;

abstract class AbstractQuantityValue extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use Model\DataObject\Traits\DefaultValueTrait;
    use Model\DataObject\Traits\DataWidthTrait;

    /**
     * @internal
     */
    public string|int|null $unitWidth = null;

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
    public bool $unique = false;

    /**
     * @internal
     */
    public bool $autoConvert = false;

    public function getUnitWidth(): int|string|null
    {
        return $this->unitWidth;
    }

    public function setUnitWidth(int|string|null $unitWidth): void
    {
        if (is_numeric($unitWidth)) {
            $unitWidth = (int)$unitWidth;
        }
        $this->unitWidth = $unitWidth;
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

    public function setDefaultUnit(?string $defaultUnit): void
    {
        $this->defaultUnit = $defaultUnit;
    }

    public function getUnique(): bool
    {
        return $this->unique;
    }

    public function setUnique(bool $unique): void
    {
        $this->unique = $unique;
    }

    public function isAutoConvert(): bool
    {
        return $this->autoConvert;
    }

    public function setAutoConvert(bool $autoConvert): void
    {
        $this->autoConvert = $autoConvert;
    }

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

    public function getDataForQueryResource(mixed $data, Concrete $object = null, array $params = []): array
    {
        return $this->getDataForResource($data, $object, $params);
    }

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
     */
    public function getDataForGrid(mixed $data, Concrete $object = null, array $params = []): ?array
    {
        if ($data instanceof Model\DataObject\Data\AbstractQuantityValue) {
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

    public function getFilterCondition(mixed $value, string $operator, array $params = []): string
    {
        /** @var UnitConversionService $converter */
        $converter = Pimcore::getContainer()->get(UnitConversionService::class);

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
            if ($operator === 'in') {
                $values = explode(',', $value[0]);
                $convertedValues = [];
                foreach ($values as $value) {
                    $filterQuantityValue->setValue($value);
                    $convertedQuantityValue = $converter->convert($filterQuantityValue, $unit);
                    $convertedValues[] = $convertedQuantityValue->getValue();
                }
                /** @var \Pimcore\Model\DataObject\Data\QuantityValue $convertedQuantityValue */
                $convertedQuantityValue->setValue(implode(',', $convertedValues));
            } else {
                $convertedQuantityValue = $converter->convert($filterQuantityValue, $unit);
            }

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

    protected function prepareUnitIdForComparison(mixed $unitId): string
    {
        $unitId = (string) $unitId;
        if (empty($unitId)) {
            $unitId = '';
        }

        return $unitId;
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof Model\DataObject\Data\AbstractQuantityValue) {
            return [
                'value' => $value->getValue(),
                'unitId' => $value->getUnitId(),
            ];
        }

        return null;
    }

    public function isEmpty(mixed $data): bool
    {
        if ($data instanceof Model\DataObject\Data\AbstractQuantityValue) {
            return empty($data->getValue()) && empty($data->getUnitId());
        }

        return parent::isEmpty($data);
    }
}
