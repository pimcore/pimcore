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

namespace Pimcore\Model\DataObject;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Pimcore\Cache;
use Pimcore\Cache\Runtime;
use Pimcore\Db;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element;
use Pimcore\Model\Element\DuplicateFullPathException;

/**
 * @method AbstractObject\Dao getDao()
 * @method array|null getPermissions(?string $type, Model\User $user, bool $quote = true)
 * @method bool __isBasedOnLatestData()
 * @method string getCurrentFullPath()
 * @method int getChildAmount($objectTypes = [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER], Model\User $user = null)
 * @method array getChildPermissions(?string $type, Model\User $user, bool $quote = true)
 */
abstract class AbstractObject extends Model\Element\AbstractElement
{
    const OBJECT_TYPE_FOLDER = 'folder';

    const OBJECT_TYPE_OBJECT = 'object';

    const OBJECT_TYPE_VARIANT = 'variant';

    const OBJECT_CHILDREN_SORT_BY_DEFAULT = 'key';

    const OBJECT_CHILDREN_SORT_BY_INDEX = 'index';

    const OBJECT_CHILDREN_SORT_ORDER_DEFAULT = 'ASC';

    /**
     * possible types of a document
     *
     * @var array
     */
    public static $types = [self::OBJECT_TYPE_FOLDER, self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_VARIANT];

    /**
     * @var bool
     */
    private static $hideUnpublished = false;

    /**
     * @var bool
     */
    private static $getInheritedValues = false;

    /**
     * @internal
     *
     * @var bool
     */
    protected static $disableDirtyDetection = false;

    /**
     * @internal
     *
     * @var string[]
     */
    protected static $objectColumns = ['o_id', 'o_parentid', 'o_type', 'o_key', 'o_classid', 'o_classname', 'o_path'];

    /**
     * @internal
     *
     * @deprecated
     *
     * @var int|null
     */
    protected $o_id;

    /**
     * @internal
     *
     * @deprecated
     *
     * @var int|null
     */
    protected $o_parentId;

    /**
     * @internal
     *
     * @deprecated
     */
    protected $o_parent;

    /**
     * @internal
     *
     * @var string
     */
    protected $o_type = 'object';

    /**
     * @internal
     *
     * @var string|null
     */
    protected $o_key;

    /**
     * @internal
     *
     * @deprecated
     *
     * @var string|null
     */
    protected $o_path;

    /**
     * @internal
     *
     * @var int
     */
    protected $o_index = 0;

    /**
     * @internal
     *
     * @deprecated
     *
     * @var int|null
     */
    protected $o_creationDate;

    /**
     * @internal
     *
     * @deprecated
     *
     * @var int|null
     */
    protected $o_modificationDate;

    /**
     * @internal
     *
     * @deprecated
     *
     * @var int|null
     */
    protected ?int $o_userOwner = null;

    /**
     * @internal
     *
     * @deprecated
     *
     * @var int|null
     */
    protected ?int $o_userModification = null;

    /**
     * @internal
     *
     * @var bool[]
     */
    protected $o_hasChildren = [];

    /**
     * Contains a list of sibling documents
     *
     * @internal
     *
     * @var array
     */
    protected $o_siblings = [];

    /**
     * Indicator if object has siblings or not
     *
     * @internal
     *
     * @var bool[]
     */
    protected $o_hasSiblings = [];

    /**
     * @internal
     *
     * @var array
     */
    protected $o_children = [];

    /**
     * @internal
     *
     * @deprecated
     *
     * @var string
     */
    protected $o_locked;

    /**
     * @internal
     *
     * @var string|null
     */
    protected $o_childrenSortBy;

    /**
     * @internal
     *
     * @var string|null
     */
    protected $o_childrenSortOrder;

    /**
     * @internal
     *
     * @deprecated
     *
     * @var int
     */
    protected $o_versionCount = 0;

    /**
     * @internal
     *
     * @deprecated
     *
     * @var array|null
     */
    protected $o_properties = null;

