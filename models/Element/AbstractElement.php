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

namespace Pimcore\Model\Element;

use Pimcore\Cache\Runtime;
use Pimcore\Event\AdminEvents;
use Pimcore\Event\Model\ElementEvent;
use Pimcore\Model;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\Traits\DirtyIndicatorTrait;

/**
 * @method Model\Document\Dao|Model\Asset\Dao|Model\DataObject\AbstractObject\Dao getDao()
 */
abstract class AbstractElement extends Model\AbstractModel implements ElementInterface, ElementDumpStateInterface, DirtyIndicatorInterface
{
    use ElementDumpStateTrait;
    use DirtyIndicatorTrait;

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
     */
    protected function updateModificationInfos()
    {
        $this->setVersionCount($this->getDao()->getVersionCountForUpdate() + 1);

        if ($this->getVersionCount() > 4200000000) {
            $this->setVersionCount(1);
        }

        $modificationDateKey = $this instanceof AbstractObject ? 'o_modificationDate' : 'modificationDate';
        if (!$this->isFieldDirty($modificationDateKey)) {
            $updateTime = time();
            $this->setModificationDate($updateTime);
        }

        if (!$this->getCreationDate()) {
            $this->setCreationDate($this->getModificationDate());
        }

        // auto assign user if possible, if not changed explicitly, if no user present, use ID=0 which represents the "system" user
        $userModificationKey = $this instanceof AbstractObject ? 'o_userModification' : 'userModification';
        if (!$this->isFieldDirty($userModificationKey)) {
            $userId = 0;
            $user = \Pimcore\Tool\Admin::getCurrentUser();
            if ($user instanceof Model\User) {
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
     * {@inheritdoc}
     */
    public function getCacheTag()
    {
        $elementType = Service::getElementType($this);

        return $elementType . '_' . $this->getId();
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

        return $elementType . '_' . $id;
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
     * @internal
     *
     * @return array
     */
    public function getUserPermissions()
    {
        $baseClass = Service::getBaseClassNameForElement($this);
        $workspaceClass = '\\Pimcore\\Model\\User\\Workspace\\' . $baseClass;
        /** @var Model\AbstractModel $dummy */
        $dummy = new $workspaceClass();
        $vars = $dummy->getObjectVars();
        $ignored = ['userId', 'cid', 'cpath', 'dao'];
        $permissions = [];

        foreach ($vars as $name => $defaultValue) {
            if (!in_array($name, $ignored)) {
                $permissions[$name] = $this->isAllowed($name);
            }
        }

        return $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed($type, ?Model\User $user = null)
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
        $type = Service::getType($this);

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
     * {@inheritdoc}
     */
    public function __sleep()
    {
        $parentVars = parent::__sleep();
        $blockedVars = ['dependencies'];

        return array_diff($parentVars, $blockedVars);
    }

    /**
     * {@inheritdoc}
     */
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
            $list->setCondition('`ctype` = ? AND cid = ? AND `autoSave` = 1 AND userId = ?', [Service::getType($this), $this->getId(), $userId]);
        } else {
            $list->setCondition('`ctype` = ? AND cid = ? AND `autoSave` = 1', [Service::getType($this), $this->getId()]);
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
        if (!Runtime::isRegistered($cacheKey)) {
            Runtime::set($cacheKey, $this);
        }

        $myProperties = $this->getProperties();
        $inheritedProperties = $this->getDao()->getProperties(true);
        $this->setProperties(array_merge($inheritedProperties, $myProperties));
    }
}
