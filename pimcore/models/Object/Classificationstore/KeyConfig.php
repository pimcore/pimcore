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

namespace Pimcore\Model\Object\Classificationstore;

use Pimcore\Event\Model\Object\ClassificationStore\KeyConfigEvent;
use Pimcore\Event\ObjectClassificationStoreEvents;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\Object\Classificationstore\KeyConfig\Dao getDao()
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
     * @var int
     */
    public $id;

    /**
     * Store ID
     *
     * @var int
     */
    public $storeId = 1;

    /** The key
     * @var string
     */
    public $name;

    /** Pseudo column for title
     * @var string
     */
    public $title;

    /** The key description.
     * @var
     */
    public $description;

    /** The key type ("text", "number", etc...)
     * @var
     */
    public $type;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @var string
     */
    public $definition;

    /** @var bool */
    public $enabled;

    /**
     * @param int $id
     *
     * @return Model\Object\Classificationstore\KeyConfig
     */
    public static function getById($id)
    {
        try {
            $id = intval($id);
            if (self::$cacheEnabled && self::$cache[$id]) {
                return self::$cache[$id];
            }

            $cacheKey = "cs_keyconfig_" . $id;
            $config = Cache::load($cacheKey);
            if ($config) {
                return $config;
            }

            $config = new self();
            $config->setId($id);

            $config->getDao()->getById();
            if (self::$cacheEnabled) {
                self::$cache[$id] = $config;
            }

            Cache::save($config, $cacheKey, [], null, 0, true);

            return $config;
        } catch (\Exception $e) {
        }
    }

    /**
     * @param bool $cacheEnabled
     */
    public static function setCacheEnabled($cacheEnabled)
    {
        self::$cacheEnabled = $cacheEnabled;
        if (!$cacheEnabled) {
            self::$cache = [];
        }
    }

    /**
     * @return bool
     */
    public static function getCacheEnabled()
    {
        return self::$cacheEnabled;
    }

    /**
     * @param $name
     * @param int $storeId
     *
     * @return KeyConfig
     *
     * @internal param null $groupId
     */
    public static function getByName($name, $storeId = 1)
    {
        try {
            $config = new self();
            $config->setName($name);
            $config->setStoreId($storeId ? $storeId : 1);
            $config->getDao()->getByName();

            return $config;
        } catch (\Exception $e) {
        }
    }

    /**
     * @return Model\Object\Classificationstore\KeyConfig
     */
    public static function create()
    {
        $config = new self();
        $config->save();

        return $config;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
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
     *
     * @return Model\Object\Classificationstore\KeyConfig
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
        DefinitionCache::clear($this);

        \Pimcore::getEventDispatcher()->dispatch(ObjectClassificationStoreEvents::KEY_CONFIG_PRE_DELETE, new KeyConfigEvent($this));
        if ($this->getId()) {
            unset(self::$cache[$this->getId()]);
            $cacheKey = "cs_keyconfig_" . $this->getId();
            Cache::remove($cacheKey);
        }
        parent::delete();
        \Pimcore::getEventDispatcher()->dispatch(ObjectClassificationStoreEvents::KEY_CONFIG_POST_DELETE, new KeyConfigEvent($this));
    }

    /**
     * Saves the key config
     */
    public function save()
    {
        DefinitionCache::clear($this);

        $isUpdate = false;

        $def = json_decode($this->definition, true);
        if ($def && isset($def['title'])) {
            $this->title = $def['title'];
        } else {
            $this->title = null;
        }

        if ($this->getId()) {
            unset(self::$cache[$this->getId()]);
            $cacheKey = "cs_keyconfig_" . $this->getId();
            Cache::remove($cacheKey);

            $isUpdate = true;
            \Pimcore::getEventDispatcher()->dispatch(ObjectClassificationStoreEvents::KEY_CONFIG_PRE_UPDATE, new KeyConfigEvent($this));
        } else {
            \Pimcore::getEventDispatcher()->dispatch(ObjectClassificationStoreEvents::KEY_CONFIG_PRE_ADD, new KeyConfigEvent($this));
        }

        $model = parent::save();

        if ($isUpdate) {
            \Pimcore::getEventDispatcher()->dispatch(ObjectClassificationStoreEvents::KEY_CONFIG_POST_UPDATE, new KeyConfigEvent($this));
        } else {
            \Pimcore::getEventDispatcher()->dispatch(ObjectClassificationStoreEvents::KEY_CONFIG_POST_ADD, new KeyConfigEvent($this));
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
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param int $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }
}
