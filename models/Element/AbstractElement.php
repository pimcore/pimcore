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

namespace Pimcore\Model\Element;

use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Event\AdminEvents;
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
     *
     * @var Model\Dependency|null
     */
    protected $dependencies;

    /**
     * @internal
     *
     * @var int
     */
    protected $__dataVersionTimestamp = null;

    /**
     * @internal
     *
     * @var string|null
     */
    protected $path;

    /**
     * @internal
     *
     * @var array|null
     */
    protected ?array $properties = null;

    /**
     * @internal
     *
     * @var bool
     */
    public static $doNotRestoreKeyAndPath = false;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $id = null;

    /**
     * @return string|null
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = (string) $path;

        return $this;
    }

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $creationDate = null;

    /**
     * @internal
     *
     * @var int|null
     */
    protected $modificationDate;

    /**
     * @internal
     *
     * @var int
     */
    protected $versionCount = 0;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $userOwner = null;

    /**
     * @internal
     *
     * @var string|null
     */
    protected ?string $locked = null;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $userModification = null;

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $parentId = null;

    /**
     * @return int|null
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param int $parentId
     *
     * @return $this
     */
    public function setParentId($parentId)
    {
        $parentId = (int) $parentId;
        $this->parentId = $parentId;
        $this->parent = null;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getUserModification()
    {
        return $this->userModification;
    }

    /**
     * @param int $userModification
     *
     * @return $this
     */
    public function setUserModification($userModification)
    {
        $this->markFieldDirty('userModification');
        $this->userModification = (int) $userModification;

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
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->markFieldDirty('modificationDate');

        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getUserOwner()
    {
        return $this->userOwner;
    }

    /**
     * @param int $userOwner
     *
     * @return $this
     */
    public function setUserOwner($userOwner)
    {
        $this->userOwner = (int) $userOwner;

        return $this;
    }

    /**
     * enum('self','propagate') nullable
     *
     * @return string|null
     */
    public function getLocked()
    {
        if (empty($this->locked)) {
            return null;
        }

        return $this->locked;
    }

    /**
     * enum('self','propagate') nullable
     *
     * @param string|null $locked
     *
     * @return $this
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

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
     * @param int|null $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id ? (int)$id : null;

        return $this;
    }

    /**
     * @var self|null
     */
    protected $parent = null;

    /**
     * @return self|null
     */
    public function getParent()
    {
        if ($this->parent === null) {
            $parent = Service::getElementById(Service::getElementType($this), $this->getParentId());
            $this->setParent($parent);
        }

        return $this->parent;
    }

    /**
     * @return Model\Property[]
     */
    public function getProperties()
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

            $this->setProperties($properties);
        }

        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    public function setProperties(?array $properties)
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @param string $name
     * @param string $type
     * @param mixed $data
     * @param bool $inherited
     * @param bool $inheritable
     *
     * @return $this
     */
    public function setProperty($name, $type, $data, $inherited = false, $inheritable = false)
    {
        $this->getProperties();

        $property = new Model\Property();
        $property->setType($type);
        $property->setCid($this->getId());
        $property->setName($name);
        $property->setCtype(Service::getElementType($this));
        $property->setData($data);
        $property->setInherited($inherited);
        $property->setInheritable($inheritable);

        $this->properties[$name] = $property;

        return $this;
    }

    /**
     * @internal
     */
    protected function updateModificationInfos()
    {
        if (Model\Version::isEnabled() === true) {
            $this->setVersionCount($this->getDao()->getVersionCountForUpdate() + 1);
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

    /**
     * {@inheritdoc}
     */
    public function getProperty($name, $asContainer = false)
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

    /**
     * {@inheritdoc}
     */
    public function hasProperty($name)
    {
        $properties = $this->getProperties();

        return array_key_exists($name, $properties);
    }

    /**
     * @param string $name
     */
    public function removeProperty($name)
    {
        $properties = $this->getProperties();
        unset($properties[$name]);
        $this->setProperties($properties);
    }

    /**
     * @return int
     */
    public function getVersionCount(): int
    {
        return $this->versionCount ? $this->versionCount : 0;
    }

    /**
     * @param int|null $versionCount
     *
     * @return $this
     */
    public function setVersionCount(?int $versionCount): ElementInterface
    {
        $this->versionCount = (int) $versionCount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTag()
    {
        $elementType = Service::getElementType($this);

        return Service::getElementCacheTag($elementType, $this->getId());
    }

    /**
     * @internal
     *
     * @param string|int $id
     *
     * @return string
     */
    protected static function getCacheKey($id): string
    {
        $elementType = Service::getElementTypeByClassName(static::class);

        return Service::getElementCacheTag($elementType, $id);
    }

    /**
     * {@inheritdoc}
     */
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
     * @return array
     */
    protected function resolveDependencies(): array
    {
        $dependencies = [[]];

        // check for properties
        if (method_exists($this, 'getProperties')) {
            foreach ($this->getProperties() as $property) {
                $dependencies[] = $property->resolveDependencies();
            }
        }

        return array_merge(...$dependencies);
    }

    /**
     * {@inheritdoc}
     */
    public function isLocked()
    {
        if ($this->getLocked()) {
            return true;
        }

        // check for inherited
        return $this->getDao()->isLocked();
    }

    /**
     * @param User|null $user
     *
     * @return array
     *
     * @throws \Exception
     *
     * @internal
     */
    public function getUserPermissions(?User $user = null)
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
            \Pimcore::getEventDispatcher()->dispatch($event, AdminEvents::ELEMENT_PERMISSION_IS_ALLOWED);

            $permissions[$type] = $event->getArgument('isAllowed');
        }

        return $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed($type, ?User $user = null)
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
        \Pimcore::getEventDispatcher()->dispatch($event, AdminEvents::ELEMENT_PERMISSION_IS_ALLOWED);

        return (bool) $event->getArgument('isAllowed');
    }

    /**
     * @internal
     */
    public function unlockPropagate()
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
    protected function validatePathLength()
    {
        if (mb_strlen($this->getRealFullPath()) > 765) {
            throw new \Exception("Full path is limited to 765 characters, reduce the length of your parent's path");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getFullPath();
    }

    /**
     * @return int
     */
    public function __getDataVersionTimestamp()
    {
        return $this->__dataVersionTimestamp;
    }

    /**
     * @param int $_dataVersionTimestamp
     */
    public function __setDataVersionTimestamp($_dataVersionTimestamp)
    {
        $this->__dataVersionTimestamp = $_dataVersionTimestamp;
    }

    /**
     * {@inheritdoc}
     */
    public function __isBasedOnLatestData()
    {
        return $this->getDao()->__isBasedOnLatestData();
    }

    /**
     * @internal
     *
     * @param string|null $versionNote
     * @param bool $saveOnlyVersion
     * @param bool $saveStackTrace
     * @param bool $isAutoSave
     *
     * @return Model\Version
     *
     * @throws \Exception
     */
    protected function doSaveVersion($versionNote = null, $saveOnlyVersion = true, $saveStackTrace = true, $isAutoSave = false)
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
        $version->setNote($versionNote);
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
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        if (!$this->dependencies) {
            $this->dependencies = Model\Dependency::getBySourceId($this->getId(), Service::getElementType($this));
        }

        return $this->dependencies;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledTasks()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getVersions()
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

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        if ($this->isInDumpState()) {
            // this is if we want to make a full dump of the object (eg. for a new version), including children for recyclebin
            $this->removeInheritedProperties();
        }

        return array_diff(parent::__sleep(), $this->getBlockedVars());
    }

    public function __wakeup()
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

    public function __clone()
    {
        parent::__clone();
        $this->dependencies = null;
    }

    /**
     * @internal
     *
     * @param int $userId
     */
    public function deleteAutoSaveVersions($userId = null)
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
    protected function removeInheritedProperties()
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
    protected function renewInheritedProperties()
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
