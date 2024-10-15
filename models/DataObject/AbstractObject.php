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

namespace Pimcore\Model\DataObject;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Element\ElementInterface;

/**
 * @method AbstractObject\Dao getDao()
 * @method array|null getPermissions(?string $type, Model\User $user, bool $quote = true)
 * @method bool __isBasedOnLatestData()
 * @method string getCurrentFullPath()
 * @method int getChildAmount($objectTypes = [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_VARIANT, DataObject::OBJECT_TYPE_FOLDER], Model\User $user = null)
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
     * @var string[]
     */
    public static array $types = [self::OBJECT_TYPE_FOLDER, self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_VARIANT];

    private static bool $hideUnpublished = false;

    private static bool $getInheritedValues = false;

    /**
     * @internal
     */
    protected static bool $disableDirtyDetection = false;

    /**
     * @internal
     *
     * @var string[]
     */
    protected static array $objectColumns = ['id', 'parentid', 'type', 'key', 'classid', 'classname', 'path'];

    /**
     * @internal
     */
    protected string $type = 'object';

    /**
     * @internal
     */
    protected ?string $key = null;

    /**
     * @internal
     */
    protected int $index = 0;

    /**
     * Contains a list of sibling documents
     *
     * @internal
     *
     * @var array<string, Listing>
     */
    protected array $siblings = [];

    /**
     * @internal
     *
     * @var array<string, Listing>
     */
    protected array $children = [];

    /**
     * @internal
     */
    protected ?string $childrenSortBy = null;

    /**
     * @internal
     */
    protected ?string $childrenSortOrder = null;

    /**
     * @internal
     *
     * @var string|null
     */
    protected $classId = null;

    /**
     * @internal
     *
     */
    protected ?array $__rawRelationData = null;

    protected function getBlockedVars(): array
    {
        $blockedVars = ['versions', 'class', 'scheduledTasks', 'omitMandatoryCheck'];

        if ($this->isInDumpState()) {
            // this is if we want to make a full dump of the object (eg. for a new version), including children for recyclebin
            $blockedVars = array_merge($blockedVars, ['dirtyFields']);
        } else {
            // this is if we want to cache the object
            $blockedVars = array_merge($blockedVars, ['children', 'properties']);
        }

        return $blockedVars;
    }

    public static function getHideUnpublished(): bool
    {
        return self::$hideUnpublished;
    }

    public static function setHideUnpublished(bool $hideUnpublished): void
    {
        self::$hideUnpublished = $hideUnpublished;
    }

    public static function doHideUnpublished(): bool
    {
        return self::$hideUnpublished;
    }

    public static function setGetInheritedValues(bool $getInheritedValues): void
    {
        self::$getInheritedValues = $getInheritedValues;
    }

    public static function getGetInheritedValues(): bool
    {
        return self::$getInheritedValues;
    }

    public static function doGetInheritedValues(Concrete $object = null): bool
    {
        if (self::$getInheritedValues && $object !== null) {
            $class = $object->getClass();

            return $class->getAllowInherit();
        }

        return self::$getInheritedValues;
    }

    public static function getTypes(): array
    {
        return self::$types;
    }

    /**
     * Static helper to get an object by the passed ID
     */
    public static function getById(int|string $id, array $params = []): ?static
    {
        if (is_string($id)) {
            trigger_deprecation(
                'pimcore/pimcore',
                '11.0',
                sprintf('Passing id as string to method %s is deprecated', __METHOD__)
            );
            $id = is_numeric($id) ? (int) $id : 0;
        }
        if ($id < 1) {
            return null;
        }

        $cacheKey = self::getCacheKey($id);

        $params = Model\Element\Service::prepareGetByIdParams($params);

        if (!$params['force'] && RuntimeCache::isRegistered($cacheKey)) {
            $object = RuntimeCache::get($cacheKey);
            if ($object && static::typeMatch($object)) {
                return $object;
            }
        }

        if ($params['force'] || !($object = Cache::load($cacheKey))) {
            $object = new Model\DataObject();

            try {
                $typeInfo = $object->getDao()->getTypeById($id);

                if (!empty($typeInfo['type']) && in_array($typeInfo['type'], DataObject::$types)) {
                    if ($typeInfo['type'] == DataObject::OBJECT_TYPE_FOLDER) {
                        $className = Folder::class;
                    } else {
                        $className = 'Pimcore\\Model\\DataObject\\' . ucfirst($typeInfo['className']);
                    }

                    /** @var AbstractObject $object */
                    $object = self::getModelFactory()->build($className);
                    RuntimeCache::set($cacheKey, $object);
                    $object->getDao()->getById($id);
                    $object->__setDataVersionTimestamp($object->getModificationDate() ?? 0);

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
                Logger::error($e->getMessage());

                return null;
            }
        } else {
            RuntimeCache::set($cacheKey, $object);
        }

        if (!$object || !static::typeMatch($object)) {
            return null;
        }

        Pimcore::getEventDispatcher()->dispatch(
            new DataObjectEvent($object, ['params' => $params]),
            DataObjectEvents::POST_LOAD
        );

        return $object;
    }

    public static function getByPath(string $path, array $params = []): static|null
    {
        if (!$path) {
            return null;
        }

        $path = Model\Element\Service::correctPath($path);

        try {
            $object = new static();
            $object->getDao()->getByPath($path);

            return static::getById($object->getId(), Model\Element\Service::prepareGetByIdParams($params));
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @return DataObject\Listing
     *
     * @throws Exception
     */
    public static function getList(array $config = []): Listing
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

        throw new Exception('Unable to initiate list class - class not found or invalid configuration');
    }

    /**
     * @internal
     */
    protected static function typeMatch(AbstractObject $object): bool
    {
        return static::class === self::class || $object instanceof static;
    }

    /**
     * Returns a list of the children objects
     */
    public function getChildren(
        array $objectTypes = [
            self::OBJECT_TYPE_OBJECT,
            self::OBJECT_TYPE_VARIANT,
            self::OBJECT_TYPE_FOLDER,
        ],
        bool $includingUnpublished = false
    ): Listing {
        $cacheKey = $this->getListingCacheKey(func_get_args());

        if (!isset($this->children[$cacheKey])) {
            if ($this->getId()) {
                $list = new Listing();
                $list->setUnpublished($includingUnpublished);
                $list->setCondition('parentId = ?', $this->getId());
                $list->setOrderKey($this->getChildrenSortBy());
                $list->setOrder($this->getChildrenSortOrder());
                $list->setObjectTypes($objectTypes);
                $this->children[$cacheKey] = $list;
            } else {
                $list = new Listing();
                $list->setObjects([]);
                $this->children[$cacheKey] = $list;
            }
        }

        return $this->children[$cacheKey];
    }

    /**
     * Quick test if there are children
     */
    public function hasChildren(
        array $objectTypes = [
            self::OBJECT_TYPE_OBJECT,
            self::OBJECT_TYPE_VARIANT,
            self::OBJECT_TYPE_FOLDER,
        ],
        ?bool $includingUnpublished = null
    ): bool {
        return $this->getDao()->hasChildren($objectTypes, $includingUnpublished);
    }

    /**
     * Get a list of the sibling objects
     */
    public function getSiblings(
        array $objectTypes = [
            self::OBJECT_TYPE_OBJECT,
            self::OBJECT_TYPE_VARIANT,
            self::OBJECT_TYPE_FOLDER,
        ],
        bool $includingUnpublished = false
    ): Listing {
        $cacheKey = $this->getListingCacheKey(func_get_args());

        if (!isset($this->siblings[$cacheKey])) {
            $parentElement = $this->getParent();
            if ($parentElement) {
                $list = new Listing();
                $list->setUnpublished($includingUnpublished);
                $list->addConditionParam('parentId = ?', $parentElement->getId());
                if ($this->getId()) {
                    $list->addConditionParam('id != ?', $this->getId());
                }
                $list->setObjectTypes($objectTypes);
                $list->setOrderKey($parentElement->getChildrenSortBy());
                $list->setOrder($parentElement->getChildrenSortOrder());
                $this->siblings[$cacheKey] = $list;
            } else {
                $list = new Listing();
                $list->setObjects([]);
                $this->siblings[$cacheKey] = $list;
            }
        }

        return $this->siblings[$cacheKey];
    }

    /**
     * Returns true if the object has at least one sibling
     */
    public function hasSiblings(
        array $objectTypes = [
            self::OBJECT_TYPE_OBJECT,
            self::OBJECT_TYPE_VARIANT,
            self::OBJECT_TYPE_FOLDER,
        ],
        ?bool $includingUnpublished = null
    ): bool {
        return $this->getDao()->hasSiblings($objectTypes, $includingUnpublished);
    }

    /**
     * @internal
     *
     * @throws Exception
     */
    protected function doDelete(): void
    {
        // delete children
        $children = $this->getChildren(self::$types, true);
        foreach ($children as $child) {
            $child->delete();
        }

        // remove dependencies
        $d = new Model\Dependency;
        $d->cleanAllForElement($this);

        // remove all properties
        $this->getDao()->deleteAllProperties();
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        $this->dispatchEvent(new DataObjectEvent($this), DataObjectEvents::PRE_DELETE);

        $this->beginTransaction();

        try {
            $this->doDelete();
            $this->getDao()->delete();

            $this->commit();

            //clear parent data from registry
            $parentCacheKey = self::getCacheKey($this->getParentId());
            if (RuntimeCache::isRegistered($parentCacheKey)) {
                /** @var AbstractObject $parent * */
                $parent = RuntimeCache::get($parentCacheKey);
                if ($parent instanceof self) {
                    $parent->setChildren(null);
                }
            }
        } catch (Exception $e) {
            try {
                $this->rollBack();
            } catch (Exception $er) {
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
        RuntimeCache::set(self::getCacheKey($this->getId()), null);

        $this->dispatchEvent(new DataObjectEvent($this), DataObjectEvents::POST_DELETE);
    }

    public function save(array $parameters = []): static
    {
        $isUpdate = false;
        $differentOldPath = null;

        try {
            $isDirtyDetectionDisabled = self::isDirtyDetectionDisabled();
            $preEvent = new DataObjectEvent($this, $parameters);
            if ($this->getId()) {
                $isUpdate = true;
                $this->dispatchEvent($preEvent, DataObjectEvents::PRE_UPDATE);
            } else {
                self::disableDirtyDetection();
                $this->dispatchEvent($preEvent, DataObjectEvents::PRE_ADD);
            }

            $parameters = $preEvent->getArguments();

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
                        throw new Exception('invalid object type given: [' . $this->getType() . ']');
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

                    $this->update($isUpdate, $parameters);

                    self::setHideUnpublished($hideUnpublishedBackup);

                    $this->commit();

                    break; // transaction was successfully completed, so we cancel the loop here -> no restart required
                } catch (Exception $e) {
                    try {
                        $this->rollBack();
                    } catch (Exception $er) {
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
            if (isset($updatedChildren)) {
                foreach ($updatedChildren as $objectId) {
                    $tag = 'object_' . $objectId;
                    $additionalTags[] = $tag;

                    // remove the child also from registry (internal cache) to avoid path inconsistencies during long running scripts, such as CLI
                    RuntimeCache::set($tag, null);
                }
            }
            $this->clearDependentCache($additionalTags);

            if ($differentOldPath) {
                $this->renewInheritedProperties();
            }

            // add to queue that saves dependencies
            $this->addToDependenciesQueue();

            //Reset Relational data to force a reload
            $this->__rawRelationData = null;

            $postEvent = new DataObjectEvent($this, $parameters);
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
        } catch (Exception $e) {
            $failureEvent = new DataObjectEvent($this, $parameters);
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
     * @throws Exception|DuplicateFullPathException
     */
    protected function correctPath(): void
    {
        // set path
        if ($this->getId() != 1) { // not for the root node
            if (!Element\Service::isValidKey($this->getKey(), 'object')) {
                throw new Exception('invalid key for object with id [ '.$this->getId().' ] key is: [' . $this->getKey() . ']');
            }

            if (!$this->getParentId()) {
                throw new Exception('ParentID is mandatory and can´t be null. If you want to add the element as a child to the tree´s root node, consider setting ParentID to 1.');
            }

            if ($this->getParentId() == $this->getId()) {
                throw new Exception("ParentID and ID are identical, an element can't be the parent of itself in the tree.");
            }

            $parent = DataObject::getById($this->getParentId());
            if (!$parent) {
                throw new Exception('ParentID not found.');
            }

            // use the parent's path from the database here (getCurrentFullPath), to ensure the path really exists and does not rely on the path
            // that is currently in the parent object (in memory), because this might have changed but wasn't not saved
            $this->setPath(str_replace('//', '/', $parent->getCurrentFullPath().'/'));

            if (strlen($this->getKey()) < 1) {
                throw new Exception('DataObject requires key');
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
                $duplicateFullPathException->setCauseElement($this);

                throw $duplicateFullPathException;
            }
        }

        $this->validatePathLength();
    }

    /*
     * @throws Exception
     *
     * @internal
     */
    protected function update(bool $isUpdate = null, array $params = []): void
    {
        $this->updateModificationInfos();

        if ($this->isFieldDirty('properties')) {
            // save properties
            $properties = $this->getProperties();
            $this->getDao()->deleteAllProperties();

            foreach ($properties as $property) {
                if (!$property->getInherited()) {
                    $property->setDao(null);
                    $property->setCid($this->getId());
                    $property->setCtype('object');
                    $property->setCpath($this->getRealFullPath());
                    $property->save();
                }
            }
        }

        // set object to registry
        RuntimeCache::set(self::getCacheKey($this->getId()), $this);
    }

    public function clearDependentCache(array $additionalTags = []): void
    {
        self::clearDependentCacheByObjectId($this->getId(), $additionalTags);
    }

    /**
     * @internal
     */
    public static function clearDependentCacheByObjectId(int $objectId, array $additionalTags = []): void
    {
        if (!$objectId) {
            throw new Exception('object ID missing');
        }

        try {
            $tags = ['object_' . $objectId, 'object_properties', 'output'];
            $tags = array_merge($tags, $additionalTags);

            Cache::clearTags($tags);
        } catch (Exception $e) {
            Logger::crit((string) $e);
        }
    }

    /**
     * @internal
     */
    public function saveIndex(int $index): void
    {
        $this->getDao()->saveIndex($index);
        $this->clearDependentCache();
    }

    public function getFullPath(): string
    {
        $path = $this->getPath() . $this->getKey();

        return $path;
    }

    public function getRealPath(): string
    {
        return $this->getPath();
    }

    public function getRealFullPath(): string
    {
        return $this->getFullPath();
    }

    public function getParentId(): ?int
    {
        $parentId = parent::getParentId();

        // fall back to parent if no ID is set but we have a parent object
        if (!$parentId && $this->parent) {
            return $this->parent->getId();
        }

        return $parentId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function setParentId(?int $parentId): static
    {
        $parentId = (int) $parentId;
        if ($parentId != $this->parentId) {
            $this->markFieldDirty('parentId');
        }

        parent::setParentId($parentId);

        $this->siblings = [];

        return $this;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function setIndex(int $index): static
    {
        $this->index = $index;

        return $this;
    }

    public function setChildrenSortBy(?string $childrenSortBy): void
    {
        if ($this->childrenSortBy !== $childrenSortBy) {
            $this->children = [];
        }
        $this->childrenSortBy = $childrenSortBy;
    }

    /**
     * @return $this
     */
    public function setChildren(
        ?Listing $children,
        array $objectTypes = [
            self::OBJECT_TYPE_OBJECT,
            self::OBJECT_TYPE_VARIANT,
            self::OBJECT_TYPE_FOLDER,
        ],
        bool $includingUnpublished = false
    ): static {
        if ($children === null) {
            // unset all cached children
            $this->children = [];
        } else {
            //default cache key
            $cacheKey = $this->getListingCacheKey([$objectTypes, $includingUnpublished]);
            $this->children[$cacheKey] = $children;
        }

        return $this;
    }

    public function getParent(): ?AbstractObject
    {
        $parent = parent::getParent();

        return $parent instanceof AbstractObject ? $parent : null;
    }

    public function setParent(?ElementInterface $parent): static
    {
        $newParentId = $parent instanceof self ? $parent->getId() : 0;
        $this->setParentId($newParentId);
        /** @var Element\AbstractElement $parent */
        $this->parent = $parent;

        return $this;
    }

    public function getChildrenSortBy(): string
    {
        return $this->childrenSortBy ?? self::OBJECT_CHILDREN_SORT_BY_DEFAULT;
    }

    public static function doNotRestoreKeyAndPath(): bool
    {
        return self::$doNotRestoreKeyAndPath;
    }

    public static function setDoNotRestoreKeyAndPath(bool $doNotRestoreKeyAndPath): void
    {
        self::$doNotRestoreKeyAndPath = $doNotRestoreKeyAndPath;
    }

    /**
     * @throws Exception
     */
    public function get(string $fieldName, string $language = null): mixed
    {
        if (!$fieldName) {
            throw new Exception('Field name must not be empty.');
        }

        return $this->{'get'.ucfirst($fieldName)}($language);
    }

    /**
     * @throws Exception
     */
    public function set(string $fieldName, mixed $value, string $language = null): mixed
    {
        if (!$fieldName) {
            throw new Exception('Field name must not be empty.');
        }

        return $this->{'set'.ucfirst($fieldName)}($value, $language);
    }

    /**
     * @internal
     */
    public static function isDirtyDetectionDisabled(): bool
    {
        return self::$disableDirtyDetection;
    }

    /**
     * @internal
     */
    public static function setDisableDirtyDetection(bool $disableDirtyDetection): void
    {
        self::$disableDirtyDetection = $disableDirtyDetection;
    }

    /**
     * @internal
     */
    public static function disableDirtyDetection(): void
    {
        self::setDisableDirtyDetection(true);
    }

    /**
     * @internal
     */
    public static function enableDirtyDetection(): void
    {
        self::setDisableDirtyDetection(false);
    }

    /**
     * @internal
     */
    protected function getListingCacheKey(array $args = []): string
    {
        $objectTypes = $args[0] ?? [self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_VARIANT, self::OBJECT_TYPE_FOLDER];
        $includingUnpublished = (bool)($args[1] ?? false);

        if (is_array($objectTypes)) {
            $objectTypes = implode('_', $objectTypes);
        }

        $cacheKey = $objectTypes . (!empty($includingUnpublished) ? '_' : '') . (string)$includingUnpublished;

        return $cacheKey;
    }

    /**
     * @return AbstractObject
     */
    public function setChildrenSortOrder(?string $reverseSort): Element\ElementInterface
    {
        $this->childrenSortOrder = $reverseSort;

        return $this;
    }

    public function getChildrenSortOrder(): string
    {
        return $this->childrenSortOrder ?? self::OBJECT_CHILDREN_SORT_ORDER_DEFAULT;
    }

    /**
     * @return $this
     */
    public function setClassId(string $classId): static
    {
        $this->classId = $classId;

        return $this;
    }

    public function getClassId(): ?string
    {
        return $this->classId;
    }

    /**
     * @internal
     *
     */
    public function __getRawRelationData(): array
    {
        if ($this->__rawRelationData === null) {
            $db = Db::get();
            $this->__rawRelationData = $db->fetchAllAssociative('SELECT * FROM object_relations_' . $this->getClassId() . ' WHERE src_id = ?', [$this->getId()]);
        }

        return $this->__rawRelationData;
    }

    /**
     * load lazy loaded fields before cloning
     */
    public function __clone(): void
    {
        parent::__clone();

        $this->parent = null;
        // note that children is currently needed for the recycle bin
        $this->siblings = [];
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $propertyName = lcfirst(preg_replace('/^getBy/i', '', $method));

        $db = \Pimcore\Db::get();

        if (in_array(strtolower($propertyName), self::$objectColumns)) {
            $value = array_key_exists(0, $arguments) ? $arguments[0] : throw new InvalidArgumentException('Mandatory argument $value not set.');
            $limit = $arguments[1] ?? null;
            $offset = $arguments[2] ?? 0;
            $objectTypes = $arguments[3] ?? null;

            $defaultCondition = $db->quoteIdentifier($propertyName).' = '.$db->quote($value).' ';

            $listConfig = [
                'condition' => $defaultCondition,
            ];

            if (!is_array($limit)) {
                $listConfig['limit'] = $limit;
                $listConfig['offset'] = $offset;
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

        throw new Exception('Call to undefined static method ' . $method . ' in class DataObject\\AbstractObject');
    }

    /**
     * @throws Exception
     */
    protected static function makeList(array $listConfig, ?array $objectTypes): Listing
    {
        $allowedObjectTypes = [static::OBJECT_TYPE_VARIANT, static::OBJECT_TYPE_OBJECT];
        $list = static::getList($listConfig);

        if (empty($objectTypes)) {
            $objectTypes = $allowedObjectTypes;
        } elseif (array_diff($objectTypes, $allowedObjectTypes)) {
            Logger::error('Class: DataObject\\AbstractObject => Unsupported object type in array ' . implode(',', $objectTypes));

            throw new Exception('Unsupported object type in array [' . implode(',', $objectTypes) . '] in class DataObject\\AbstractObject');
        }

        $list->setObjectTypes($objectTypes);

        return $list;
    }
}
