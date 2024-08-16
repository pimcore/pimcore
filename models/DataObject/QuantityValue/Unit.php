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

namespace Pimcore\Model\DataObject\QuantityValue;

use Pimcore\Cache;
use Pimcore\Event\DataObjectQuantityValueEvents;
use Pimcore\Event\Model\DataObject\QuantityValueUnitEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\QuantityValue\Unit\Dao getDao()
 */
class Unit extends Model\AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    const CACHE_KEY = 'quantityvalue_units_table';

    protected ?string $id = null;

    protected ?string $abbreviation = null;

    protected ?string $group = null;

    protected ?string $longname = null;

    protected ?string $baseunit = null;

    protected ?string $reference = null;

    protected ?float $factor = null;

    protected ?float $conversionOffset = null;

    protected ?string $converter = null;

    public static function getByAbbreviation(string $abbreviation): ?Unit
    {
        try {
            $unit = new self();
            $unit->getDao()->getByAbbreviation($abbreviation);

            return $unit;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public static function getByReference(string $reference): ?Unit
    {
        try {
            $unit = new self();
            $unit->getDao()->getByReference($reference);

            return $unit;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public static function getById(string $id): ?Unit
    {
        $table = Service::getQuantityValueUnitsTable();

        return $table[$id] ?? null;
    }

    public static function create(array $values = []): Unit
    {
        $unit = new self();
        $unit->setValues($values);

        return $unit;
    }

    public function save(): void
    {
        $isUpdate = false;
        if ($this->getId()) {
            $isUpdate = true;
            $this->dispatchEvent(new QuantityValueUnitEvent($this), DataObjectQuantityValueEvents::UNIT_PRE_UPDATE);
        } else {
            $this->dispatchEvent(new QuantityValueUnitEvent($this), DataObjectQuantityValueEvents::UNIT_PRE_ADD);
        }

        $this->getDao()->save();
        Cache\RuntimeCache::set(self::CACHE_KEY, null);
        Cache::remove(self::CACHE_KEY);

        if ($isUpdate) {
            $this->dispatchEvent(new QuantityValueUnitEvent($this), DataObjectQuantityValueEvents::UNIT_POST_UPDATE);
        } else {
            $this->dispatchEvent(new QuantityValueUnitEvent($this), DataObjectQuantityValueEvents::UNIT_POST_ADD);
        }
    }

    public function delete(): void
    {
        $this->dispatchEvent(new QuantityValueUnitEvent($this), DataObjectQuantityValueEvents::UNIT_PRE_DELETE);
        $this->getDao()->delete();
        Cache\RuntimeCache::set(self::CACHE_KEY, null);
        Cache::remove(self::CACHE_KEY);
        $this->dispatchEvent(new QuantityValueUnitEvent($this), DataObjectQuantityValueEvents::UNIT_POST_DELETE);
    }

    public function __toString(): string
    {
        return ucfirst($this->getAbbreviation() . ' (' . $this->getId() . ')');
    }

    /**
     * @return $this
     */
    public function setAbbreviation(?string $abbreviation): static
    {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    public function getAbbreviation(): ?string
    {
        return $this->abbreviation;
    }

    /**
     * @return $this
     */
    public function setBaseunit(Unit|string|null $baseunit): static
    {
        if ($baseunit instanceof self) {
            $baseunit = $baseunit->getId();
        }
        $this->baseunit = $baseunit;

        return $this;
    }

    public function getBaseunit(): ?Unit
    {
        if ($this->baseunit) {
            return self::getById($this->baseunit);
        }

        return null;
    }

    /**
     * @return $this
     */
    public function setFactor(?float $factor): static
    {
        $this->factor = $factor;

        return $this;
    }

    public function getFactor(): ?float
    {
        return $this->factor;
    }

    /**
     * @return $this
     */
    public function setGroup(?string $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * @return $this
     */
    public function setId(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setLongname(?string $longname): static
    {
        $this->longname = $longname;

        return $this;
    }

    public function getLongname(): ?string
    {
        return $this->longname;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     * @return $this
     */
    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getConversionOffset(): ?float
    {
        return $this->conversionOffset;
    }

    /**
     * @return $this
     */
    public function setConversionOffset(?float $conversionOffset): static
    {
        $this->conversionOffset = $conversionOffset;

        return $this;
    }

    public function getConverter(): ?string
    {
        return $this->converter;
    }

    /**
     * @return $this
     */
    public function setConverter(?string $converter): static
    {
        $this->converter = $converter;

        return $this;
    }
}