    public function __construct()
    {
        $this->o_id = & $this->id;
        $this->o_path = & $this->path;
        $this->o_creationDate = & $this->creationDate;
        $this->o_userOwner = & $this->userOwner;
        $this->o_versionCount = & $this->versionCount;
        $this->o_modificationDate = & $this->modificationDate;
        $this->o_locked = & $this->locked;
        $this->o_parent = & $this->parent;
        $this->o_properties = & $this->properties;
        $this->o_userModification = & $this->userModification;
        $this->o_parentId = & $this->parentId;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBlockedVars(): array
    {
        $blockedVars = ['o_hasChildren', 'o_versions', 'o_class', 'scheduledTasks', 'o_parent', 'parent', 'omitMandatoryCheck'];

        if ($this->isInDumpState()) {
            // this is if we want to make a full dump of the object (eg. for a new version), including children for recyclebin
            $blockedVars = array_merge($blockedVars, ['o_dirtyFields']);
        } else {
            // this is if we want to cache the object
            $blockedVars = array_merge($blockedVars, ['o_children', 'properties', 'o_properties']);
        }

        return $blockedVars;
    }

    /**
     * @static
     *
     * @return bool
     */
    public static function getHideUnpublished()
    {
        return self::$hideUnpublished;
    }

    /**
     * @static
     *
     * @param bool $hideUnpublished
     */
    public static function setHideUnpublished($hideUnpublished)
    {
        self::$hideUnpublished = $hideUnpublished;
    }

    /**
     * @static
     *
     * @return bool
     */
    public static function doHideUnpublished()
    {
        return self::$hideUnpublished;
    }

    /**
     * @static
     *
     * @param bool $getInheritedValues
     */
    public static function setGetInheritedValues($getInheritedValues)
    {
        self::$getInheritedValues = $getInheritedValues;
    }

    /**
     * @static
     *
     * @return bool
     */
    public static function getGetInheritedValues()
    {
        return self::$getInheritedValues;
    }

    /**
     * @static
     *
     * @param Concrete|null $object
     *
     * @return bool
     */
    public static function doGetInheritedValues(Concrete $object = null)
    {
        if (self::$getInheritedValues && $object !== null) {
            $class = $object->getClass();

            return $class->getAllowInherit();
        }

        return self::$getInheritedValues;
    }

    /**
     * get possible types
     *
     * @return array
     */
    public static function getTypes()
    {
        return self::$types;
    }

    /**
     * Static helper to get an object by the passed ID
     *
     * @param int $id
     * @param bool $force
     *
     * @return static|null
     */
    public static function getById($id, $force = false)
    {
        if (!is_numeric($id) || $id < 1) {
            return null;
        }

        $id = (int)$id;
        $cacheKey = self::getCacheKey($id);

        if (!$force && Runtime::isRegistered($cacheKey)) {
            $object = Runtime::get($cacheKey);
            if ($object && static::typeMatch($object)) {
                return $object;
            }
        }

        if ($force || !($object = Cache::load($cacheKey))) {
            $object = new Model\DataObject();

            try {
                $typeInfo = $object->getDao()->getTypeById($id);

                if (!empty($typeInfo['o_type']) && in_array($typeInfo['o_type'], DataObject::$types)) {
                    if ($typeInfo['o_type'] == DataObject::OBJECT_TYPE_FOLDER) {
                        $className = Folder::class;
                    } else {
                        $className = 'Pimcore\\Model\\DataObject\\' . ucfirst($typeInfo['o_className']);
                    }

                    /** @var AbstractObject $object */
                    $object = self::getModelFactory()->build($className);
                    Runtime::set($cacheKey, $object);
                    $object->getDao()->getById($id);
                    $object->__setDataVersionTimestamp($object->getModificationDate());

                    Service::recursiveResetDirtyMap($object);

                    // force loading of relation data
                    if ($object instanceof Concrete) {
                        $object->__getRawRelationData();
                    }

                    Cache::save($object, $cacheKey);
                } else {
                    throw new Model\Exception\NotFoundException('No entry for object id ' . $id);
                }
            } catch (Model\Exception\NotFoundException $e) {
                return null;
            }
        } else {
            Runtime::set($cacheKey, $object);
        }

        if (!$object || !static::typeMatch($object)) {
            return null;
        }

        return $object;
    }

    /**
     * @param string $path
     * @param bool $force
     *
     * @return static|null
     */
    public static function getByPath($path, $force = false)
    {
        if (!$path) {
            return null;
        }

        $path = Model\Element\Service::correctPath($path);

        try {
            $object = new static();
            $object->getDao()->getByPath($path);

            return static::getById($object->getId(), $force);
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @param array $config
     *
     * @return DataObject\Listing
     *
     * @throws \Exception
     */
    public static function getList($config = [])
    {
        $className = DataObject::class;
        // get classname
        if (!in_array(static::class, [__CLASS__, Concrete::class, Folder::class], true)) {
            /** @var Concrete $tmpObject */
            $tmpObject = new static();
            if ($tmpObject instanceof Concrete) {
                $className = 'Pimcore\\Model\\DataObject\\' . ucfirst($tmpObject->getClassName());
            }
        }

        if (is_array($config)) {
            if (!empty($config['class'])) {
                $className = ltrim($config['class'], '\\');
            }

            if ($className) {
                $listClass = $className . '\\Listing';
                /** @var DataObject\Listing $list */
                $list = self::getModelFactory()->build($listClass);
                $list->setValues($config);

                return $list;
            }
        }

        throw new \Exception('Unable to initiate list class - class not found or invalid configuration');
    }

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @param array $config
     *
     * @return int total count
     */
    public static function getTotalCount($config = [])
    {
        $list = static::getList($config);
        $count = $list->getTotalCount();

        return $count;
    }

    /**
     * @internal
     *
     * @param AbstractObject $object
     *
     * @return bool
     */
    protected static function typeMatch(AbstractObject $object)
    {
        return in_array(static::class, [Concrete::class, __CLASS__], true) || $object instanceof static;
    }

    /**
     * @param array $objectTypes
     * @param bool $includingUnpublished
     *
     * @return DataObject[]
     */
    public function getChildren(array $objectTypes = [self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER], $includingUnpublished = false)
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());

        if (!isset($this->o_children[$cacheKey])) {
            if ($this->getId()) {
                $list = new Listing();
                $list->setUnpublished($includingUnpublished);
                $list->setCondition('o_parentId = ?', $this->getId());
                $list->setOrderKey(sprintf('o_%s', $this->getChildrenSortBy()));
                $list->setOrder($this->getChildrenSortOrder());
                $list->setObjectTypes($objectTypes);
                $this->o_children[$cacheKey] = $list->load();
                $this->o_hasChildren[$cacheKey] = (bool) count($this->o_children[$cacheKey]);
            } else {
                $this->o_children[$cacheKey] = [];
                $this->o_hasChildren[$cacheKey] = false;
            }
        }

        return $this->o_children[$cacheKey];
    }

    /**
     * Quick test if there are children
     *
     * @param array $objectTypes
     * @param bool|null $includingUnpublished
     *
     * @return bool
     */
    public function hasChildren($objectTypes = [self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER], $includingUnpublished = null)
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());

