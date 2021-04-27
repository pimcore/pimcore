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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\QuantityValue;

use Pimcore\Cache;
use Pimcore\Event\DataObjectQuantityValueEvents;
use Pimcore\Event\Model\DataObject\QuantityValueUnitEvent;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\QuantityValue\Unit\Dao getDao()
 */
class Unit extends Model\AbstractModel
{
    const CACHE_KEY = 'quantityvalue_units_table';

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $abbreviation;

    /**
     * @var string
     */
    protected $group;

    /**
     * @var string
     */
    protected $longname;

    /**
     * @var string
     */
    protected $baseunit;

    /**
     * @var string
     */
    protected $reference;

    /**
     * @var float|null
     */
    protected $factor;

    /**
     * @var float|null
     */
    protected $conversionOffset;

    /**
     * @var string
     */
    protected $converter;

    /**
     * @param string $abbreviation
     *
     * @return self|null
     */
    public static function getByAbbreviation($abbreviation)
    {
        try {
            $unit = new self();
            $unit->getDao()->getByAbbreviation($abbreviation);

            return $unit;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $reference
     *
     * @return self|null
     */
    public static function getByReference($reference)
    {
        try {
            $unit = new self();
            $unit->getDao()->getByReference($reference);

            return $unit;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $id
     *
     * @return Unit|null
     */
    public static function getById($id)
    {
        try {
            $table = null;
            if (Cache\Runtime::isRegistered(self::CACHE_KEY)) {
                $table = Cache\Runtime::get(self::CACHE_KEY);
            }

            if (!is_array($table)) {
                $table = Cache::load(self::CACHE_KEY);
                if (is_array($table)) {
                    Cache\Runtime::set(self::CACHE_KEY, $table);
                }
            }

            if (!is_array($table)) {
                $table = [];
                $list = new Model\DataObject\QuantityValue\Unit\Listing();
                $list = $list->load();
                foreach ($list as $item) {
                    $table[$item->getId()] = $item;
                }

                Cache::save($table, self::CACHE_KEY, [], null, 995, true);
                Cache\Runtime::set(self::CACHE_KEY, $table);
            }
        } catch (\Exception $e) {
            return null;
        }

        if (isset($table[$id])) {
            return $table[$id];
        }

        return null;
    }

    /**
     * @param array $values
     *
     * @return Unit
     */
    public static function create($values = [])
    {
        $unit = new self();
        $unit->setValues($values);

        return $unit;
    }

    public function save()
    {
        $isUpdate = false;
        if ($this->getId()) {
            $isUpdate = true;
            \Pimcore::getEventDispatcher()->dispatch(new QuantityValueUnitEvent($this), DataObjectQuantityValueEvents::UNIT_PRE_UPDATE);
        } else {
            \Pimcore::getEventDispatcher()->dispatch(new QuantityValueUnitEvent($this), DataObjectQuantityValueEvents::UNIT_PRE_ADD);
        }

        $this->getDao()->save();
        Cache\Runtime::set(self::CACHE_KEY, null);
        Cache::remove(self::CACHE_KEY);

        if ($isUpdate) {
            \Pimcore::getEventDispatcher()->dispatch(new QuantityValueUnitEvent($this), DataObjectQuantityValueEvents::UNIT_POST_UPDATE);
        } else {
            \Pimcore::getEventDispatcher()->dispatch(new QuantityValueUnitEvent($this), DataObjectQuantityValueEvents::UNIT_POST_ADD);
        }
    }

    public function delete()
    {
        \Pimcore::getEventDispatcher()->dispatch(new QuantityValueUnitEvent($this), DataObjectQuantityValueEvents::UNIT_PRE_DELETE);
        $this->getDao()->delete();
        Cache\Runtime::set(self::CACHE_KEY, null);
        Cache::remove(self::CACHE_KEY);
        \Pimcore::getEventDispatcher()->dispatch(new QuantityValueUnitEvent($this), DataObjectQuantityValueEvents::UNIT_POST_DELETE);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return ucfirst($this->getAbbreviation() . ' (' . $this->getId() . ')');
    }

    /**
     * @param string $abbreviation
     *
     * @return $this
     */
    public function setAbbreviation($abbreviation)
    {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    /**
     * @return string
     */
    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

    /**
     * @param int|Unit $baseunit
     *
     * @return $this
     */
    public function setBaseunit($baseunit)
    {
        if ($baseunit instanceof self) {
            $baseunit = $baseunit->getId();
        }
        $this->baseunit = $baseunit;

        return $this;
    }

    /**
     * @return Unit|null
     */
    public function getBaseunit()
    {
        if ($this->baseunit) {
            return self::getById($this->baseunit);
        }

        return null;
    }

    /**
     * @param float $factor
     *
     * @return $this
     */
    public function setFactor($factor)
    {
        $this->factor = $factor;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getFactor()
    {
        return $this->factor;
    }

    /**
     * @param string $group
     *
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (string) $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return (string) $this->id;
    }

    /**
     * @param string $longname
     *
     * @return $this
     */
    public function setLongname($longname)
    {
        $this->longname = $longname;

        return $this;
    }

    /**
     * @return string
     */
    public function getLongname()
    {
        return $this->longname;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     *
     * @return $this
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getConversionOffset()
    {
        return $this->conversionOffset;
    }

    /**
     * @param float $conversionOffset
     *
     * @return $this
     */
    public function setConversionOffset($conversionOffset)
    {
        $this->conversionOffset = $conversionOffset;

        return $this;
    }

    /**
     * @return string
     */
    public function getConverter()
    {
        return $this->converter;
    }

    /**
     * @param string $converter
     *
     * @return $this
     */
    public function setConverter($converter)
    {
        $this->converter = (string)$converter;

        return $this;
    }
}
