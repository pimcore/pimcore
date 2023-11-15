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

namespace Pimcore\Model\Element;

use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Event\ElementEvents;
use Pimcore\Event\Model\ElementEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Model;
use Pimcore\Model\Element\Traits\DirtyIndicatorTrait;
use Pimcore\Model\User;

/**
 * @method Model\Document\Dao|Model\Asset\Dao|Model\DataObject\AbstractObject\Dao getDao()
 */
abstract class AbstractElement extends Model\AbstractModel implements ElementInterface, ElementDumpStateInterface, DirtyIndicatorInterface
{
    use ElementDumpStateTrait;
    use DirtyIndicatorTrait;
    use RecursionBlockingEventDispatchHelperTrait;

    /**
     * @internal
     */
    protected ?Model\Dependency $dependencies = null;

    /**
     * @internal
     */
    protected ?int $__dataVersionTimestamp = null;

    /**
     * @internal
     */
    protected ?string $path = null;

    /**
     * @internal
     *
     * @var array<string, Model\Property>|null
     */
    protected ?array $properties = null;

    /**
     * @internal
     */
    public static bool $doNotRestoreKeyAndPath = false;

    /**
     * @internal
     */
    protected ?int $id = null;

    /**
     * @internal
     */
    protected ?int $creationDate = null;

    /**
     * @internal
     */
    protected ?int $modificationDate = null;

    /**
     * @internal
     */
    protected int $versionCount = 0;

    /**
     * @internal
     */
    protected ?int $userOwner = null;

    /**
     * @internal
     */
    protected ?string $locked = null;

    /**
     * @internal
     */
    protected ?int $userModification = null;

    /**
     * @internal
     */
    protected ?int $parentId = null;

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function setParentId(?int $parentId): static
    {
        $parentId = (int) $parentId;
        $this->parentId = $parentId;
        $this->parent = null;

        return $this;
    }

    public function getUserModification(): ?int
    {
        return $this->userModification;
    }

