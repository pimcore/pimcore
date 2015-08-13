<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Classificationstore;

use Pimcore\Model;

class KeyConfig extends Model\AbstractModel {

    /**
     * @var array
     */
    static $cache = array();

    /**
     * @var bool
     */
    static $cacheEnabled = false;

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


    /**
     * @var integer
     */
    public $creationDate;

    /**
     * @var integer
     */
    public $modificationDate;

    /**
     * @var string
     */
    public $definition;

    /** @var  boolean */
    public $enabled;

    /** @var  int */
    public $sorter;


    /**
     * @param integer $id
     * @return Model\Object\Classificationstore\KeyConfig
     */
    public static function getById($id) {
        try {
            $id = intval($id);
            if (self::$cacheEnabled && self::$cache[$id]) {
                return self::$cache[$id];
            }
            $config = new self();
            $config->setId($id);
            $config->getResource()->getById();
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
        if(!$cacheEnabled){
            self::$cache = array();
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
    public static function getByName ($name, $groupId = null) {
        try {
            $config = new self();
            $config->setName($name);
            $config->setGroup($groupId);
            $config->getResource()->getByName();

            return $config;
        } catch (\Exception $e) {

        }
    }

    /**
     * @return Model\Object\Classificationstore\KeyConfig
     */
    public static function create() {
        $config = new self();
        $config->save();

        return $config;
    }


    /**
     * @param integer $id
     * @return void
     */
    public function setId($id) {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param string name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /** Returns the key description.
     * @return mixed
     */
    public function getDescription() {
        return $this->description;
    }

    /** Sets the key description
     * @param $description
     * @return Model\Object\Classificationstore\KeyConfig
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }



    /**
     * Deletes the key value key configuration
     */
    public function delete() {
        DefinitionCache::clear($this);

        \Pimcore::getEventManager()->trigger("object.Classificationstore.keyConfig.preDelete", $this);
        if ($this->getId()) {
            unset(self::$cache[$this->getId()]);
        }
        parent::delete();
        \Pimcore::getEventManager()->trigger("object.Classificationstore.keyConfig.postDelete", $this);
    }

    /**
     * Saves the key config
     */
    public function save() {
        DefinitionCache::clear($this);

        $isUpdate = false;

        if ($this->getId()) {
            unset(self::$cache[$this->getId()]);
            $isUpdate = true;
            \Pimcore::getEventManager()->trigger("object.Classificationstore.keyConfig.preUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("object.Classificationstore.keyConfig.preAdd", $this);
        }

        $model = parent::save();

        if ($isUpdate) {
            \Pimcore::getEventManager()->trigger("object.Classificationstore.keyConfig.postUpdate", $this);
        } else {
            \Pimcore::getEventManager()->trigger("object.Classificationstore.keyConfig.postAdd", $this);
        }
        return $model;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param int $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $modificationDate
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param string $definition
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }

    /**
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param mixed $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return int
     */
    public function getSorter()
    {
        return $this->sorter;
    }

    /**
     * @param int $sorter
     */
    public function setSorter($sorter)
    {
        $this->sorter = $sorter;
    }




}