        if (isset($this->o_hasChildren[$cacheKey])) {
            return $this->o_hasChildren[$cacheKey];
        }

        return $this->o_hasChildren[$cacheKey] = $this->getDao()->hasChildren($objectTypes, $includingUnpublished);
    }

    /**
     * Get a list of the sibling documents
     *
     * @param array $objectTypes
     * @param bool $includingUnpublished
     *
     * @return array
     */
    public function getSiblings(array $objectTypes = [self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER], $includingUnpublished = false)
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());

        if (!isset($this->o_siblings[$cacheKey])) {
            if ($this->getParentId()) {
                $list = new Listing();
                $list->setUnpublished($includingUnpublished);
                $list->addConditionParam('o_parentId = ?', $this->getParentId());
                if ($this->getId()) {
                    $list->addConditionParam('o_id != ?', $this->getId());
                }
                $list->setOrderKey('o_key');
                $list->setObjectTypes($objectTypes);
                $list->setOrder('asc');
                $this->o_siblings[$cacheKey] = $list->load();
                $this->o_hasSiblings[$cacheKey] = (bool) count($this->o_siblings[$cacheKey]);
            } else {
                $this->o_siblings[$cacheKey] = [];
                $this->o_hasSiblings[$cacheKey] = false;
            }
        }

        return $this->o_siblings[$cacheKey];
    }

    /**
     * Returns true if the object has at least one sibling
     *
     * @param array $objectTypes
     * @param bool|null $includingUnpublished
     *
     * @return bool
     */
    public function hasSiblings($objectTypes = [self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER], $includingUnpublished = null)
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());

        if (isset($this->o_hasSiblings[$cacheKey])) {
            return $this->o_hasSiblings[$cacheKey];
        }

        return $this->o_hasSiblings[$cacheKey] = $this->getDao()->hasSiblings($objectTypes, $includingUnpublished);
    }

    /**
     * @internal
     *
     * @throws \Exception
     */
    protected function doDelete()
    {
        // delete children
        $children = $this->getChildren(self::$types, true);
        if (count($children) > 0) {
            foreach ($children as $child) {
                $child->delete();
            }
        }

        // remove dependencies
        $d = new Model\Dependency;
        $d->cleanAllForElement($this);

        // remove all properties
        $this->getDao()->deleteAllProperties();
    }

    /**
     * @throws \Exception
     */
    public function delete()
    {
        $this->dispatchEvent(new DataObjectEvent($this), DataObjectEvents::PRE_DELETE);

        $this->beginTransaction();

        try {
            $this->doDelete();
            $this->getDao()->delete();

            $this->commit();

            //clear parent data from registry
            $parentCacheKey = self::getCacheKey($this->getParentId());
            if (Runtime::isRegistered($parentCacheKey)) {
                /** @var AbstractObject $parent * */
                $parent = Runtime::get($parentCacheKey);
                if ($parent instanceof self) {
                    $parent->setChildren(null);
                }
            }
        } catch (\Exception $e) {
            try {
                $this->rollBack();
            } catch (\Exception $er) {
                // PDO adapter throws exceptions if rollback fails
                Logger::info((string) $er);
            }

            $failureEvent = new DataObjectEvent($this);
            $failureEvent->setArgument('exception', $e);
            $this->dispatchEvent($failureEvent, DataObjectEvents::POST_DELETE_FAILURE);

            Logger::crit((string) $e);

            throw $e;
        }

        // empty object cache
        $this->clearDependentCache();

        //clear object from registry
        Runtime::set(self::getCacheKey($this->getId()), null);

        $this->dispatchEvent(new DataObjectEvent($this), DataObjectEvents::POST_DELETE);
    }

    /**
     * @return $this
     *
     * @throws \Exception
     */
    public function save()
    {
        // additional parameters (e.g. "versionNote" for the version note)
        $params = [];
        if (func_num_args() && is_array(func_get_arg(0))) {
            $params = func_get_arg(0);
        }

        $isUpdate = false;
        $differentOldPath = null;

        try {
            $isDirtyDetectionDisabled = self::isDirtyDetectionDisabled();
            $preEvent = new DataObjectEvent($this, $params);
            if ($this->getId()) {
                $isUpdate = true;
                $this->dispatchEvent($preEvent, DataObjectEvents::PRE_UPDATE);
            } else {
                self::disableDirtyDetection();
                $this->dispatchEvent($preEvent, DataObjectEvents::PRE_ADD);
            }

            $params = $preEvent->getArguments();

            $this->correctPath();

            // we wrap the save actions in a loop here, so that we can restart the database transactions in the case it fails
            // if a transaction fails it gets restarted $maxRetries times, then the exception is thrown out
            // this is especially useful to avoid problems with deadlocks in multi-threaded environments (forked workers, ...)
            $maxRetries = 5;
            for ($retries = 0; $retries < $maxRetries; $retries++) {

                // be sure that unpublished objects in relations are saved also in frontend mode, eg. in importers, ...
                $hideUnpublishedBackup = self::getHideUnpublished();
                self::setHideUnpublished(false);

                $this->beginTransaction();

                try {
                    if (!in_array($this->getType(), self::$types)) {
                        throw new \Exception('invalid object type given: [' . $this->getType() . ']');
                    }

                    if (!$isUpdate) {
                        $this->getDao()->create();
                    }

                    // get the old path from the database before the update is done
                    $oldPath = null;
                    if ($isUpdate) {
                        $oldPath = $this->getDao()->getCurrentFullPath();
                    }

                    // if the old path is different from the new path, update all children
                    // we need to do the update of the children's path before $this->update() because the
                    // inheritance helper needs the correct paths of the children in InheritanceHelper::buildTree()
                    $updatedChildren = [];
                    if ($oldPath && $oldPath != $this->getRealFullPath()) {
                        $differentOldPath = $oldPath;
                        $this->getDao()->updateWorkspaces();
                        $updatedChildren = $this->getDao()->updateChildPaths($oldPath);
                    }

                    $this->update($isUpdate, $params);

                    self::setHideUnpublished($hideUnpublishedBackup);

                    $this->commit();

                    break; // transaction was successfully completed, so we cancel the loop here -> no restart required
                } catch (\Exception $e) {
                    try {
                        $this->rollBack();
                    } catch (\Exception $er) {
                        // PDO adapter throws exceptions if rollback fails
                        Logger::info((string) $er);
                    }

                    // set "HideUnpublished" back to the value it was originally
                    self::setHideUnpublished($hideUnpublishedBackup);

                    if ($e instanceof UniqueConstraintViolationException) {
                        throw new Element\ValidationException('unique constraint violation', 0, $e);
                    }

                    if ($e instanceof RetryableException) {
                        // we try to start the transaction $maxRetries times again (deadlocks, ...)
                        if ($retries < ($maxRetries - 1)) {
                            $run = $retries + 1;
                            $waitTime = random_int(1, 5) * 100000; // microseconds
                            Logger::warn('Unable to finish transaction (' . $run . ". run) because of the following reason '" . $e->getMessage() . "'. --> Retrying in " . $waitTime . ' microseconds ... (' . ($run + 1) . ' of ' . $maxRetries . ')');

                            usleep($waitTime); // wait specified time until we restart the transaction
                        } else {
                            // if the transaction still fail after $maxRetries retries, we throw out the exception
                            Logger::error('Finally giving up restarting the same transaction again and again, last message: ' . $e->getMessage());

                            throw $e;
                        }
                    } else {
                        throw $e;
                    }
                }
            }

            $additionalTags = [];
            if (isset($updatedChildren) && is_array($updatedChildren)) {
                foreach ($updatedChildren as $objectId) {
                    $tag = 'object_' . $objectId;
                    $additionalTags[] = $tag;

                    // remove the child also from registry (internal cache) to avoid path inconsistencies during long running scripts, such as CLI
                    Runtime::set($tag, null);
                }
            }
            $this->clearDependentCache($additionalTags);

            $postEvent = new DataObjectEvent($this, $params);
            if ($isUpdate) {
                if ($differentOldPath) {
                    $postEvent->setArgument('oldPath', $differentOldPath);
                }
                $this->dispatchEvent($postEvent, DataObjectEvents::POST_UPDATE);
            } else {
                self::setDisableDirtyDetection($isDirtyDetectionDisabled);
                $this->dispatchEvent($postEvent, DataObjectEvents::POST_ADD);
            }

            return $this;
        } catch (\Exception $e) {
            $failureEvent = new DataObjectEvent($this, $params);
            $failureEvent->setArgument('exception', $e);
            if ($isUpdate) {
                $this->dispatchEvent($failureEvent, DataObjectEvents::POST_UPDATE_FAILURE);
            } else {
                $this->dispatchEvent($failureEvent, DataObjectEvents::POST_ADD_FAILURE);
            }

            throw $e;
        }
    }

    /**
     * @internal
     *
     * @throws \Exception|DuplicateFullPathException
     */
    protected function correctPath()
    {
        // set path
        if ($this->getId() != 1) { // not for the root node

            if (!Element\Service::isValidKey($this->getKey(), 'object')) {
                throw new \Exception('invalid key for object with id [ '.$this->getId().' ] key is: [' . $this->getKey() . ']');
            }

            if ($this->getParentId() == $this->getId()) {
                throw new \Exception("ParentID and ID is identical, an element can't be the parent of itself.");
            }

            $parent = DataObject::getById($this->getParentId());

            if ($parent) {
                // use the parent's path from the database here (getCurrentFullPath), to ensure the path really exists and does not rely on the path
                // that is currently in the parent object (in memory), because this might have changed but wasn't not saved
                $this->setPath(str_replace('//', '/', $parent->getCurrentFullPath().'/'));
            } else {
                // parent document doesn't exist anymore, set the parent to to root
                $this->setParentId(1);
                $this->setPath('/');
            }

            if (strlen($this->getKey()) < 1) {
                throw new \Exception('DataObject requires key');
            }
        } elseif ($this->getId() == 1) {
            // some data in root node should always be the same
            $this->setParentId(0);
            $this->setPath('/');
            $this->setKey('');
            $this->setType(DataObject::OBJECT_TYPE_FOLDER);
        }

        if (Service::pathExists($this->getRealFullPath())) {
            $duplicate = DataObject::getByPath($this->getRealFullPath());
            if ($duplicate instanceof self && $duplicate->getId() != $this->getId()) {
                $duplicateFullPathException = new DuplicateFullPathException('Duplicate full path [ '.$this->getRealFullPath().' ] - cannot save object');
                $duplicateFullPathException->setDuplicateElement($duplicate);

                throw $duplicateFullPathException;
            }
        }

        $this->validatePathLength();
    }

    /**
     * @internal
     *
     * @param bool|null $isUpdate
     * @param array $params
     *
     * @throws \Exception
     */
    protected function update($isUpdate = null, $params = [])
    {
        $this->updateModificationInfos();

        // save properties
        $this->getProperties();
        $this->getDao()->deleteAllProperties();

        if (is_array($this->getProperties()) && count($this->getProperties()) > 0) {
            foreach ($this->getProperties() as $property) {
                if (!$property->getInherited()) {
                    $property->setDao(null);
                    $property->setCid($this->getId());
                    $property->setCtype('object');
                    $property->setCpath($this->getRealFullPath());
                    $property->save();
                }
            }
        }

        // save dependencies
        $d = new Model\Dependency();
        $d->setSourceType('object');
        $d->setSourceId($this->getId());

        foreach ($this->resolveDependencies() as $requirement) {
            if ($requirement['id'] == $this->getId() && $requirement['type'] === 'object') {
                // dont't add a reference to yourself
                continue;
            }

            $d->addRequirement($requirement['id'], $requirement['type']);
        }

        $d->save();

        //set object to registry
        Runtime::set(self::getCacheKey($this->getId()), $this);
    }

    /**
     * {@inheritdoc}
     */
    public function clearDependentCache($additionalTags = [])
    {
        self::clearDependentCacheByObjectId($this->getId(), $additionalTags);
    }

    /**
     * @internal
     *
     * @param int $objectId
     * @param array $additionalTags
     */
    public static function clearDependentCacheByObjectId($objectId, $additionalTags = [])
    {
        if (!$objectId) {
            throw new \Exception('object ID missing');
        }

        try {
            $tags = ['object_' . $objectId, 'object_properties', 'output'];
            $tags = array_merge($tags, $additionalTags);

            Cache::clearTags($tags);
        } catch (\Exception $e) {
            Logger::crit((string) $e);
        }
    }

    /**
     * @internal
     *
     * @param int $index
     */
    public function saveIndex($index)
    {
        $this->getDao()->saveIndex($index);
        $this->clearDependentCache();
    }

    /**
     * @return string
     */
    public function getFullPath()
    {
        $path = $this->getPath() . $this->getKey();

        return $path;
    }

    /**
     * @return string
     */
    public function getRealPath()
    {
        return $this->getPath();
    }

    /**
     * @return string
     */
    public function getRealFullPath()
    {
        return $this->getFullPath();
    }

    /**
     * @return int|null
     */
    public function getParentId()
    {
        $parentId = parent::getParentId();

        // fall back to parent if no ID is set but we have a parent object
        if (!$parentId && $this->parent) {
            return $this->parent->getId();
        }

        return $parentId;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->o_type;
    }

    /**
     * @return string|null
     */
    public function getKey()
    {
        return $this->o_key;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->o_index;
    }

    /**
     * @param int $parentId
     *
     * @return $this
     */
    public function setParentId($parentId)
    {
        $parentId = (int) $parentId;
        if ($parentId != $this->parentId) {
            $this->markFieldDirty('parentId');
        }

        parent::setParentId($parentId);

        $this->o_siblings = [];
        $this->o_hasSiblings = [];

        return $this;
    }

    /**
     * @param string $o_type
     *
     * @return $this
     */
    public function setType($o_type)
    {
        $this->o_type = $o_type;

        return $this;
    }

    /**
     * @param string $o_key
     *
     * @return $this
     */
    public function setKey($o_key)
    {
        $this->o_key = (string)$o_key;

        return $this;
    }

    /**
     * @param int $o_index
     *
     * @return $this
     */
    public function setIndex($o_index)
    {
        $this->o_index = (int) $o_index;

        return $this;
    }

    /**
     * @param string|null $childrenSortBy
     */
    public function setChildrenSortBy($childrenSortBy)
    {
        if ($this->o_childrenSortBy !== $childrenSortBy) {
            $this->o_children = [];
            $this->o_hasChildren = [];
        }
        $this->o_childrenSortBy = $childrenSortBy;
    }

    /**
     * @param DataObject[]|null $children
     * @param array $objectTypes
     * @param bool $includingUnpublished
     *
     * @return $this
     */
    public function setChildren($children, array $objectTypes = [self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER], $includingUnpublished = false)
    {
        if ($children === null) {
            // unset all cached children
            $this->o_children = [];
            $this->o_hasChildren = [];
        } elseif (is_array($children)) {
            //default cache key
            $cacheKey = $this->getListingCacheKey([$objectTypes, $includingUnpublished]);
            $this->o_children[$cacheKey] = $children;
            $this->o_hasChildren[$cacheKey] = (bool) count($children);
        }

        return $this;
    }

    /**
     * @return self|null
     */
    public function getParent() /** : ?self **/
    {
        $parent = parent::getParent();

        return $parent instanceof AbstractObject ? $parent : null;
    }

    /**
     * @param self|null $parent
     *
     * @return $this
     */
    public function setParent($parent)
    {
        $newParentId = $parent instanceof self ? $parent->getId() : 0;
        $this->setParentId($newParentId);
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return string
     */
    public function getChildrenSortBy()
    {
        return $this->o_childrenSortBy ?? self::OBJECT_CHILDREN_SORT_BY_DEFAULT;
    }

    /**
     * @param string $method
     * @param array $args
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function __call($method, $args)
    {

        // compatibility mode (they do not have any set_oXyz() methods anymore)
        if (preg_match('/^(get|set)o_/i', $method)) {
            $newMethod = preg_replace('/^(get|set)o_/i', '$1', $method);
            if (method_exists($this, $newMethod)) {
                $r = call_user_func_array([$this, $newMethod], $args);

                return $r;
            }
        }

        return parent::__call($method, $args);
    }

    /**
     * @return bool
     */
    public static function doNotRestoreKeyAndPath()
    {
        return self::$doNotRestoreKeyAndPath;
    }

    /**
     * @param bool $doNotRestoreKeyAndPath
     */
    public static function setDoNotRestoreKeyAndPath($doNotRestoreKeyAndPath)
    {
        self::$doNotRestoreKeyAndPath = $doNotRestoreKeyAndPath;
    }

    /**
     * @param string $fieldName
     * @param string|null $language
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function get($fieldName, $language = null)
    {
        if (!$fieldName) {
            throw new \Exception('Field name must not be empty.');
        }

        return $this->{'get'.ucfirst($fieldName)}($language);
    }

    /**
     * @param string $fieldName
     * @param mixed $value
     * @param string|null $language
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function set($fieldName, $value, $language = null)
    {
        if (!$fieldName) {
            throw new \Exception('Field name must not be empty.');
        }

        return $this->{'set'.ucfirst($fieldName)}($value, $language);
    }

    /**
     * @internal
     *
     * @return bool
     */
    public static function isDirtyDetectionDisabled()
    {
        return self::$disableDirtyDetection;
    }

    /**
     * @internal
     *
     * @param bool $disableDirtyDetection
     */
    public static function setDisableDirtyDetection(bool $disableDirtyDetection)
    {
        self::$disableDirtyDetection = $disableDirtyDetection;
    }

    /**
     * @internal
     */
    public static function disableDirtyDetection()
    {
        self::setDisableDirtyDetection(true);
    }

    /**
     * @internal
     */
    public static function enableDirtyDetection()
    {
        self::setDisableDirtyDetection(false);
    }

    /**
     * @internal
     *
     * @param array $args
     *
     * @return string
     */
    protected function getListingCacheKey(array $args = [])
    {
        $objectTypes = $args[0] ?? [self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER];
        $includingUnpublished = (bool)($args[1] ?? false);

        if (is_array($objectTypes)) {
            $objectTypes = implode('_', $objectTypes);
        }

        $cacheKey = $objectTypes . (!empty($includingUnpublished) ? '_' : '') . (string)$includingUnpublished;

        return $cacheKey;
    }

    /**
     * @param string | null $o_reverseSort
     *
     * @return AbstractObject
     */
    public function setChildrenSortOrder(?string $o_reverseSort): Element\ElementInterface
    {
        $this->o_childrenSortOrder = $o_reverseSort;

        return $this;
    }

    /**
     * @return string
     */
    public function getChildrenSortOrder(): string
    {
        return $this->o_childrenSortOrder ?? self::OBJECT_CHILDREN_SORT_ORDER_DEFAULT;
    }

    /**
     * load lazy loaded fields before cloning
     */
    public function __clone()
    {
        parent::__clone();
        $this->o_parent = null;
        // note that o_children is currently needed for the recycle bin
        $this->o_hasSiblings = [];
        $this->o_siblings = [];
    }

    /**
     * @param string $method
     * @param array $arguments
     *
     * @return mixed|Listing|null
     *
     * @throws \Exception
     */
    public static function __callStatic($method, $arguments)
    {
        $propertyName = lcfirst(preg_replace('/^getBy/i', '', $method));

        $realPropertyName = 'o_'.$propertyName;

        $db = \Pimcore\Db::get();

        if (in_array(strtolower($realPropertyName), self::$objectColumns)) {
            $arguments = array_pad($arguments, 4, 0);
            [$value, $limit, $offset, $objectTypes] = $arguments;

            $defaultCondition = $realPropertyName.' = '.Db::get()->quote($value).' ';

            $listConfig = [
                'condition' => $defaultCondition,
            ];

            if (!is_array($limit)) {
                if ($limit) {
                    $listConfig['limit'] = $limit;
                }
                if ($offset) {
                    $listConfig['offset'] = $offset;
                }
            } else {
                $listConfig = array_merge($listConfig, $limit);
                $limitCondition = $limit['condition'] ?? '';
                $listConfig['condition'] = $defaultCondition.$limitCondition;
            }

            $list = static::makeList($listConfig, $objectTypes);

            if (isset($listConfig['limit']) && $listConfig['limit'] == 1) {
                $elements = $list->getObjects();

                return isset($elements[0]) ? $elements[0] : null;
            }

            return $list;
        }

        // there is no property for the called method, so throw an exception
        Logger::error('Class: DataObject\\AbstractObject => call to undefined static method ' . $method);

        throw new \Exception('Call to undefined static method ' . $method . ' in class DataObject\\AbstractObject');
    }

    /**
     * @param  array  $listConfig
     * @param  mixed $objectTypes
     *
     * @return Listing
     *
     * @throws \Exception
     */
    protected static function makeList(array $listConfig, mixed $objectTypes): Listing
    {
        $list = static::getList($listConfig);

        // Check if variants, in addition to objects, to be fetched
        if (!empty($objectTypes)) {
            if (\array_diff($objectTypes, [static::OBJECT_TYPE_VARIANT, static::OBJECT_TYPE_OBJECT])) {
                Logger::error('Class: DataObject\\AbstractObject => Unsupported object type in array ' . implode(',', $objectTypes));

                throw new \Exception('Unsupported object type in array [' . implode(',', $objectTypes) . '] in class DataObject\\AbstractObject');
            }

            $list->setObjectTypes($objectTypes);
        }

        return $list;
    }

    public function __wakeup()
    {
        $propertyMappings = [
            'o_id' => 'id',
            'o_path' => 'path',
            'o_creationDate' => 'creationDate',
            'o_userOwner' => 'userOwner',
            'o_versionCount' => 'versionCount',
            'o_locked' => 'locked',
            'o_parent' => 'parent',
            'o_properties' => 'properties',
            'o_userModification' => 'userModification',
            'o_modificationDate' => 'modificationDate',
            'o_parentId' => 'parentId',
        ];

        foreach ($propertyMappings as $oldProperty => $newProperty) {
            if ($this->$newProperty === null) {
                $this->$newProperty = $this->$oldProperty;
                $this->$oldProperty = & $this->$newProperty;
            }
        }

        parent::__wakeup();
    }
}
