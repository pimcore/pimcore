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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\KeyValue;

use Pimcore\Model;

/**
 * @deprecated will be removed entirely in Pimcore 5
 * @method \Pimcore\Model\Object\KeyValue\KeyConfig\Dao getDao()
 */
class KeyConfig extends Model\AbstractModel
{

    /**
     * @var array
     */
    public static $cache = [];

    /**
     * @var bool
     */
    public static $cacheEnabled = false;

    /**
     * @var integer
     */
    public $id;

    /** The key
     * @var string
     */
    public $name;

    /** The key description.
     * @var
     */
    public $description;

    /** The key type ("text", "number", etc...)
     * @var
     */
    public $type;

    /** Unit information (just for information)
     * @var
     */
    public $unit;

    /** The group id.
     * @var
     */
    public $group;

    /** Array of possible vales ("select" datatype)
     * @var
     */
    public $possiblevalues;

    /**
     * @var integer
     */
    public $creationDate;

    /**
     * @var integer
     */
    public $modificationDate;

    /**
     * @var
     */
    public $translator;

    /**
     * @var
     */
    public $mandatory;

    /** Sets the translator id.
     * @param $translator
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /** Returns the translator id.
     * @return mixed
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @param $unit
     * @return $this
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param $values
     * @return $this
     */
    public function setPossibleValues($values)
    {
        $this->possiblevalues = $values;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPossibleValues()
    {
        return $this->possiblevalues;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $group
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param integer $id
     * @return Model\Object\KeyValue\KeyConfig
     */
    public static function getById($id)
    {
        try {
            $id = intval($id);
            if (self::$cacheEnabled && self::$cache[$id]) {
                return self::$cache[$id];
            }
            $config = new self();
            $config->setId($id);
            $config->getDao()->getById();
            if (self::$cacheEnabled) {
                self::$cache[$id] = $config;
            }

            return $config;
        } catch (\Exception $e) {
        }
    }

    /**
     * @param boolean $cacheEnabled
     */
    public static function setCacheEnabled($cacheEnabled)
    {
        self::$cacheEnabled = $cacheEnabled;
        if (!$cacheEnabled) {
            self::$cache = [];
        }
    }

    /**
     * @return boolean
     */
    public static function getCacheEnabled()
    {
        return self::$cacheEnabled;
    }

    /**
     * @param $name
     * @param null $groupId
     * @return KeyConfig
     */
    public static function getByName($name, $groupId = null)
    {
        try {
            $config = new self();
            $config->setName($name);
            $config->setGroup($groupId);
            $config->getDao()->getByName();

            return $config;
        } catch (\Exception $e) {
        }
    }

    /**
     * @return Model\Object\KeyValue\KeyConfig
     */
    public static function create()
    {
        $config = new self();
        $config->save();

        return $config;
    }


    /**
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /** Returns the key description.
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /** Sets the key description
     * @param $description
     * @return Model\Object\KeyValue\KeyConfig
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }


    /**
     * Deletes the key value key configuration
     */
    public function delete()
    {
        \Pimcore::getEventManager()->trigger("object.keyValue.keyConfig.preDelete", $this);
        if ($this->getId()) {
            unset(self::$cache[$this->getId()]);
        }
        parent::delete();
        \Pimcore::getEventManager()->trigger("object.keyValue.keyConfig.postDelete", $this);
    }

    /**
     * Saves the key config
     */
    public function save()
    {
        $isUpdate = false;

        if ($this->getId()) {
            unset(self::$cache[$this->getId()]);
            $isUpdate = true;
            \Pimcore::getEventManager()->trigger("object.keyValue.keyConfig.preUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("object.keyValue.keyConfig.preAdd", $this);
        }

        $model = $this->getDao()->save();

        if ($isUpdate) {
            \Pimcore::getEventManager()->trigger("object.keyValue.keyConfig.postUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("object.keyValue.keyConfig.postAdd", $this);
        }

        return $model;
    }

    /**
     * @param $creationDate
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param $modificationDate
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param mixed $mandatory
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = (bool)$mandatory;
    }

    /**
     * @return mixed
     */
    public function getMandatory()
    {
        return $this->mandatory;
    }
}
