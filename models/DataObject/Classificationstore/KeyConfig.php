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

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Cache;
use Pimcore\Event\DataObjectClassificationStoreEvents;
use Pimcore\Event\Model\DataObject\ClassificationStore\KeyConfigEvent;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao getDao()
 */
final class KeyConfig extends Model\AbstractModel
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
    protected $id;

    /**
     * Store ID
     *
     * @var int
     */
    protected $storeId = 1;

    /** The key
     * @var string
     */
    protected $name;

    /** Pseudo column for title
     * @var string|null
     */
    protected $title;

    /**
     * The key description.
     *
     * @var string
     */
    protected $description;

    /**
     * The key type ("text", "number", etc...)
     *
     * @var string
     */
    protected $type;

    /**
     * @var int
     */
    protected $creationDate;

    /**
     * @var int
     */
    protected $modificationDate;

    /**
     * @var string
     */
    protected $definition;

    /** @var bool */
    protected $enabled;

    /**
     * @param int $id
     *
     * @return self|null
     */
    public static function getById($id)
    {
        try {
            $id = (int)$id;
            if (self::$cacheEnabled && self::$cache[$id]) {
                return self::$cache[$id];
            }

            $cacheKey = 'cs_keyconfig_' . $id;
            $config = Cache::load($cacheKey);
            if ($config) {
                return $config;
            }

            $config = new self();
            $config->getDao()->getById($id);
            if (self::$cacheEnabled) {
                self::$cache[$id] = $config;
            }

            Cache::save($config, $cacheKey, [], null, 0, true);

            return $config;
        } catch (\Exception $e) {
            return null;
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
     * @param string $name
     * @param int $storeId
     *
     * @return self|null
     *
     * @throws \Exception
     */
    public static function getByName($name, $storeId = 1)
    {
        try {
            $cacheKey = 'cs_keyconfig_' . $storeId . '_' . md5($name);

            if (self::$cacheEnabled && Cache\Runtime::isRegistered($cacheKey)) {
                $config = Cache\Runtime::get($cacheKey);
                if ($config) {
                    return $config;
                }
            }

            $config = Cache::load($cacheKey);
            if ($config) {
                return $config;
            }

            $config = new self();
            $config->setName($name);
            $config->setStoreId($storeId ? $storeId : 1);
            $config->getDao()->getByName();

            if (self::$cacheEnabled) {
                Cache\Runtime::set($cacheKey, $config);
            }

            Cache::save($config, $cacheKey, [], null, 0, true);

            return $config;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @return Model\DataObject\Classificationstore\KeyConfig
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

    /**
     * Returns the key description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the key description
     *
     * @param string $description
     *
     * @return Model\DataObject\Classificationstore\KeyConfig
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

        \Pimcore::getEventDispatcher()->dispatch(new KeyConfigEvent($this), DataObjectClassificationStoreEvents::KEY_CONFIG_PRE_DELETE);
        if ($this->getId()) {
            unset(self::$cache[$this->getId()]);
            $cacheKey = 'cs_keyconfig_' . $this->getId();
            Cache::remove($cacheKey);
        }
        $this->getDao()->delete();
        \Pimcore::getEventDispatcher()->dispatch(new KeyConfigEvent($this), DataObjectClassificationStoreEvents::KEY_CONFIG_POST_DELETE);
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
            $cacheKey = 'cs_keyconfig_' . $this->getId();
            Cache::remove($cacheKey);

            $isUpdate = true;
            \Pimcore::getEventDispatcher()->dispatch(new KeyConfigEvent($this), DataObjectClassificationStoreEvents::KEY_CONFIG_PRE_UPDATE);
        } else {
            \Pimcore::getEventDispatcher()->dispatch(new KeyConfigEvent($this), DataObjectClassificationStoreEvents::KEY_CONFIG_PRE_ADD);
        }

        $this->getDao()->save();

        if ($isUpdate) {
            \Pimcore::getEventDispatcher()->dispatch(new KeyConfigEvent($this), DataObjectClassificationStoreEvents::KEY_CONFIG_POST_UPDATE);
        } else {
            \Pimcore::getEventDispatcher()->dispatch(new KeyConfigEvent($this), DataObjectClassificationStoreEvents::KEY_CONFIG_POST_ADD);
        }
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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
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
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
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
