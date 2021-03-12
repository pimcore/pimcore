<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Pimcore\Cache;
use Pimcore\Cache\Runtime;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element;

/**
 * @method AbstractObject\Dao getDao()
 * @method array|null getPermissions(string $type, Model\User $user, bool $quote = true)
 * @method bool __isBasedOnLatestData()
 * @method string getCurrentFullPath()
 * @method int getChildAmount($objectTypes = [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER], Model\User $user = null)
 * @method array getChildPermissions(string $type, Model\User $user, bool $quote = true)
 */
class AbstractObject extends Model\Element\AbstractElement
{
    const OBJECT_TYPE_FOLDER = 'folder';
    const OBJECT_TYPE_OBJECT = 'object';
    const OBJECT_TYPE_VARIANT = 'variant';

    const OBJECT_CHILDREN_SORT_BY_DEFAULT = 'key';
    const OBJECT_CHILDREN_SORT_BY_INDEX = 'index';
    const OBJECT_CHILDREN_SORT_ORDER_DEFAULT = 'ASC';

    /**
     * @var bool
     */
    public static $doNotRestoreKeyAndPath = false;

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
     * @var bool
     */
    protected static $disableDirtyDetection = false;

    /**
     * @var int
     */
    protected $o_id = 0;

    /**
     * @var int
     */
    protected $o_parentId;

    /**
     * @var self|null
     */
    protected $o_parent;

    /**
     * @var string
     */
    protected $o_type = 'object';

    /**
     * @var string
     */
    protected $o_key;

    /**
     * @var string
     */
    protected $o_path;

    /**
     * @var int
     */
    protected $o_index;

    /**
     * @var int
     */
    protected $o_creationDate;

    /**
     * @var int
     */
    protected $o_modificationDate;

    /**
     * @var int
     */
    protected $o_userOwner;

    /**
     * @var int
     */
    protected $o_userModification;

    /**
     * @var array
     */
    protected $o_properties = null;

    /**
     * @var bool[]
     */
    protected $o_hasChildren = [];

    /**
     * Contains a list of sibling documents
     *
     * @var array
     */
    protected $o_siblings = [];

    /**
     * Indicator if object has siblings or not
     *
     * @var bool[]
     */
    protected $o_hasSiblings = [];

    /**
     * @var array
     */
    protected $o_children = [];

    /**
     * @var string
     */
    protected $o_locked;

    /**
     * @var Model\Element\AdminStyle
     */
    protected $o_elementAdminStyle;

    /**
     * @var string
     */
    protected $o_childrenSortBy;

    /**
     * @var string
     */
    protected $o_childrenSortOrder;

    /**
     * @var int
     */
    protected $o_versionCount = 0;

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
     * @param Concrete $object
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

        $id = intval($id);
        $cacheKey = self::getCacheKey($id);

        if (!$force && Runtime::isRegistered($cacheKey)) {
            $object = Runtime::get($cacheKey);
            if ($object && static::typeMatch($object)) {
                return $object;
            }
        }

        try {
            if ($force || !($object = Cache::load($cacheKey))) {
                $object = new Model\DataObject();
                $typeInfo = $object->getDao()->getTypeById($id);

                if (!empty($typeInfo['o_type']) && ($typeInfo['o_type'] == 'object' || $typeInfo['o_type'] == 'variant' || $typeInfo['o_type'] == 'folder')) {
                    if ($typeInfo['o_type'] == 'folder') {
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
                    throw new \Exception('No entry for object id ' . $id);
                }
            } else {
                Runtime::set($cacheKey, $object);
            }
        } catch (\Exception $e) {
            return null;
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
        $path = Model\Element\Service::correctPath($path);

        try {
            $object = new self();
            $object->getDao()->getByPath($path);

            return static::getById($object->getId(), $force);
        } catch (\Exception $e) {
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
     * @return self[]
     */
    public function getChildren(array $objectTypes = [self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER], $includingUnpublished = false)
    {
        $cacheKey = $this->getListingCacheKey(func_get_args());

        if (!isset($this->o_children[$cacheKey])) {
            $list = new Listing();
            $list->setUnpublished($includingUnpublished);
            $list->setCondition('o_parentId = ?', $this->getId());
            $list->setOrderKey(sprintf('o_%s', $this->getChildrenSortBy()));
            $list->setOrder($this->getChildrenSortOrder());
            $list->setObjectTypes($objectTypes);
            $this->o_children[$cacheKey] = $list->load();
            $this->o_hasChildren[$cacheKey] = (bool) count($this->o_children[$cacheKey]);
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
            $list = new Listing();
            $list->setUnpublished($includingUnpublished);
            // string conversion because parentId could be 0
            $list->addConditionParam('o_parentId = ?', (string)$this->getParentId());
            $list->addConditionParam('o_id != ?', $this->getId());
            $list->setOrderKey('o_key');
            $list->setObjectTypes($objectTypes);
            $list->setOrder('asc');
            $this->o_siblings[$cacheKey] = $list->load();
            $this->o_hasSiblings[$cacheKey] = (bool) count($this->o_siblings[$cacheKey]);
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
     * enum('self','propagate') nullable
     *
     * @return string|null
     */
    public function getLocked()
    {
        return $this->o_locked;
    }

    /**
     * enum('self','propagate') nullable
     *
     * @param string|null $o_locked
     *
     * @return $this
     */
    public function setLocked($o_locked)
    {
        $this->o_locked = $o_locked;

        return $this;
    }

    /**
     * @throws \Exception
     */
    protected function doDelete()
    {
        // delete children
        $children = $this->getChildren([self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_FOLDER, self::OBJECT_TYPE_VARIANT], true);
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

        // remove all permissions
        $this->getDao()->deleteAllPermissions();
    }

    /**
     * @throws \Exception
     */
    public function delete()
    {
        \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::PRE_DELETE, new DataObjectEvent($this));

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
            $this->rollBack();
            $failureEvent = new DataObjectEvent($this);
            $failureEvent->setArgument('exception', $e);
            \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_DELETE_FAILURE, $failureEvent);

            Logger::crit($e);
            throw $e;
        }

        // empty object cache
        $this->clearDependentCache();

        //clear object from registry
        Runtime::set(self::getCacheKey($this->getId()), null);

        \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_DELETE, new DataObjectEvent($this));
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
                \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::PRE_UPDATE, $preEvent);
            } else {
                self::disableDirtyDetection();
                \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::PRE_ADD, $preEvent);
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
                        Logger::info($er);
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

