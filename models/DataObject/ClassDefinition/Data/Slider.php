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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;

class Slider extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface
{
    use Model\DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use DataObject\ClassDefinition\NullablePhpdocReturnTypeTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'slider';

    /**
     * @var int
     */
    public $width;

    /**
     * @var int
     */
    public $height;

    /**
     * @var float
     */
    public $minValue;

    /**
     * @var float
     */
    public $maxValue;

    /**
     * @var bool
     */
    public $vertical;

    /**
     * @var float
     */
    public $increment;

    /**
     * @var int
     */
    public $decimalPrecision;

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
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $this->getAsIntegerCast($height);

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
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if (!$omitMandatoryCheck and $this->getMandatory() and $data === null) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ] '.strval($data));
        }

        if (!empty($data) and !is_numeric($data)) {
            throw new Model\Element\ValidationException('invalid slider data');
        }
    }

    /** True if change is allowed in edit mode.
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
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
}
