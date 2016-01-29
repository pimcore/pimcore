<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

class Object_Data_QuantityValue
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
     * @var QuantityValue_Unit
     */
    public $unit;


    public function __construct($value = null, $unitId = null)
    {
        $this->value = $value;
        $this->unitId = $unitId;
        $this->unit = null;
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


    public function getUnit()
    {
        if (empty($this->unit)) {
            $this->unit = Pimcore\Model\Object\QuantityValue\Unit::getById($this->unitId);
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

    public function __toString()
    {
        $value = $this->getValue();
        if (is_numeric($value)) {
            try {
                $locale = Zend_Registry::get("Zend_Locale");
            } catch (Exception $e) {
            }

            if ($locale) {
                $value = Zend_Locale_Format::toNumber($value, array('locale' => $locale));
            }
        }

        return $value . " " . $this->getUnit()->getAbbreviation();
    }
}