            if ($isUpdate) {
                $updateEvent = new DataObjectEvent($this);
                if ($differentOldPath) {
                    $updateEvent->setArgument('oldPath', $differentOldPath);
                }
                \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_UPDATE, $updateEvent);
            } else {
                self::setDisableDirtyDetection($isDirtyDetectionDisabled);
                \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_ADD, new DataObjectEvent($this));
            }

            return $this;
        } catch (\Exception $e) {
            $failureEvent = new DataObjectEvent($this);
            $failureEvent->setArgument('exception', $e);
            if ($isUpdate) {
                \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_UPDATE_FAILURE, $failureEvent);
            } else {
                \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_ADD_FAILURE, $failureEvent);
            }

            throw $e;
        }
    }

    public function correctPath()
    {
        // set path
        if ($this->getId() != 1) { // not for the root node

            if (!Element\Service::isValidKey($this->getKey(), 'object')) {
                throw new \Exception('invalid key for object with id [ '.$this->getId().' ] key is: [' . $this->getKey() . ']');
            }

            if ($this->getParentId() == $this->getId()) {
                throw new \Exception("ParentID and ID is identical, an element can't be the parent of itself.");
            }

            $parent = AbstractObject::getById($this->getParentId());

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
            $this->setType('folder');
        }

        if (Service::pathExists($this->getRealFullPath())) {
            $duplicate = AbstractObject::getByPath($this->getRealFullPath());
            if ($duplicate instanceof self && $duplicate->getId() != $this->getId()) {
                throw new \Exception('Duplicate full path [ '.$this->getRealFullPath().' ] - cannot save object');
            }
        }

        $this->validatePathLength();
    }

    /**
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
     * @param array $additionalTags
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
            Logger::crit($e);
        }
    }

    /**
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
     * @return int
     */
    public function getId()
    {
        return $this->o_id;
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        // fall back to parent if no ID is set but we have a parent object
        if (!$this->o_parentId && $this->o_parent) {
            return $this->o_parent->getId();
        }

        return $this->o_parentId;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->o_type;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->o_key;
    }

    /**
     * @return string path
     */
    public function getPath()
    {
        return $this->o_path;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->o_index;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->o_creationDate;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->o_modificationDate;
    }

    /**
     * @return int
     */
    public function getUserOwner()
    {
        return $this->o_userOwner;
    }

    /**
     * @return int
     */
    public function getUserModification()
    {
        return $this->o_userModification;
    }

    /**
     * @param int $o_id
     *
     * @return $this
     */
    public function setId($o_id)
    {
        $this->o_id = (int) $o_id;

        return $this;
    }

    /**
     * @param int $o_parentId
     *
     * @return $this
     */
    public function setParentId($o_parentId)
    {
        $o_parentId = (int) $o_parentId;
        if ($o_parentId != $this->o_parentId) {
            $this->markFieldDirty('o_parentId');
        }
        $this->o_parentId = $o_parentId;
        $this->o_parent = null;
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
        $this->o_key = $o_key;

        return $this;
    }

    /**
     * @param string $o_path
     *
     * @return $this
     */
    public function setPath($o_path)
    {
        $this->o_path = $o_path;

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
     * @param int $o_creationDate
     *
     * @return $this
     */
    public function setCreationDate($o_creationDate)
    {
        $this->o_creationDate = (int) $o_creationDate;

        return $this;
    }

    /**
     * @param int $o_modificationDate
     *
     * @return $this
     */
    public function setModificationDate($o_modificationDate)
    {
        $this->markFieldDirty('o_modificationDate');

        $this->o_modificationDate = (int) $o_modificationDate;

        return $this;
    }

    /**
     * @param int $o_userOwner
     *
     * @return $this
     */
    public function setUserOwner($o_userOwner)
    {
        $this->o_userOwner = (int) $o_userOwner;

        return $this;
    }

    /**
     * @param int $o_userModification
     *
     * @return $this
     */
    public function setUserModification($o_userModification)
    {
        $this->markFieldDirty('o_userModification');

        $this->o_userModification = (int) $o_userModification;

        return $this;
    }

    /**
     * @param array|null $children
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
     * @return self
     */
    public function getParent()
    {
        if ($this->o_parent === null) {
            $this->setParent(AbstractObject::getById($this->getParentId()));
        }

        return $this->o_parent;
    }

    /**
     * @param self $o_parent
     *
     * @return $this
     */
    public function setParent($o_parent)
    {
        $newParentId = $o_parent instanceof self ? $o_parent->getId() : 0;
        $this->setParentId($newParentId);
        $this->o_parent = $o_parent;

        return $this;
    }

    /**
     * @return Model\Property[]
     */
    public function getProperties()
    {
        if ($this->o_properties === null) {
            // try to get from cache
            $cacheKey = 'object_properties_' . $this->getId();
            $properties = Cache::load($cacheKey);
            if (!is_array($properties)) {
                $properties = $this->getDao()->getProperties();
                $elementCacheTag = $this->getCacheTag();
                $cacheTags = ['object_properties' => 'object_properties', $elementCacheTag => $elementCacheTag];
                Cache::save($properties, $cacheKey, $cacheTags);
            }

            $this->setProperties($properties);
        }

        return $this->o_properties;
    }

    /**
     * @param Model\Property[] $o_properties
     *
     * @return $this
     */
    public function setProperties($o_properties)
    {
        $this->o_properties = $o_properties;

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
        $property->setCtype('object');
        $property->setData($data);
        $property->setInherited($inherited);
        $property->setInheritable($inheritable);

        $this->o_properties[$name] = $property;

        return $this;
    }

    /**
     * @deprecated since 6.4.1, use AdminEvents.RESOLVE_ELEMENT_ADMIN_STYLE event instead
     *
     * @return Model\Element\AdminStyle
     */
    public function getElementAdminStyle()
    {
        if (empty($this->o_elementAdminStyle)) {
            $this->o_elementAdminStyle = new Model\Element\AdminStyle($this);
        }

        return $this->o_elementAdminStyle;
    }

    /**
     * @return string
     */
    public function getChildrenSortBy()
    {
        return $this->o_childrenSortBy ?? self::OBJECT_CHILDREN_SORT_BY_DEFAULT;
    }

    public function __sleep()
    {
        $parentVars = parent::__sleep();

        $blockedVars = ['o_hasChildren', 'o_versions', 'o_class', 'scheduledTasks', 'o_parent', 'omitMandatoryCheck'];

        if ($this->isInDumpState()) {
            // this is if we want to make a full dump of the object (eg. for a new version), including children for recyclebin
            $blockedVars = array_merge($blockedVars, ['o_dirtyFields']);
            $this->removeInheritedProperties();
        } else {
            // this is if we want to cache the object
            $blockedVars = array_merge($blockedVars, ['o_children', 'o_properties']);
        }

        return array_diff($parentVars, $blockedVars);
    }

    public function __wakeup()
    {
        if ($this->isInDumpState() && !self::$doNotRestoreKeyAndPath) {
            // set current key and path this is necessary because the serialized data can have a different path than the original element ( element was renamed or moved )
            $originalElement = AbstractObject::getById($this->getId());
            if ($originalElement) {
                $this->setKey($originalElement->getKey());
                $this->setPath($originalElement->getRealPath());
            }
        }

        if ($this->isInDumpState() && $this->o_properties !== null) {
            $this->renewInheritedProperties();
        }

        $this->setInDumpState(false);
    }

    public function removeInheritedProperties()
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

    public function renewInheritedProperties()
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
     * @return bool
     */
    public static function isDirtyDetectionDisabled()
    {
        return self::$disableDirtyDetection;
    }

    /**
     * @param bool $disableDirtyDetection
     */
    public static function setDisableDirtyDetection(bool $disableDirtyDetection)
    {
        self::$disableDirtyDetection = $disableDirtyDetection;
    }

    /**
     * Disables the dirty detection
     */
    public static function disableDirtyDetection()
    {
        self::setDisableDirtyDetection(true);
    }

    /**
     * Enables the dirty detection
     */
    public static function enableDirtyDetection()
    {
        self::setDisableDirtyDetection(false);
    }

    /**
     * @return int
     */
    public function getVersionCount(): int
    {
        return $this->o_versionCount ? $this->o_versionCount : 0;
    }

    /**
     * @param int|null $o_versionCount
     *
     * @return AbstractObject
     */
    public function setVersionCount(?int $o_versionCount): Element\ElementInterface
    {
        $this->o_versionCount = (int) $o_versionCount;

        return $this;
    }

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
}

class_alias(AbstractObject::class, 'Pimcore\\Model\\DataObject');
