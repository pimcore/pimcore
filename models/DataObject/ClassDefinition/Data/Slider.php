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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
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
    public $fieldtype = 'slider';

    /**
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * @internal
     *
     * @var string|int
     */
    public $height = 0;

    /**
     * @internal
     *
     * @var float
     */
    public $minValue;

    /**
     * @internal
     *
     * @var float
     */
    public $maxValue;

    /**
     * @internal
     *
     * @var bool
     */
    public $vertical;

    /**
     * v
     *
     * @var float
     */
    public $increment;

    /**
     * @internal
     *
     * @var int
     */
    public $decimalPrecision;

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
     * @return string|int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param string|int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        if (is_numeric($height)) {
            $height = (int)$height;
        }
        $this->height = $height;

        return $this;
    }

    /**
     * @return float
     */
    public function getMinValue()
    {
        return $this->minValue;
    }

    /**
     * @param float $minValue
     *
     * @return $this
     */
    public function setMinValue($minValue)
    {
        $this->minValue = $this->getAsFloatCast($minValue);

        return $this;
    }

    /**
     * @return float
     */
    public function getMaxValue()
    {
        return $this->maxValue;
    }

    /**
     * @param string|int|null $maxValue
     *
     * @return $this
     *
     * @internal param float $minValue
     */
    public function setMaxValue($maxValue)
    {
        $this->maxValue = $this->getAsFloatCast($maxValue);

        return $this;
    }

    /**
     * @return bool
     */
    public function getVertical()
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
    public function setVertical($vertical)
    {
        $this->vertical = (bool) $vertical;

        return $this;
    }

    /**
     * @return float
     */
    public function getIncrement()
    {
        return $this->increment;
    }

    /**
     * @param float $increment
     *
     * @return $this
     */
    public function setIncrement($increment)
    {
        $this->increment = $this->getAsFloatCast($increment);

        return $this;
    }

    /**
     * @return int
     */
    public function getDecimalPrecision()
    {
        return $this->decimalPrecision;
    }

    /**
     * @param int $decimalPrecision
     *
     * @return $this
     */
    public function setDecimalPrecision($decimalPrecision)
    {
        $this->decimalPrecision = $this->getAsIntegerCast($decimalPrecision);

        return $this;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param float|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data != null) {
            $data = (float) $data;
        }

        return $data;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param float|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data != null) {
            $data = (float) $data;
        }

        return $data;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param float|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float|null
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param float|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param float|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     * @param float|null $data
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float|null
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param float|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return (string)$data;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if (!$omitMandatoryCheck and $this->getMandatory() and $data === null) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ] '.(string)$data);
        }

        if (!empty($data) and !is_numeric($data)) {
            throw new Model\Element\ValidationException('invalid slider data');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed($object, $params = [])
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

    /**
     * @param float|null $oldValue
     * @param float|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        $oldValue = (float) $oldValue;
        $newValue = (float) $newValue;
        if (abs($oldValue - $newValue) < 0.00001) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?float';
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?float';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return 'float|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return 'float|null';
    }
}
