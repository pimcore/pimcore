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

use Exception;
use Pimcore;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Event\DataObjectClassificationStoreEvents;
use Pimcore\Event\Model\DataObject\ClassificationStore\KeyConfigEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Model;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Classificationstore\Traits\IdentifierTrait;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\KeyConfig\Dao getDao()
 */
final class KeyConfig extends Model\AbstractModel
{
    use IdentifierTrait;
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

    /** The title the key is shown under, seeded from the definition's own title
     */
    protected ?string $title = null;

    /**
     * Whether `title` carries a value that was loaded or explicitly set.
     *
     * @internal
     */
    protected bool $titleInitialized = false;

    /**
     * Whether `title` carries a value the save in progress seeded from the definition.
     *
     * @internal
     */
    protected bool $titleSeeded = false;

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

    /**
     * Whether the key holds a value per language, null for the `localized` flag of the class
     * definition field it is rendered in.
     */
    protected ?bool $localized = null;

    /**
     * Whether `localized` carries a value that was loaded or explicitly set.
     *
     * @internal
     */
    protected bool $localizedInitialized = false;

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

    public static function getByIdentifier(string $identifier, int $storeId = 1): ?KeyConfig
    {
        try {
            $config = new self();
            $config->setStoreId($storeId);
            $config->getDao()->getByIdentifier($identifier);

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
     *
     * @throws Model\Exception\InvalidConfigException
     * @throws Model\DataObject\Exception\UnsupportedEngineFeatureException
     */
    public function save(): void
    {
        // first, so that a refused key consumes no identifier either
        $this->assertTypeFitsTheStoreEngine();

        if ($this->getId() === null) {
            $this->generateIdentifier();
        }
        $this->getDao()->assertIdentifierIsUnique();

        DefinitionCache::clear($this);

        $isUpdate = false;

        $this->seedTitleFromDefinition();

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

    public function getLocalized(): ?bool
    {
        return $this->localized;
    }

    public function setLocalized(?bool $localized): void
    {
        $this->localized = $localized;
        $this->localizedInitialized = true;
    }

    /**
     * @internal
     */
    public function isLocalizedInitialized(): bool
    {
        return $this->localizedInitialized;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->titleInitialized = true;
    }

    /**
     * @internal
     */
    public function isTitleInitialized(): bool
    {
        return $this->titleInitialized;
    }

    /**
     * @internal
     */
    public function isTitleSeeded(): bool
    {
        return $this->titleSeeded;
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
     * Refuses a key whose field type the engine of its store cannot hold, before anything is
     * written and before an identifier is generated for it.
     *
     * Only a type of the accepted set is governed: a type outside it keeps the support core ships
     * for it, which for `calculatedValue` is a computed read and an editmode payload.
     *
     * @throws Model\DataObject\Exception\UnsupportedEngineFeatureException
     */
    private function assertTypeFitsTheStoreEngine(): void
    {
        // the property carries no default, and a key saved to take an id carries no type yet
        $type = $this->getObjectVars()['type'] ?? null;
        if (!is_string($type) || $type === '') {
            return;
        }

        $typeSupport = Pimcore::getContainer()->get(KeyTypeSupport::class);
        if (!$typeSupport->isAcceptedType($type)) {
            return;
        }

        if ($typeSupport->isHeldInValueColumns($type, $this->getDelegateType())) {
            return;
        }

        $engineResolver = Pimcore::getContainer()->get(EngineResolver::class);
        $engine = $engineResolver->resolveForStore($this->storeId);
        if ($engine->supports(EngineFeature::StructuredValues)) {
            return;
        }

        throw new Model\DataObject\Exception\UnsupportedEngineFeatureException(
            $type,
            $this->storeId,
            $engineResolver->nameEngine($engine)
        );
    }

    /**
     * The delegate type an `encryptedField` key carries in its definition, which is what decides
     * what such a key stores. Absent for every other type, and for a delegate that is not set.
     */
    private function getDelegateType(): ?string
    {
        $definition = json_decode($this->definition, true);
        $delegateType = is_array($definition) ? ($definition['delegateDatatype'] ?? null) : null;

        return is_string($delegateType) && $delegateType !== '' ? $delegateType : null;
    }

    /**
     * Seeds the column of a key whose title was neither loaded nor explicitly set. The property is
     * assigned without marking the title initialised, so every save seeds it again.
     */
    private function seedTitleFromDefinition(): void
    {
        $this->titleSeeded = false;

        if ($this->titleInitialized) {
            return;
        }

        $definition = json_decode($this->definition, true);
        $title = is_array($definition) ? ($definition['title'] ?? null) : null;

        // a definition without a title serialises it as null, and an empty one is nothing to seed
        if (!is_string($title) || $title === '') {
            return;
        }

        $this->title = $title;
        $this->titleSeeded = true;
    }

    /**
     * Calculate cache key
     */
    private static function getCacheKey(int $id, ?string $name = null): string
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
