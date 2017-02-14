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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Data;

use Pimcore\Model\Object\QuantityValue\Unit;

class QuantityValue
{
    /**
     * @var double | string
     */
    public $value;

    /**
     * @var int
     */
    public $unitId;

    /**
     * @var \Pimcore\Model\Object\QuantityValue\Unit
     */
    public $unit;

    /**
     * QuantityValue constructor.
     * @param null $value
     * @param null $unitId
     */
    public function __construct($value = null, $unitId = null)
    {
        $this->value = $value;
        $this->unitId = $unitId;
        $this->unit = "";

        if ($unitId) {
            $this->unit = Unit::getById($this->unitId);
        }
    }


    /**
     * @param  $unitId
     * @return void
     */
    public function setUnitId($unitId)
    {
        $this->unitId = $unitId;
        $this->unit = null;
    }

    /**
     * @return int
     */
    public function getUnitId()
    {
        return $this->unitId;
    }

    /**
     * @return Unit
     */
    public function getUnit()
    {
        if (empty($this->unit)) {
            $this->unit = Unit::getById($this->unitId);
        }

        return $this->unit;
    }

    /**
     * @param  $value
     * @return void
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return double
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     * @throws \Zend_Locale_Exception
     */
    public function __toString()
    {
        $value = $this->getValue();
        if (is_numeric($value)) {
            $locale = null;
            try {
                $locale = \Zend_Registry::get("Zend_Locale");
            } catch (\Exception $e) {
            }

            if ($locale) {
                $value = \Zend_Locale_Format::toNumber($value, ['locale' => $locale]);
            }
        }

        if ($this->getUnit() instanceof Unit) {
            $value .= " " . $this->getUnit()->getAbbreviation();
        }

        return $value;
    }
}
