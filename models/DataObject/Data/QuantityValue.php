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

use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class QuantityValue implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /**
     * @var float|null
     */
    protected $value;

    /**
     * @var string|null
     */
    protected $unitId;

    /**
     * @var Unit|null
     */
    protected $unit;

    /**
     * QuantityValue constructor.
     *
     * @param float|null $value
     * @param Unit|string|null $unit
     */
    public function __construct($value = null, $unit = null)
    {
        $this->value = $value;

        if ($unit instanceof Unit) {
            $this->unit = $unit;
            $this->unitId = $unit->getId();
        } elseif ($unit) {
            $this->unit = Unit::getById($unit);
            $this->unitId = $unit;
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
     * @return string|null
     */
    public function getUnitId()
    {
        return $this->unitId;
    }

    /**
     * @return Unit|null
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
     * @return float|null
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
            $locale = \Pimcore::getContainer()->get(LocaleServiceInterface::class)->findLocale();

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
