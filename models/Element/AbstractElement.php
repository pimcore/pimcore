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

namespace Pimcore\Model\Element;

use Closure;
use Doctrine\DBAL\Exception\RetryableException;
use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Config;
use Pimcore\Db;
use Pimcore\Event\ElementEvents;
use Pimcore\Event\Model\ElementEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Logger;
use Pimcore\Messenger\ElementDependenciesMessage;
use Pimcore\Model;
use Pimcore\Model\Element\Traits\DirtyIndicatorTrait;
use Pimcore\Model\User;
use Pimcore\Model\Version\Adapter\VersionStorageAdapterInterface;
use Pimcore\Workflow\Manager;
use RuntimeException;
use Throwable;
use WeakMap;

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

    private static bool $getInheritedProperties = true;

    /**
     * The versions written in place (see doSaveVersion()) in the running transaction attempt of retryableFunction(),
     * each with its stored state and data from before and the name of its lock: the version storage may not be
     * transactional (e.g. the file system), so the stored data is written back when the transaction is rolled back. An
     * element has an entry only while such an attempt runs. A WeakMap keeps it out of serialization and deep copies.
     *
     * @var WeakMap<self, list<array{Model\Version, Model\Version, string, string}>>
     */
    private static WeakMap $versionsWrittenInPlace;

    /**
     * Seconds to wait for another writer in place of the same version (see rememberVersionWrittenInPlace())
     */
    private const VERSION_WRITTEN_IN_PLACE_LOCK_TIMEOUT = 30;

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
        $this->parentId = $parentId;
        $this->parent = null;

        return $this;
    }

    public function getUserModification(): ?int
    {
        return $this->userModification;
    }

    public function setUserModification(?int $userModification): static
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
        $this->markFieldDirty('modificationDate');
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getUserOwner(): ?int
    {
        return $this->userOwner;
    }

    public function setUserOwner(?int $userOwner): static
    {
        $this->userOwner = $userOwner;

        return $this;
    }

    public function getLocked(): ?string
    {
        if (empty($this->locked)) {
            return null;
        }

        return $this->locked;
    }

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
        $parentId = $this->getParentId();
        if ($this->parent === null && $parentId !== null && $parentId !== 0) {
            $parent = Service::getElementById(Service::getElementType($this), $parentId);
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

        $properties = $this->properties;
        if (!static::getGetInheritedProperties()) {
            $properties = array_filter($properties, static function (Model\Property $property) {
                return !$property->isInherited();
            });
        }

        return $properties;
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

    public static function setGetInheritedProperties(bool $getInheritedProperties): void
    {
        self::$getInheritedProperties = $getInheritedProperties;
    }

    public static function getGetInheritedProperties(): bool
    {
        return self::$getInheritedProperties;
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
    public function resolveDependencies(): array
    {
        $dependencies = [[]];

        // check for properties
        foreach ($this->getProperties() as $property) {
            $dependencies[] = $property->resolveDependencies();
        }

        return array_merge(...$dependencies);
    }

    protected function addToDependenciesQueue(): void
    {
        if (Config::getSystemConfiguration()['dependency']['enabled']) {
            Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
                new ElementDependenciesMessage(Service::getElementType($this), $this->getId())
            );
        }
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
     * @throws Exception
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

        // cache only the DAO result per request; the ELEMENT_PERMISSION_IS_ALLOWED event below
        // stays outside the cache. All requested columns are resolved together (all-or-nothing),
        // because the DAO derives the "list" permission from the full column set.
        $permissionCache = Pimcore::getContainer()->get(PermissionCache::class);
        $permissions = [];
        foreach ($columns as $column) {
            $cached = $permissionCache->get($user, $this, $column, PermissionCacheScope::Batch);
            if (null === $cached) {
                $permissions = [];

                break;
            }
            $permissions[$column] = (int) $cached;
        }

        if ($permissions === []) {
            $permissions = $this->getDao()->areAllowed($columns, $user);
            foreach ($permissions as $column => $isAllowed) {
                $permissionCache->set($user, $this, (string) $column, PermissionCacheScope::Batch, (bool) $isAllowed);
            }
        }

        foreach ($permissions as $type => $isAllowed) {
            $event = new ElementEvent($this, ['isAllowed' => $isAllowed, 'permissionType' => $type, 'user' => $user]);
            Pimcore::getEventDispatcher()->dispatch($event, ElementEvents::ELEMENT_PERMISSION_IS_ALLOWED);

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
        /** @var Manager $workflowManager */
        $workflowManager = Pimcore::getContainer()->get(Manager::class);
        $isDeniedInWorkflow = $workflowManager->isDeniedInWorkflow($this, $type);

        //everything is allowed for admin except if it is denied in workflow
        if ($user->isAdmin()) {
            return !$isDeniedInWorkflow;
        }

        if (!$user->isAllowed(Service::getElementType($this) . 's')) {
            return false;
        }

        // cache only the DAO result per request; the workflow-deny check and the
        // ELEMENT_PERMISSION_IS_ALLOWED event below stay outside the cache
        $permissionCache = Pimcore::getContainer()->get(PermissionCache::class);
        $isAllowed = $permissionCache->get($user, $this, $type, PermissionCacheScope::Single);
        if (null === $isAllowed) {
            $isAllowed = $this->getDao()->isAllowed($type, $user);
            $permissionCache->set($user, $this, $type, PermissionCacheScope::Single, $isAllowed);
        }

        if ($isDeniedInWorkflow) {
            $isAllowed = false;
        }

        $event = new ElementEvent($this, ['isAllowed' => $isAllowed, 'permissionType' => $type, 'user' => $user]);
        Pimcore::getEventDispatcher()->dispatch($event, ElementEvents::ELEMENT_PERMISSION_IS_ALLOWED);

        return (bool) $event->getArgument('isAllowed');
    }

    /**
     * @internal
     */
    public function unlockPropagate(): void
    {
        $type = Service::getElementType($this);
        $event = new ElementEvent(
            $this,
            ['elementId' => $this->getId(), 'elementType' => $type]
        );

        $ids = $this->getDao()->unlockPropagate();

        $eventDispatcher = Pimcore::getEventDispatcher();
        $eventDispatcher->dispatch($event, ElementEvents::POST_ELEMENT_UNLOCK_PROPAGATE);

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
     * @throws Exception
     */
    protected function validatePathLength(): void
    {
        if (mb_strlen($this->getRealFullPath()) > self::MAX_FULL_PATH_LENGTH) {
            throw new Exception('Full path is limited to ' . self::MAX_FULL_PATH_LENGTH . " characters, reduce the length of your parent's path");
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
     * Writes a version of the element. With $existingVersion the version data is written into that version
     * instead of a new one (e.g. when a prepared version is published); it must belong to this element.
     *
     * @throws Exception
     *
     * @internal
     *
     */
    protected function doSaveVersion(?string $versionNote = null, bool $saveOnlyVersion = true, bool $saveStackTrace = true, bool $isAutoSave = false, ?Model\Version $existingVersion = null): Model\Version
    {
        $version = null;

        if ($existingVersion instanceof Model\Version) {
            $this->assertVersionBelongsToElement($existingVersion);
            $this->rememberVersionWrittenInPlace($existingVersion);
            $version = $existingVersion;
        } elseif ($isAutoSave) {
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
        // a version that is written into (e.g. when it is published) keeps its author, the user who published it is
        // the user modification of the element
        if (!$existingVersion instanceof Model\Version) {
            $version->setUserId($this->getUserModification());
        }
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

    /**
     * Resolves the `existingVersion` save parameter: the version the saved state is written into instead of a new one,
     * it must belong to this element and must not be an auto-save version
     *
     * @throws InvalidArgumentException
     *
     * @internal
     */
    protected function resolveExistingVersionParameter(array $parameters): ?Model\Version
    {
        $existingVersion = $parameters['existingVersion'] ?? null;
        if ($existingVersion !== null && !$existingVersion instanceof Model\Version) {
            throw new InvalidArgumentException(sprintf(
                'The "existingVersion" parameter must be an instance of %s, %s given',
                Model\Version::class,
                get_debug_type($existingVersion)
            ));
        }

        if ($existingVersion !== null) {
            $this->assertVersionBelongsToElement($existingVersion);

            // auto-save versions are rewritten by the editor of their user at any time, writing into them would race
            // with that, so they are saved as a new version
            if ($existingVersion->isAutoSave()) {
                throw new InvalidArgumentException(sprintf(
                    'Version %d is an auto-save version and cannot be written into, save it as a new version instead',
                    $existingVersion->getId()
                ));
            }
        }

        return $existingVersion;
    }

    private function trackVersionsWrittenInPlace(): void
    {
        self::$versionsWrittenInPlace ??= new WeakMap();
        self::$versionsWrittenInPlace[$this] = [];
    }

    /**
     * Keeps the stored data of a version that is written in place, to restore it if the transaction is rolled back. A
     * version written without the transaction of retryableFunction() (e.g. by saveVersion() alone) isn't rolled back,
     * so its data isn't kept.
     *
     * @throws RuntimeException if the version is locked by another writer or its stored data cannot be read
     */
    private function rememberVersionWrittenInPlace(Model\Version $version): void
    {
        if (!isset(self::$versionsWrittenInPlace[$this])) {
            return;
        }

        // a named lock is kept by the connection when the transaction is rolled back (also by the database itself, e.g.
        // on a deadlock): another writer in place of this version waits until it is committed or its data is restored
        $db = Db::get();
        $lockName = 'pimcore_version_written_in_place_' . $version->getId();
        if ((int) $db->fetchOne('SELECT GET_LOCK(?, ?)', [$lockName, self::VERSION_WRITTEN_IN_PLACE_LOCK_TIMEOUT]) !== 1) {
            throw new RuntimeException(sprintf('Version %d is written by another process, it cannot be written into', $version->getId()));
        }

        try {
            // the stored state of the version, it may differ from the given one (e.g. moved to another storage): its row
            // is locked and read as it is stored, not from the snapshot of the transaction. Every write of a version
            // locks its row first (Version\Dao::save()), so no other write of it happens until the transaction is
            // committed or rolled back
            /** @var Model\Version $storedVersion */
            $storedVersion = self::getModelFactory()->build(Model\Version::class);
            $storedVersion->getDao()->getById($version->getId(), true);
            $metaData = Pimcore::getContainer()->get(VersionStorageAdapterInterface::class)->loadMetaData($storedVersion);
            if ($metaData === null) {
                throw new RuntimeException(sprintf(
                    'The stored data of version %d cannot be read, so it cannot be restored and the version cannot be written into',
                    $version->getId()
                ));
            }
        } catch (Throwable $e) {
            $this->releaseVersionLock($lockName);

            throw $e;
        }

        $versions = self::$versionsWrittenInPlace[$this];
        $versions[] = [$version, $storedVersion, $metaData, $lockName];
        self::$versionsWrittenInPlace[$this] = $versions;
    }

    private function forgetVersionsWrittenInPlace(): void
    {
        if (!isset(self::$versionsWrittenInPlace[$this])) {
            return;
        }

        $versions = self::$versionsWrittenInPlace[$this];
        self::$versionsWrittenInPlace->offsetUnset($this);
        foreach ($versions as [, , , $lockName]) {
            $this->releaseVersionLock($lockName);
        }
    }

    /**
     * Writes the stored data of the versions written in place back when the transaction fails, where the version
     * storage didn't keep it. Another writer in place of the same version waits for the named lock, so its data isn't
     * overwritten.
     */
    private function restoreVersionsWrittenInPlace(): void
    {
        if (!isset(self::$versionsWrittenInPlace[$this])) {
            return;
        }

        $versions = self::$versionsWrittenInPlace[$this];
        self::$versionsWrittenInPlace->offsetUnset($this);
        $storageAdapter = Pimcore::getContainer()->get(VersionStorageAdapterInterface::class);
        foreach (array_reverse($versions) as [$writtenVersion, $storedVersion, $metaData, $lockName]) {
            try {
                // the written version may have moved to another storage (see Version::save()), a retry reads it from
                // where it is stored
                if ($storedVersion->getStorageType() !== null) {
                    $writtenVersion->setStorageType($storedVersion->getStorageType());
                }
                $writtenVersion->setBinaryFileId($storedVersion->getBinaryFileId());
                $writtenVersion->setBinaryFileHash($storedVersion->getBinaryFileHash());

                if ($storageAdapter->loadMetaData($storedVersion) !== $metaData) {
                    // the binary data is written along, a storage that writes both at once would clear it otherwise;
                    // it is the one of the version (see Version::save())
                    $binaryDataStream = $storedVersion->getCtype() === 'asset' && $storedVersion->getBinaryFileHash() !== null
                        ? $storageAdapter->loadBinaryData($storedVersion)
                        : null;
                    if (is_resource($binaryDataStream)) {
                        // a storage may return it at its end (e.g. the database)
                        rewind($binaryDataStream);
                    } else {
                        $binaryDataStream = null;
                    }
                    $storageAdapter->save($storedVersion, $metaData, $binaryDataStream);
                }
            } catch (Throwable $e) {
                Logger::error('Unable to restore the data of version ' . $storedVersion->getId() . ' after the transaction failed: ' . $e);
            } finally {
                $this->releaseVersionLock($lockName);
            }
        }
    }

    private function releaseVersionLock(string $lockName): void
    {
        try {
            Db::get()->fetchOne('SELECT RELEASE_LOCK(?)', [$lockName]);
        } catch (Throwable $e) {
            Logger::error('Unable to release the lock ' . $lockName . ': ' . $e);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function assertVersionBelongsToElement(Model\Version $version): void
    {
        $elementType = Service::getElementType($this);
        if ($version->getCid() !== $this->getId() || $version->getCtype() !== $elementType) {
            throw new InvalidArgumentException(sprintf(
                'Version %d belongs to %s %d and cannot be written for %s %d',
                $version->getId(),
                $version->getCtype(),
                $version->getCid(),
                $elementType,
                $this->getId()
            ));
        }
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

        return array_diff(parent::__sleep(), $this->getBlockedVars(), self::getBlockedVars());
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
    public function deleteAutoSaveVersions(?int $userId = null): void
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

    protected function retryableFunction(
        ?Closure $beforeRetryables = null,
        ?Closure $retryableFunc = null,
        ?Closure $onCommit = null,
        ?Closure $onBeforeRetry = null,
        ?Closure $onFailure = null,
        int $maxRetries = 5,
    ): void {
        try {
            if ($beforeRetryables instanceof Closure) {
                $beforeRetryables();
            }

            for ($retries = 0; $retries < $maxRetries; $retries++) {
                $this->beginTransaction();
                $this->trackVersionsWrittenInPlace();

                try {
                    if ($retryableFunc instanceof Closure) {
                        $retryableFunc();
                    }
                    $this->commit();
                    $this->forgetVersionsWrittenInPlace();
                    if ($onCommit instanceof Closure) {
                        $onCommit();
                    }

                    break; // transaction was successfully completed, so we cancel the loop here -> no restart required
                } catch (Throwable $e) {
                    $this->restoreVersionsWrittenInPlace();

                    try {
                        $this->rollBack();
                    } catch (Exception $er) {
                        // PDO adapter throws exceptions if rollback fails
                        Logger::info((string)$er);
                    }

                    if ($onBeforeRetry instanceof Closure) {
                        $onBeforeRetry($e);
                    }

                    if ($e instanceof RetryableException && $retries < ($maxRetries - 1)) {
                        $run = $retries + 1;
                        $waitTime = rand(1, 5) * 100000; // microseconds
                        Logger::warn(
                            'Unable to finish transaction (' . $run . '. run) because of the following reason: '
                            . $e->getMessage()
                            . '. --> Retrying in ' . $waitTime . ' microseconds ... ('
                            . ($run + 1) . ' of ' . $maxRetries . ')'
                        );

                        usleep($waitTime); // wait specified time until we restart the transaction
                    } else {
                        // if the transaction still fail after $maxRetries retries, we throw out the exception
                        throw $e;
                    }

                }
            }

        } catch (Exception $e) {
            if ($onFailure instanceof Closure) {
                $onFailure($e);
            }
            Logger::crit((string)$e);

            throw $e;
        }
    }
}
