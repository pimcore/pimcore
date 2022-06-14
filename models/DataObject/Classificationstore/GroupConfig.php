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
use Pimcore\Event\Model\DataObject\ClassificationStore\GroupConfigEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Model;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\GroupConfig\Dao getDao()
 * @method int hasChildren()
 */
final class GroupConfig extends Model\AbstractModel
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

    /**
     * Parent id
     *
     * @var int|null
     */
    protected $parentId;

    /**
     * The group name.
     *
     * @var string
     */
    protected $name;

    /**
     * The group description.
     *
     * @var string
     */
    protected $description;

    /**
     * @var int|null
     */
    protected $creationDate;

    /**
     * @var int|null
     */
    protected $modificationDate;

    /**
     * @param int $id
     * @param bool|null $force
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
     * @param bool|null $force
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
            if (!$force && $config = Cache::load($cacheKey)) {
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
     * @return Model\DataObject\Classificationstore\GroupConfig
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
     * @return int|null
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param int $parentId
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
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
     * @return Model\DataObject\Classificationstore\GroupConfig
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Deletes the key value group configuration
     */
    public function delete()
    {
        $this->dispatchEvent(new GroupConfigEvent($this), DataObjectClassificationStoreEvents::GROUP_CONFIG_PRE_DELETE);
        if ($this->getId()) {
            self::removeCache();
        }

        $this->getDao()->delete();
        $this->dispatchEvent(new GroupConfigEvent($this), DataObjectClassificationStoreEvents::GROUP_CONFIG_POST_DELETE);
    }

    /**
     * Saves the group config
     */
    public function save()
    {
        $isUpdate = false;

        if ($this->getId()) {
            self::removeCache();

            $isUpdate = true;
            $this->dispatchEvent(new GroupConfigEvent($this), DataObjectClassificationStoreEvents::GROUP_CONFIG_PRE_UPDATE);
        } else {
            $this->dispatchEvent(new GroupConfigEvent($this), DataObjectClassificationStoreEvents::GROUP_CONFIG_PRE_ADD);
        }

        $model = $this->getDao()->save();

        if ($isUpdate) {
            $this->dispatchEvent(new GroupConfigEvent($this), DataObjectClassificationStoreEvents::GROUP_CONFIG_POST_UPDATE);
        } else {
            $this->dispatchEvent(new GroupConfigEvent($this), DataObjectClassificationStoreEvents::GROUP_CONFIG_POST_ADD);
        }

        return $model;
    }

    /**
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $creationDate
     *
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Returns all keys belonging to this group
     *
     * @return KeyGroupRelation[]
     */
    public function getRelations()
    {
        $list = new KeyGroupRelation\Listing();
        $list->setCondition('groupId = ' . $this->id);
        $list = $list->load();

        return $list;
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
        $cacheKey = 'cs_groupconfig_' . $id;
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
