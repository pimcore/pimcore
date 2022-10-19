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
     * @var string|int
     */
    public string|int $width = 0;

    /**
     * @internal
     *
     * @var string|int
     */
    public string|int $height = 0;

    /**
     * @internal
     *
     * @var float|null
     */
    public ?float $minValue;

    /**
     * @internal
     *
     * @var float|null
     */
    public ?float $maxValue;

    /**
     * @internal
     */
    public bool $vertical = false;

    /**
     * @internal
     *
     * @var float|null
     */
    public ?float $increment;

    /**
     * @internal
     *
     * @var int|null
     */
    public ?int $decimalPrecision;

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
     * @return string|int
     */
    public function getWidth(): int|string
    {
        return $this->width;
    }

    /**
     * @param int|string $width
     *
     * @return $this
     */
    public function setWidth(int|string $width): static
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @return string|int
     */
    public function getHeight(): int|string
    {
        return $this->height;
    }

    /**
     * @param int|string $height
     *
     * @return $this
     */
    public function setHeight(int|string $height): static
    {
        if (is_numeric($height)) {
            $height = (int)$height;
        }
        $this->height = $height;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getMinValue(): ?float
    {
        return $this->minValue;
    }

    /**
     * @param float|null $minValue
     *
     * @return $this
     */
    public function setMinValue(?float $minValue): static
    {
        $this->minValue = $this->getAsFloatCast($minValue);

        return $this;
    }

    /**
     * @return float|null
     */
    public function getMaxValue(): ?float
    {
        return $this->maxValue;
    }

    /**
     * @param float|null $maxValue
     *
     * @return $this
     */
    public function setMaxValue(?float $maxValue): static
    {
        $this->maxValue = $this->getAsFloatCast($maxValue);

        return $this;
    }

    /**
     * @return bool
     */
    public function getVertical(): bool
    {
        return $this->vertical;
    }

    /**
     * @return null
     */
    public function getDefaultValue()
    {
        return null;
    }

    /**
     * @param bool $vertical
     *
     * @return $this
     */
    public function setVertical(bool $vertical): static
    {
        $this->vertical = (bool) $vertical;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getIncrement(): ?float
    {
        return $this->increment;
    }

    /**
     * @param float|null $increment
     *
     * @return $this
     */
    public function setIncrement(?float $increment): static
    {
        $this->increment = $this->getAsFloatCast($increment);

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDecimalPrecision(): ?int
    {
        return $this->decimalPrecision;
    }

    /**
     * @param int|null $decimalPrecision
     *
     * @return $this
     */
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
     *@see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, $object = null, array $params = []): ?float
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
     *@see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, $object = null, array $params = []): ?float
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
     *@see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     */
    public function getDataForQueryResource(mixed $data, $object = null, array $params = []): ?float
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return float|null
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, $object = null, array $params = []): ?float
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return float|null
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, $object = null, array $params = []): ?float
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
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, $object = null, array $params = []): string
    {
        return (string)$data;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = [])
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
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
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
