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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\QuantityValue;

use Pimcore\Cache;
use Pimcore\Logger;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\Object\QuantityValue\Unit\Dao getDao()
 */
class Unit extends Model\AbstractModel
{
    const CACHE_KEY = 'quantityvalue_units_table';

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $abbreviation;

    /**
     * @var string
     */
    public $group;

    /**
     * @var string
     */
    public $longname;

    /**
     * @var string
     */
    public $baseunit;

    /**
     * @var string
     */
    public $reference;

    /**
     * @var float
     */
    public $factor;

    /**
     * @var float
     */
    public $conversionOffset;

    /**
     * @param string $abbreviation
     *
     * @return Unit
     */
    public static function getByAbbreviation($abbreviation)
    {
        $unit = new self();
        $unit->getDao()->getByAbbreviation($abbreviation);

        return $unit;
    }

    /**
     * @param string $reference
     *
     * @return Unit
     */
    public static function getByReference($reference)
    {
        $unit = new self();
        $unit->getDao()->getByReference($reference);

        return $unit;
    }

    /**
     * @param string $id
     *
     * @return Unit
     */
    public static function getById($id)
    {
        try {
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
                $list = new Model\Object\QuantityValue\Unit\Listing();
                $list = $list->load();
                /** @var $item Model\Object\QuantityValue\Unit */
                foreach ($list as $item) {
                    $table[$item->getId()] = $item;
                }

                Cache::save($table, self::CACHE_KEY, [], null, 995, true);
                Cache\Runtime::set(self::CACHE_KEY, $table);
            }
        } catch (\Exception $e) {
            Logger::error($e);
        }

        if (isset($table[$id])) {
            return $table[$id];
        }
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
        $this->getDao()->save();
        Cache\Runtime::set(self::CACHE_KEY, null);
        Cache::remove(self::CACHE_KEY);
    }

    public function delete()
    {
        $this->getDao()->delete();
        Cache\Runtime::set(self::CACHE_KEY, null);
        Cache::remove(self::CACHE_KEY);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return ucfirst($this->getAbbreviation() . ' (' . $this->getId() . ')');
    }

    public function setAbbreviation($abbreviation)
    {
        $this->abbreviation = $abbreviation;
    }

    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

    public function setBaseunit($baseunit)
    {
        $this->baseunit = $baseunit;
    }

    public function getBaseunit()
    {
        return $this->baseunit;
    }

    public function setFactor($factor)
    {
        $this->factor = $factor;
    }

    public function getFactor()
    {
        return $this->factor;
    }

    public function setGroup($group)
    {
        $this->group = $group;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setLongname($longname)
    {
        $this->longname = $longname;
    }

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
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    /**
     * @return float
     */
    public function getConversionOffset()
    {
        return $this->conversionOffset;
    }

    /**
     * @param float $conversionOffset
     */
    public function setConversionOffset($conversionOffset)
    {
        $this->conversionOffset = $conversionOffset;
    }
}
