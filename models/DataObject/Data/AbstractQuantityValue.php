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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\Data;

use InvalidArgumentException;
use Pimcore;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\QuantityValue\UnitConversionService;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

abstract class AbstractQuantityValue implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /**
     * @var string|null
     */
    protected $unitId;

    /**
     * @var Unit|null
     */
    protected $unit;

    /**
     * @param Unit|string|null $unit
     */
    public function __construct($unit = null)
    {
        if ($unit instanceof Unit) {
            $this->unit = $unit;
            $this->unitId = $unit->getId();
        } elseif ($unit) {
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
     * @param Unit|string $unit target unit. if string provided, unit is tried to be found by abbreviation
     *
     * @return self
     *
     * @throws \Exception
     */
    public function convertTo($unit)
    {
        if (is_string($unit)) {
            $unitObject = Unit::getByAbbreviation($unit);
            if (!$unitObject instanceof Unit) {
                throw new InvalidArgumentException('Unit with abbreviation "'.$unit.'" does not exist');
            }
            $unit = $unitObject;
        }

        if (!$unit instanceof Unit) {
            throw new InvalidArgumentException('Please provide unit as '.Unit::class.' object or as string');
        }

        /** @var UnitConversionService $converter */
        $converter = Pimcore::getContainer()->get(UnitConversionService::class);

        return $converter->convert($this, $unit);
    }

    abstract public function getValue();

    abstract public function __toString();
}
