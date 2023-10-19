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

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

class Slider extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use Model\DataObject\Traits\SimpleComparisonTrait;
    use DataObject\Traits\SimpleNormalizerTrait;
    use DataObject\Traits\DataHeightTrait;
    use DataObject\Traits\DataWidthTrait;

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
    public bool $vertical = false;

    /**
     * @internal
     *
     */
    public ?float $increment = null;

    /**
     * @internal
     *
     */
    public ?int $decimalPrecision = null;

    public function getMinValue(): ?float
    {
        return $this->minValue;
    }

    public function setMinValue(?float $minValue): static
    {
        $this->minValue = $this->getAsFloatCast($minValue);

        return $this;
    }

    public function getMaxValue(): ?float
    {
        return $this->maxValue;
    }

    public function setMaxValue(?float $maxValue): static
    {
        $this->maxValue = $this->getAsFloatCast($maxValue);

        return $this;
    }

    public function getVertical(): bool
    {
        return $this->vertical;
    }

    public function setVertical(bool $vertical): static
    {
        $this->vertical = $vertical;

        return $this;
    }

    public function getIncrement(): ?float
    {
        return $this->increment;
    }

    public function setIncrement(?float $increment): static
    {
        $this->increment = $this->getAsFloatCast($increment);

        return $this;
    }

    public function getDecimalPrecision(): ?int
    {
        return $this->decimalPrecision;
    }

    public function setDecimalPrecision(?int $decimalPrecision): static
    {
        $this->decimalPrecision = $this->getAsIntegerCast($decimalPrecision);

        return $this;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?float
    {
        if ($data != null) {
            $data = (float) $data;
        }

        return $data;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?float
    {
        if ($data != null) {
            $data = (float) $data;
        }

        return $data;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?float
    {
        return $data;
    }

    /**
     *
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?float
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?float
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     * @param Model\DataObject\Concrete|null $object
     *
     */
    public function getDataFromGridEditor(mixed $data, Concrete $object = null, array $params = []): ?float
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        return (string)$data;
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && $data === null) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ] '.(string)$data);
        }

        if (!empty($data) && !is_numeric($data)) {
            throw new Model\Element\ValidationException('invalid slider data');
        }
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /**
     * @param DataObject\ClassDefinition\Data\Slider $mainDefinition
     */
    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        $this->minValue = $mainDefinition->minValue;
        $this->maxValue = $mainDefinition->maxValue;
        $this->vertical = $mainDefinition->vertical;
        $this->increment = $mainDefinition->increment;
        $this->decimalPrecision = $mainDefinition->decimalPrecision;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldValue = (float) $oldValue;
        $newValue = (float) $newValue;
        if (abs($oldValue - $newValue) < 0.00001) {
            return true;
        }

        return false;
    }

    public function isEmpty(mixed $data): bool
    {
        return !is_numeric($data);
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?float';
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?float';
    }

    public function getPhpdocInputType(): ?string
    {
        return 'float|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return 'float|null';
    }

    public function getColumnType(): string
    {
        return 'double';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'slider';
    }
}
