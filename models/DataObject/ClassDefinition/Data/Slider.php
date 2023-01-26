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
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use DataObject\Traits\SimpleNormalizerTrait;
    use DataObject\Traits\DataHeightTrait;
    use DataObject\Traits\DataWidthTrait;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public string $fieldtype = 'slider';

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
    public bool $vertical = false;

    /**
     * @internal
     *
     * @var float|null
     */
    public ?float $increment = null;

    /**
     * @internal
     *
     * @var int|null
     */
    public ?int $decimalPrecision = null;

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
        $this->vertical = (bool) $vertical;

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
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return float|null
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
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return float|null
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
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return float|null
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?float
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return float|null
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?float
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return float|null
     *
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?float
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     * @param float|null $data
     * @param Model\DataObject\Concrete|null $object
     * @param array $params
     *
     * @return float|null
     */
    public function getDataFromGridEditor(?float $data, Concrete $object = null, array $params = []): ?float
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        return (string)$data;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && $data === null) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ] '.(string)$data);
        }

        if (!empty($data) && !is_numeric($data)) {
            throw new Model\Element\ValidationException('invalid slider data');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /**
     * @param DataObject\ClassDefinition\Data\Slider $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition): void
    {
        $this->minValue = $masterDefinition->minValue;
        $this->maxValue = $masterDefinition->maxValue;
        $this->vertical = $masterDefinition->vertical;
        $this->increment = $masterDefinition->increment;
        $this->decimalPrecision = $masterDefinition->decimalPrecision;
    }

    /**
     * {@inheritdoc}
     */
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
}
