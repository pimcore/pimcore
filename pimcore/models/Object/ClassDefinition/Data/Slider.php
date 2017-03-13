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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;

class Slider extends Model\Object\ClassDefinition\Data
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "slider";

    /**
     * @var integer
     */
    public $width;

    /**
     * @var integer
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
     * @var boolean
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
    public $queryColumnType = "double";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "double";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "float";

    /**
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param integer $width
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
    }

    /**
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param integer $height
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
     * @param $maxValue
     * @return $this
     * @internal param float $minValue
     */
    public function setMaxValue($maxValue)
    {
        $this->maxValue = $this->getAsFloatCast($maxValue);

        return $this;
    }

    /**
     * @return boolean
     */
    public function getVertical()
    {
        return $this->vertical;
    }

    /**
     * @return integer
     */
    public function getDefaultValue()
    {
        return null;
    }


    /**
     * @param boolean $vertical
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
     * @return $this
     */
    public function setDecimalPrecision($decimalPrecision)
    {
        $this->decimalPrecision = $this->getAsIntegerCast($decimalPrecision);

        return $this;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param float $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return float
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        return (float) $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param float $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return float
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        return (float) $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param float $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return float
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param float $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return float
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param float $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return float
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $this->getDataFromResource($data, $object, $params);
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param float $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     * @return float
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param boolean $omitMandatoryCheck
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if (!$omitMandatoryCheck and $this->getMandatory() and $data === null) {
            throw new Model\Element\ValidationException("Empty mandatory field [ ".$this->getName()." ] ".strval($data));
        }

        if (!empty($data) and !is_numeric($data)) {
            throw new Model\Element\ValidationException("invalid slider data");
        }
    }


    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition)
    {
        $this->minValue = $masterDefinition->minValue;
        $this->maxValue = $masterDefinition->maxValue;
        $this->vertical = $masterDefinition->vertical;
        $this->increment = $masterDefinition->increment;
        $this->decimalPrecision = $masterDefinition->decimalPrecision;
    }
}
