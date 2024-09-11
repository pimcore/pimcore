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

namespace Pimcore\Model\DataObject\Data;

use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\QuantityValue\UnitConversionService;
use Pimcore\Model\DataObject\Traits\ObjectVarTrait;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

abstract class AbstractQuantityValue implements OwnerAwareFieldInterface
{
    use ObjectVarTrait;
    use OwnerAwareFieldTrait;

    protected string|null $unitId = null;

    protected ?Unit $unit = null;

    public function __construct(Unit|string $unit = null)
    {
        if ($unit instanceof Unit) {
            $this->unit = $unit;
            $this->unitId = $unit->getId();
        } elseif ($unit) {
            $this->unitId = $unit;
        }
        $this->markMeDirty();
    }

    public function setUnitId(string $unitId): void
    {
        $this->unitId = $unitId;
        $this->unit = null;
        $this->markMeDirty();
    }

    public function getUnitId(): string|null
    {
        return $this->unitId;
    }

    public function getUnit(): ?Unit
    {
        if (empty($this->unit) && !empty($this->unitId)) {
            $this->unit = Unit::getById($this->unitId);
        }

        return $this->unit;
    }

    /**
     * @param string|Unit $unit target unit. if string provided, unit is tried to be found by abbreviation
     *
     * @throws Exception
     */
    public function convertTo(Unit|string $unit): AbstractQuantityValue
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

    abstract public function getValue(): mixed;

    abstract public function __toString(): string;
}
