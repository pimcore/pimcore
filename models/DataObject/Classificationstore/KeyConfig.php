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

namespace Pimcore\Model\DataObject\Classificationstore;

use Exception;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
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

    protected ?int $id = null;

    /**
     * Store ID
     *
     */
    protected int $storeId = 1;

    /** The key
     */
    protected string $name;

    /** Pseudo column for title
     */
    protected ?string $title = null;

    /**
     * The key description.
     */
    protected ?string $description = null;

    /**
     * The key type ("text", "number", etc...)
     *
     */
    protected string $type;

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    protected string $definition = '[]';

    protected bool $enabled = false;

    public static function getById(int $id, ?bool $force = false): ?KeyConfig
    {
        $cacheKey = self::getCacheKey($id);

        try {
            if (!$force && Cache\RuntimeCache::isRegistered($cacheKey)) {
                return Cache\RuntimeCache::get($cacheKey);
            }

            if (!$force && $config = Cache::load($cacheKey)) {
                Cache\RuntimeCache::set($cacheKey, $config);

                return $config;
            }
            $config = new self();
            $config->getDao()->getById($id);

            Cache\RuntimeCache::set($cacheKey, $config);
            Cache::save($config, $cacheKey);

            return $config;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     *
     * @throws Exception
     */
    public static function getByName(string $name, int $storeId = 1, ?bool $force = false): ?KeyConfig
    {
        $cacheKey = self::getCacheKey($storeId, $name);

        try {
            if (!$force && Cache\RuntimeCache::isRegistered($cacheKey)) {
                return Cache\RuntimeCache::get($cacheKey);
            }

            if (!$force && ($config = Cache::load($cacheKey))) {
                Cache\RuntimeCache::set($cacheKey, $config);

                return $config;
            }

            $config = new self();
            $config->setName($name);
            $config->setStoreId($storeId ? $storeId : 1);
            $config->getDao()->getByName();

            Cache\RuntimeCache::set($cacheKey, $config);
            Cache::save($config, $cacheKey);

            return $config;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public static function create(): KeyConfig
    {
        $config = new self();
        $config->save();

        return $config;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets the description.
     *
     * @return $this
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Deletes the key value key configuration
     */
    public function delete(): void
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
    public function save(): void
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

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function setCreationDate(int $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setModificationDate(int $modificationDate): void
    {
        $this->modificationDate = $modificationDate;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getDefinition(): string
    {
        return $this->definition;
    }

    public function setDefinition(string $definition): void
    {
        $this->definition = $definition;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function setStoreId(int $storeId): void
    {
        $this->storeId = $storeId;
    }

    /**
     * Calculate cache key
     */
    private static function getCacheKey(int $id, string $name = null): string
    {
        $cacheKey = 'cs_keyconfig_' . $id;
        if ($name !== null) {
            $cacheKey .= '_' . md5($name);
        }

        return $cacheKey;
    }

    private function removeCache(): void
    {
        // Remove runtime cache
        if (RuntimeCache::getInstance()->offsetExists(self::getCacheKey($this->getId()))) {
            RuntimeCache::getInstance()->offsetUnset(self::getCacheKey($this->getId()));
        }
        if (RuntimeCache::getInstance()->offsetExists(self::getCacheKey($this->getStoreId(), $this->getName()))) {
            RuntimeCache::getInstance()->offsetUnset(self::getCacheKey($this->getStoreId(), $this->getName()));
        }

        // Remove persisted cache
        Cache::remove(self::getCacheKey($this->getId()));
        Cache::remove(self::getCacheKey($this->getStoreId(), $this->getName()));
    }
}
