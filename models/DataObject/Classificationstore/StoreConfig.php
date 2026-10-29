<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore;
use Pimcore\Event\DataObjectClassificationStoreEvents;
use Pimcore\Event\Model\DataObject\ClassificationStore\StoreConfigEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Model;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\StoreConfig\Dao getDao()
 */
final class StoreConfig extends Model\AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    protected ?int $id = null;

    /**
     * The store name.
     *
     */
    protected ?string $name = null;

    /**
     * The store description.
     *
     */
    protected ?string $description = null;

    /**
     * The name of the storage engine, null for the default one.
     *
     */
    protected ?string $engine = null;

    /**
     * Whether `engine` carries a value that was loaded or explicitly set.
     *
     * @internal
     */
    protected bool $engineInitialized = false;

    public static function getById(int $id): ?StoreConfig
    {
        try {
            $config = new self();
            $config->getDao()->getById($id);

            return $config;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public static function getByName(string $name): ?StoreConfig
    {
        try {
            $config = new self();
            $config->getDao()->getByName($name);

            return $config;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public static function create(): StoreConfig
    {
        $config = new self();
        $config->save();

        return $config;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Returns the description.
     *
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets the description.
     *
     *
     * @return Model\DataObject\Classificationstore\StoreConfig
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getEngine(): ?string
    {
        return $this->engine;
    }

    public function setEngine(?string $engine): static
    {
        $this->engine = $engine;
        $this->engineInitialized = true;

        return $this;
    }

    /**
     * @internal
     */
    public function isEngineInitialized(): bool
    {
        return $this->engineInitialized;
    }

    /**
     * Deletes the key value group configuration
     */
    public function delete(): void
    {
        $this->dispatchEvent(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_PRE_DELETE);
        $this->getDao()->delete();
        $this->invalidateResolvedEngine();
        $this->dispatchEvent(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_POST_DELETE);
    }

    /**
     * Saves the store config
     */
    public function save(): void
    {
        $isUpdate = false;

        if ($this->getId()) {
            $isUpdate = true;
            $this->dispatchEvent(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_PRE_UPDATE);
        } else {
            $this->dispatchEvent(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_PRE_ADD);
        }

        $this->getDao()->save();
        $this->invalidateResolvedEngine();

        if ($isUpdate) {
            $this->dispatchEvent(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_POST_UPDATE);
        } else {
            $this->dispatchEvent(new StoreConfigEvent($this), DataObjectClassificationStoreEvents::STORE_CONFIG_POST_ADD);
        }
    }

    /**
     * The resolver memoizes which engine this store resolves to, so a write that may have changed
     * the engine column has to drop that entry rather than wait for the next kernel reset.
     */
    private function invalidateResolvedEngine(): void
    {
        $id = $this->getId();
        if ($id === null || !Pimcore::hasContainer()) {
            return;
        }

        Pimcore::getContainer()->get(EngineResolver::class)->invalidateStore($id);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