    public function setUserModification(int $userModification): static
    {
        $this->markFieldDirty('userModification');
        $this->userModification = $userModification;

        return $this;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function setCreationDate(int $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setModificationDate(int $modificationDate): static
    {
        if($this->modificationDate != $modificationDate) {
            $this->markFieldDirty('modificationDate');
            $this->modificationDate = $modificationDate;
        }

        return $this;
    }

    public function getUserOwner(): ?int
    {
        return $this->userOwner;
    }

    public function setUserOwner(int $userOwner): static
    {
        $this->userOwner = $userOwner;

        return $this;
    }

    /**
     * enum('self','propagate') nullable
     *
     */
    public function getLocked(): ?string
    {
        if (empty($this->locked)) {
            return null;
        }

        return $this->locked;
    }

    /**
     * enum('self','propagate') nullable
     *
     *
     * @return $this
     */
    public function setLocked(?string $locked): static
    {
        $this->locked = $locked;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    protected ?AbstractElement $parent = null;

    public function getParent(): ?AbstractElement
    {
        if ($this->parent === null && $this->getParentId() !== null) {
            $parent = Service::getElementById(Service::getElementType($this), $this->getParentId());
            $this->setParent($parent);
        }

        return $this->parent;
    }

    public function getProperties(): array
    {
        $type = Service::getElementType($this);

        if ($this->properties === null) {
            // try to get from cache
            $cacheKey = $type . '_properties_' . $this->getId();
            $properties = Cache::load($cacheKey);
            if (!is_array($properties)) {
                $properties = $this->getDao()->getProperties();
                $elementCacheTag = $this->getCacheTag();
                $cacheTags = [$type . '_properties' => $type . '_properties', $elementCacheTag => $elementCacheTag];
                Cache::save($properties, $cacheKey, $cacheTags);
            }

            $this->properties = $properties;
        }

        return $this->properties;
    }

    public function setProperties(?array $properties): static
    {
        $this->markFieldDirty('properties');
        $this->properties = $properties;

        return $this;
    }

    public function setProperty(
        string $name,
        string $type,
        mixed $data,
        bool $inherited = false,
        bool $inheritable = false
    ): static {
        $properties = $this->getProperties();

        $id = $this->getId();
        $property = new Model\Property();
        $property->setType($type);
        if (isset($id)) {
            $property->setCid($id);
        }
        $property->setName($name);
        $property->setCtype(Service::getElementType($this));
        $property->setCpath($this->getRealFullPath());
        $property->setData($data);
        $property->setInherited($inherited);
        $property->setInheritable($inheritable);

        $properties[$name] = $property;

        $this->setProperties($properties);

        return $this;
    }

    /**
     * @internal
     */
    protected function updateModificationInfos(): void
    {
        if (Model\Version::isEnabled() === true) {
            $this->setVersionCount($this->getDao()->getVersionCountForUpdate() + 1);
        } else {
            $this->setVersionCount($this->getDao()->getVersionCountForUpdate());
        }

        if ($this->getVersionCount() > 4200000000) {
            $this->setVersionCount(1);
        }

        $modificationDateKey = 'modificationDate';
        if (!$this->isFieldDirty($modificationDateKey)) {
            $updateTime = time();
            $this->setModificationDate($updateTime);
        }

        if (!$this->getCreationDate()) {
            $this->setCreationDate($this->getModificationDate());
        }

        // auto assign user if possible, if not changed explicitly, if no user present, use ID=0 which represents the "system" user
        $userModificationKey = 'userModification';
        if (!$this->isFieldDirty($userModificationKey)) {
            $userId = 0;
            $user = \Pimcore\Tool\Admin::getCurrentUser();
            if ($user instanceof User) {
                $userId = $user->getId();
            }
            $this->setUserModification($userId);
        }

        if ($this->getUserOwner() === null) {
            $this->setUserOwner($this->getUserModification());
        }
    }

    public function getProperty(string $name, bool $asContainer = false): mixed
    {
        $properties = $this->getProperties();
        if ($this->hasProperty($name)) {
            if ($asContainer) {
                return $properties[$name];
            } else {
                return $properties[$name]->getData();
            }
        }

        return null;
    }

    public function hasProperty(string $name): bool
    {
        $properties = $this->getProperties();

        return array_key_exists($name, $properties);
    }

    public function removeProperty(string $name): void
    {
        $properties = $this->getProperties();
        unset($properties[$name]);
        $this->setProperties($properties);
    }

    public function getVersionCount(): int
    {
        return $this->versionCount ? $this->versionCount : 0;
    }

    public function setVersionCount(int $versionCount): static
    {
        $this->versionCount = $versionCount;

        return $this;
    }

    public function getCacheTag(): string
    {
        $elementType = Service::getElementType($this);

        return Service::getElementCacheTag($elementType, $this->getId());
    }

    /**
     *
     *
     * @internal
     */
    protected static function getCacheKey(int|string $id): string
    {
        $elementType = Service::getElementTypeByClassName(static::class);

        return Service::getElementCacheTag($elementType, $id);
    }

    public function getCacheTags(array $tags = []): array
    {
        $tags[$this->getCacheTag()] = $this->getCacheTag();

        return $tags;
    }

    /**
     * Resolves the dependencies of the element and returns an array of them - Used by update()
     *
     * @internal
     *
     */
    protected function resolveDependencies(): array
    {
        $dependencies = [[]];

        // check for properties
        foreach ($this->getProperties() as $property) {
            $dependencies[] = $property->resolveDependencies();
        }

        return array_merge(...$dependencies);
    }

    public function isLocked(): bool
    {
        if ($this->getLocked()) {
            return true;
        }

        // check for inherited
        return $this->getDao()->isLocked();
    }

    /**
     *
     *
     * @throws \Exception
     *
     * @internal
     */
    public function getUserPermissions(?User $user = null): array
    {
        $baseClass = Service::getBaseClassNameForElement($this);
        $workspaceClass = '\\Pimcore\\Model\\User\\Workspace\\' . $baseClass;
        /** @var Model\AbstractModel $dummy */
        $dummy = new $workspaceClass();
        $vars = $dummy->getObjectVars();
        $ignored = ['userId', 'cid', 'cpath', 'dao'];
        $permissions = [];

        $columns = array_diff(array_keys($vars), $ignored);
        $defaultValue = 0;

        if (null === $user) {
            $user = \Pimcore\Tool\Admin::getCurrentUser();
        }

        if ((!$user && php_sapi_name() === 'cli') || $user?->isAdmin()) {
            $defaultValue = 1;
        }

        foreach ($columns as $name) {
            $permissions[$name] = $defaultValue;
        }

        if (!$user || $user->isAdmin() || !$user->isAllowed(Service::getElementType($this) . 's')) {
            return $permissions;
        }

        $permissions = $this->getDao()->areAllowed($columns, $user);

        foreach ($permissions as $type => $isAllowed) {
            $event = new ElementEvent($this, ['isAllowed' => $isAllowed, 'permissionType' => $type, 'user' => $user]);
            \Pimcore::getEventDispatcher()->dispatch($event, ElementEvents::ELEMENT_PERMISSION_IS_ALLOWED);

            $permissions[$type] = $event->getArgument('isAllowed');
        }

        return $permissions;
    }

    public function isAllowed(string $type, ?User $user = null): bool
    {
        if (null === $user) {
            $user = \Pimcore\Tool\Admin::getCurrentUser();
        }

        if (!$user) {
            if (php_sapi_name() === 'cli') {
                return true;
            }

            return false;
        }

        //everything is allowed for admin
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->isAllowed(Service::getElementType($this) . 's')) {
            return false;
        }
        $isAllowed = $this->getDao()->isAllowed($type, $user);

        $event = new ElementEvent($this, ['isAllowed' => $isAllowed, 'permissionType' => $type, 'user' => $user]);
        \Pimcore::getEventDispatcher()->dispatch($event, ElementEvents::ELEMENT_PERMISSION_IS_ALLOWED);

        return (bool) $event->getArgument('isAllowed');
    }

    /**
     * @internal
     */
    public function unlockPropagate(): void
    {
        $type = Service::getElementType($this);

        $ids = $this->getDao()->unlockPropagate();

        // invalidate cache items
        foreach ($ids as $id) {
            $element = Service::getElementById($type, $id);
            if ($element) {
                $element->clearDependentCache();
            }
        }
    }

    /**
     * @internal
     *
     * @throws \Exception
     */
    protected function validatePathLength(): void
    {
        if (mb_strlen($this->getRealFullPath()) > 765) {
            throw new \Exception("Full path is limited to 765 characters, reduce the length of your parent's path");
        }
    }

    public function __toString(): string
    {
        return $this->getFullPath();
    }

    public function __getDataVersionTimestamp(): ?int
    {
        return $this->__dataVersionTimestamp;
    }

    public function __setDataVersionTimestamp(int $_dataVersionTimestamp): void
    {
        $this->__dataVersionTimestamp = $_dataVersionTimestamp;
    }

    public function __isBasedOnLatestData(): bool
    {
        return $this->getDao()->__isBasedOnLatestData();
    }

    /**
     *
     *
     * @throws \Exception
     *
     * @internal
     *
     */
    protected function doSaveVersion(string $versionNote = null, bool $saveOnlyVersion = true, bool $saveStackTrace = true, bool $isAutoSave = false): Model\Version
    {
        $version = null;

        if ($isAutoSave) {
            $list = new Model\Version\Listing();
            $list->setLoadAutoSave(true);
            $list->setCondition('autoSave = 1 AND cid = ? AND cType = ? AND userId = ? ', [$this->getId(), Service::getElementType($this), $this->getUserModification()]);
            $version = $list->current();
        }

        if (!$version) {
            /** @var Model\Version $version */
            $version = self::getModelFactory()->build(Model\Version::class);
        }

        $version->setCid($this->getId());
        $version->setCtype(Service::getElementType($this));
        $version->setDate($this->getModificationDate());
        $version->setUserId($this->getUserModification());
        $version->setData($this);
        if ($versionNote !== null) {
            $version->setNote($versionNote);
        }
        $version->setGenerateStackTrace($saveStackTrace);
        $version->setAutoSave($isAutoSave);

        if ($saveOnlyVersion) {
            $versionCount = $this->getDao()->getVersionCountForUpdate();
            $versionCount++;
        } else {
            $versionCount = $this->getVersionCount();
        }

        $version->setVersionCount($versionCount);
        $version->save();

        return $version;
    }

    public function getDependencies(): Model\Dependency
    {
        if (!$this->dependencies) {
            $this->dependencies = Model\Dependency::getBySourceId($this->getId(), Service::getElementType($this));
        }

        return $this->dependencies;
    }

    public function getScheduledTasks(): array
    {
        return [];
    }

    public function getVersions(): array
    {
        return [];
    }

    /**
     * @internal
     *
     * @return string[]
     */
    protected function getBlockedVars(): array
    {
        return ['dependencies', 'parent'];
    }

    public function __sleep(): array
    {
        if ($this->isInDumpState()) {
            // this is if we want to make a full dump of the object (eg. for a new version), including children for recyclebin
            $this->removeInheritedProperties();
        }

        return array_diff(parent::__sleep(), $this->getBlockedVars());
    }

    public function __wakeup(): void
    {
        if ($this->isInDumpState()) {
            // set current key and path this is necessary because the serialized data can have a different path than the original element ( element was renamed or moved )
            $originalElement = static::getById($this->getId());

            if ($originalElement && !self::$doNotRestoreKeyAndPath) {
                // set key and path for DataObject and Document (assets have different wakeup call)
                $this->setKey($originalElement->getKey());
                $this->setPath($originalElement->getRealPath());
            }
        }

        if ($this->isInDumpState() && $this->properties !== null) {
            $this->renewInheritedProperties();
        }

        $this->setInDumpState(false);
    }

    public function __clone(): void
    {
        parent::__clone();
        $this->dependencies = null;
    }

    /**
     *
     * @internal
     */
    public function deleteAutoSaveVersions(int $userId = null): void
    {
        $list = new Model\Version\Listing();
        $list->setLoadAutoSave(true);
        if ($userId) {
            $list->setCondition('`ctype` = ? AND cid = ? AND `autoSave` = 1 AND userId = ?', [Service::getElementType($this), $this->getId(), $userId]);
        } else {
            $list->setCondition('`ctype` = ? AND cid = ? AND `autoSave` = 1', [Service::getElementType($this), $this->getId()]);
        }

        foreach ($list->load() as $version) {
            $version->delete();
        }
    }

    /**
     * @internal
     */
    protected function removeInheritedProperties(): void
    {
        $myProperties = $this->getProperties();

        if ($myProperties) {
            foreach ($this->getProperties() as $name => $property) {
                if ($property->getInherited()) {
                    unset($myProperties[$name]);
                }
            }
        }

        $this->setProperties($myProperties);
    }

    /**
     * @internal
     */
    protected function renewInheritedProperties(): void
    {
        $this->removeInheritedProperties();

        // add to registry to avoid infinite regresses in the following $this->getDao()->getProperties()
        $cacheKey = self::getCacheKey($this->getId());
        if (!RuntimeCache::isRegistered($cacheKey)) {
            RuntimeCache::set($cacheKey, $this);
        }

        $myProperties = $this->getProperties();
        $inheritedProperties = $this->getDao()->getProperties(true);
        $this->setProperties(array_merge($inheritedProperties, $myProperties));
    }
}
