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

use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class QuantityValue implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /**
     * @var float
     */
    protected $value;

    /**
     * @var string
     */
    protected $unitId;

    /**
     * @var \Pimcore\Model\DataObject\QuantityValue\Unit|null
     */
    protected $unit;

    /**
     * QuantityValue constructor.
     *
     * @param float|null $value
     * @param int|Unit|null $unitId
     */
    public function __construct($value = null, $unitId = null)
    {
        $this->value = $value;
        $this->unitId = $unitId;
        $this->unit = null;

        if ($unitId instanceof Unit) {
            $this->unit = $unitId;
            $this->unitId = $this->unit->getId();
        } elseif ($unitId) {
            $this->unit = Unit::getById($this->unitId);
        }
        $this->markMeDirty();
    }

    /**
     * @param string $unitId
     */
    public function setUnitId($unitId)
    {
        $this->unitId = $unitId;
        $this->unit = null;
        $this->markMeDirty();
    }

    /**
     * @return string
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
     * @param float $value
     */
    public function setValue($value)
    {
        $this->value = $value;
        $this->markMeDirty();
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

        return $value ? $value : '';
    }
}
