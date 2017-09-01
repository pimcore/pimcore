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

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\QuantityValue\Unit;

class QuantityValue
{
    /**
     * @var float | string
     */
    public $value;

    /**
     * @var int
     */
    public $unitId;

    /**
     * @var \Pimcore\Model\DataObject\QuantityValue\Unit
     */
    public $unit;

    /**
     * QuantityValue constructor.
     *
     * @param null $value
     * @param null $unitId
     */
    public function __construct($value = null, $unitId = null)
    {
        $this->value = $value;
        $this->unitId = $unitId;
        $this->unit = '';

        if ($unitId) {
            $this->unit = Unit::getById($this->unitId);
        }
    }

    /**
     * @param  $unitId
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
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function __toString()
    {
        $value = $this->getValue();
        if (is_numeric($value)) {
            $locale = \Pimcore::getContainer()->get('pimcore.locale')->findLocale();

            if ($locale) {
                $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
                $value = $formatter->format($value);
            }
        }

        if ($this->getUnit() instanceof Unit) {
            $value .= ' ' . $this->getUnit()->getAbbreviation();
        }

        return $value;
    }
}
