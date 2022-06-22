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

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Cache;
use Pimcore\Cache\Runtime;
use Pimcore\Event\DataObjectClassificationStoreEvents;
use Pimcore\Event\Model\DataObject\ClassificationStore\KeyConfigEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao getDao()
 */
final class KeyConfig extends Model\AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    /**
     * @var int|null
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
     * @var int|null
     */
    protected $creationDate;

    /**
     * @var int|null
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
     * @param null|bool $force
     *
     * @return self|null
     */
    public static function getById($id, ?bool $force = false)
    {
        $id = (int)$id;
        $cacheKey = self::getCacheKey($id);

        try {
            if (!$force && Cache\Runtime::isRegistered($cacheKey)) {
                return Cache\Runtime::get($cacheKey);
            }

            if (!$force && $config = Cache::load($cacheKey)) {
                Cache\Runtime::set($cacheKey, $config);

                return $config;
            }
            $config = new self();
            $config->getDao()->getById($id);

            Cache\Runtime::set($cacheKey, $config);
            Cache::save($config, $cacheKey);

            return $config;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @param string $name
     * @param int $storeId
     * @param bool $force
     *
     * @return self|null
     *
     * @throws \Exception
     */
    public static function getByName($name, $storeId = 1, ?bool $force = false)
    {
        $cacheKey = self::getCacheKey($storeId, $name);

        try {
            if (!$force && Cache\Runtime::isRegistered($cacheKey)) {
                return Cache\Runtime::get($cacheKey);
            }

            if (!$force && ($config = Cache::load($cacheKey))) {
                Cache\Runtime::set($cacheKey, $config);

                return $config;
            }

            $config = new self();
            $config->setName($name);
            $config->setStoreId($storeId ? $storeId : 1);
            $config->getDao()->getByName();

            Cache\Runtime::set($cacheKey, $config);
            Cache::save($config, $cacheKey);

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
     * @return int|null
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
     * Returns the description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description.
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

        $this->dispatchEvent(new KeyConfigEvent($this), DataObjectClassificationStoreEvents::KEY_CONFIG_PRE_DELETE);
        if ($this->getId()) {
            self::removeCache();
        }

        $this->getDao()->delete();
        $this->dispatchEvent(new KeyConfigEvent($this), DataObjectClassificationStoreEvents::KEY_CONFIG_POST_DELETE);
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
            self::removeCache();

            $isUpdate = true;
            $this->dispatchEvent(new KeyConfigEvent($this), DataObjectClassificationStoreEvents::KEY_CONFIG_PRE_UPDATE);
        } else {
            $this->dispatchEvent(new KeyConfigEvent($this), DataObjectClassificationStoreEvents::KEY_CONFIG_PRE_ADD);
        }

        $this->getDao()->save();

        if ($isUpdate) {
            $this->dispatchEvent(new KeyConfigEvent($this), DataObjectClassificationStoreEvents::KEY_CONFIG_POST_UPDATE);
        } else {
            $this->dispatchEvent(new KeyConfigEvent($this), DataObjectClassificationStoreEvents::KEY_CONFIG_POST_ADD);
        }
    }

    /**
     * @return int|null
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
     * @return int|null
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

    /**
     * Calculate cache key
     *
     * @param int $id
     * @param string|null $name
     *
     * @return string
     */
    private static function getCacheKey(int $id, string $name = null): string
    {
        $cacheKey = 'cs_keyconfig_' . $id;
        if ($name !== null) {
            $cacheKey .= '_' . md5($name);
        }

        return $cacheKey;
    }

    /**
     * @internal
     */
    private function removeCache(): void
    {
        // Remove runtime cache
        Runtime::set(self::getCacheKey($this->getId()), null);
        Runtime::set(self::getCacheKey($this->getStoreId(), $this->getName()), null);

        // Remove persisted cache
        Cache::remove(self::getCacheKey($this->getId()));
        Cache::remove(self::getCacheKey($this->getStoreId(), $this->getName()));
    }
}
