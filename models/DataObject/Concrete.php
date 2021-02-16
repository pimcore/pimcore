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

use Pimcore\Db;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data\LazyLoadingSupportInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\Element\DirtyIndicatorInterface;

/**
 * @method \Pimcore\Model\DataObject\Concrete\Dao getDao()
 * @method \Pimcore\Model\Version getLatestVersion()
 */
class Concrete extends AbstractObject implements LazyLoadedFieldsInterface
{
    use Model\DataObject\Traits\LazyLoadedRelationTrait;

    /** @var array|null */
    protected $__rawRelationData = null;

    /**
     * @var array
     */
    public static $systemColumnNames = ['id', 'fullpath', 'key', 'published', 'creationDate', 'modificationDate', 'filename', 'classname'];

    /**
     * @var bool
     */
    protected $o_published;

    /**
     * @var ClassDefinition|null
     */
    protected $o_class;

    /**
     * @var string
     */
    protected $o_classId;

    /**
     * @var string
     */
    protected $o_className;

    /**
     * @var array|null
     */
    protected $o_versions = null;

    /**
     * Contains all scheduled tasks
     *
     * @var array|null
     */
    protected $scheduledTasks = null;

    /**
     * @var bool|null
     */
    protected $omitMandatoryCheck;

    /**
     * @var bool
     */
    protected $allLazyKeysMarkedAsLoaded = false;

    /**
     * returns the class ID of the current object class
     *
     * @return int
     */
    public static function classId()
    {
        $v = get_class_vars(get_called_class());

        return $v['o_classId'];
    }

    public function __construct()
    {
        // nothing to do here
    }

    /**
     * @param bool|null $isUpdate
     * @param array $params additional parameters (e.g. "versionNote" for the version note)
     *
     * @throws \Exception
     */
    protected function update($isUpdate = null, $params = [])
    {
        $fieldDefintions = $this->getClass()->getFieldDefinitions();

        $validationExceptions = [];

        foreach ($fieldDefintions as $fd) {
            try {
                $getter = 'get' . ucfirst($fd->getName());

                if (method_exists($this, $getter)) {
                    $value = $this->$getter();
                    $omitMandatoryCheck = $this->getOmitMandatoryCheck();

                    //check throws Exception
                    try {
                        $fd->checkValidity($value, $omitMandatoryCheck);
                    } catch (\Exception $e) {
                        if ($this->getClass()->getAllowInherit()) {
                            //try again with parent data when inheritance is activated
                            try {
                                $getInheritedValues = AbstractObject::doGetInheritedValues();
                                AbstractObject::setGetInheritedValues(true);

                                $value = $this->$getter();
                                $fd->checkValidity($value, $omitMandatoryCheck);

                                AbstractObject::setGetInheritedValues($getInheritedValues);
                            } catch (\Exception $e) {
                                if (!$e instanceof Model\Element\ValidationException) {
                                    throw $e;
                                }
                                $exceptionClass = get_class($e);
                                $newException = new $exceptionClass($e->getMessage() . ' fieldname=' . $fd->getName(), $e->getCode(), $e->getPrevious());
                                $newException->setSubItems($e->getSubItems());
                                throw $newException;
                            }
                        } else {
                            if ($e instanceof Model\Element\ValidationException) {
                                throw $e;
                            }
                            $exceptionClass = get_class($e);
                            throw new $exceptionClass($e->getMessage() . ' fieldname=' . $fd->getName(), $e->getCode(), $e);
                        }
                    }
                }
            } catch (Model\Element\ValidationException $ve) {
                $validationExceptions[] = $ve;
            }
        }

        if ($validationExceptions) {
            $message = 'Validation failed: ';
            $errors = [];
            /** @var \Exception $e */
            foreach ($validationExceptions as $e) {
                $msg = $e->getMessage();

                if ($e instanceof Model\Element\ValidationException) {
                    $subItems = $e->getSubItems();
                    if (is_array($subItems) && count($subItems)) {
                        $msg .= ' (';
                        $subItemParts = [];
                        /** @var \Exception $subItem */
                        foreach ($subItems as $subItem) {
                            $subItemMessage = $subItem->getMessage();
                            if ($subItem instanceof Model\Element\ValidationException) {
                                $subItemMessage .= '[ ' . $subItem->getContextStack()[0] . ' ]';
                            }
                            $subItemParts[] = $subItemMessage;
                        }
                        $msg .= implode(', ', $subItemParts);
                        $msg .= ')';
                    }
                }
                $errors[] = $msg;
            }
            $message .= implode(' / ', $errors);
            $aggregatedExceptions = new Model\Element\ValidationException($message);
            $aggregatedExceptions->setSubItems($validationExceptions);
            throw $aggregatedExceptions;
        }

        $isDirtyDetectionDisabled = self::isDirtyDetectionDisabled();
        try {
            $oldVersionCount = $this->getVersionCount();

            parent::update($isUpdate, $params);

            $newVersionCount = $this->getVersionCount();

            if (($newVersionCount != $oldVersionCount + 1) || ($this instanceof DirtyIndicatorInterface && $this->isFieldDirty('o_parentId'))) {
                self::disableDirtyDetection();
            }

            $this->getDao()->update($isUpdate);

            // scheduled tasks are saved in $this->saveVersion();

            $this->saveVersion(false, false, isset($params['versionNote']) ? $params['versionNote'] : null);
            $this->saveChildData();
        } finally {
            self::setDisableDirtyDetection($isDirtyDetectionDisabled);
        }
    }

    protected function saveChildData()
    {
        if ($this->getClass()->getAllowInherit()) {
            $this->getDao()->saveChildData();
        }
    }

    public function saveScheduledTasks()
    {
        // update scheduled tasks
        $this->getScheduledTasks();
        $this->getDao()->deleteAllTasks();

        if (is_array($this->getScheduledTasks()) && count($this->getScheduledTasks()) > 0) {
            foreach ($this->getScheduledTasks() as $task) {
                $task->setId(null);
                $task->setDao(null);
                $task->setCid($this->getId());
                $task->setCtype('object');
                $task->save();
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function doDelete()
    {
        // delete all versions
        foreach ($this->getVersions() as $v) {
            $v->delete();
        }

        $this->getDao()->deleteAllTasks();

        parent::doDelete();
    }

    /**
     * $callPluginHook is true when the method is called from outside (eg. directly in the controller "save only version")
     * it is false when the method is called by $this->update()
     *
     * @param bool $setModificationDate
     * @param bool $saveOnlyVersion
     * @param string $versionNote version note
     *
     * @return Model\Version
     */
    public function saveVersion($setModificationDate = true, $saveOnlyVersion = true, $versionNote = null)
    {
        try {
            if ($setModificationDate) {
                $this->setModificationDate(time());
            }

            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::PRE_UPDATE, new DataObjectEvent($this, [
                    'saveVersionOnly' => true,
                ]));
            }

            // scheduled tasks are saved always, they are not versioned!
            $this->saveScheduledTasks();

            $version = null;

            // only create a new version if there is at least 1 allowed
            // or if saveVersion() was called directly (it's a newer version of the object)
            $objectsConfig = \Pimcore\Config::getSystemConfiguration('objects');
            if (!empty($objectsConfig['versions']['steps'])
                || !empty($objectsConfig['versions']['days'])
                || $setModificationDate) {
                $saveStackTrace = !($objectsConfig['versions']['disable_stack_trace'] ?? false);
                $version = $this->doSaveVersion($versionNote, $saveOnlyVersion, $saveStackTrace);
            }

            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_UPDATE, new DataObjectEvent($this, [
                    'saveVersionOnly' => true,
                ]));
            }

            return $version;
        } catch (\Exception $e) {
            \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_UPDATE_FAILURE, new DataObjectEvent($this, [
                'saveVersionOnly' => true,
                'exception' => $e,
            ]));

            throw $e;
        }
    }

    /**
     * @return Model\Version[]
     */
    public function getVersions()
    {
        if ($this->o_versions === null) {
            $this->setVersions($this->getDao()->getVersions());
        }

        return $this->o_versions;
    }

    /**
     * @param Model\Version[] $o_versions
     *
     * @return $this
     */
    public function setVersions($o_versions)
    {
        $this->o_versions = $o_versions;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getValueForFieldName($key)
    {
        if (isset($this->$key)) {
            return $this->$key;
        }

        if ($this->getClass()->getFieldDefinition($key) instanceof Model\DataObject\ClassDefinition\Data\CalculatedValue) {
            $value = new Model\DataObject\Data\CalculatedValue($key);
            $value = Service::getCalculatedFieldValue($this, $value);

            return $value;
        }

        return null;
    }

    /**
     * @param array $tags
     *
     * @return array
     */
    public function getCacheTags($tags = [])
    {
        $tags = is_array($tags) ? $tags : [];

        $tags = parent::getCacheTags($tags);

        $tags['class_' . $this->getClassId()] = 'class_' . $this->getClassId();
        foreach ($this->getClass()->getFieldDefinitions() as $name => $def) {
            // no need to add lazy-loading fields to the cache tags
            if ((!method_exists($def, 'getLazyLoading') && !$def instanceof LazyLoadingSupportInterface) || !$def->getLazyLoading()) {
                $tags = $def->getCacheTags($this->getValueForFieldName($name), $tags);
            }
        }

        return $tags;
    }

    /**
     * @return array
     */
    public function resolveDependencies()
    {
        $dependencies = [parent::resolveDependencies()];

        // check in fields
        if ($this->getClass() instanceof ClassDefinition) {
            foreach ($this->getClass()->getFieldDefinitions() as $field) {
                $key = $field->getName();
                $dependencies[] = $field->resolveDependencies(
                    isset($this->$key) ? $this->$key : null
                );
            }
        }

        $dependencies = array_merge(...$dependencies);

        return $dependencies;
    }

    /**
     * @param ClassDefinition $o_class
     *
     * @return self
     */
    public function setClass($o_class)
    {
        $this->o_class = $o_class;

        return $this;
    }

    /**
     * @return ClassDefinition
     */
    public function getClass()
    {
        if (!$this->o_class) {
            $this->setClass(ClassDefinition::getById($this->getClassId()));
        }

        return $this->o_class;
    }

    /**
     * @return string
     */
    public function getClassId()
    {
        return $this->o_classId;
    }

    /**
     * @param string $o_classId
     *
     * @return $this
     */
    public function setClassId($o_classId)
    {
        $this->o_classId = $o_classId;

        return $this;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->o_className;
    }

    /**
     * @param string $o_className
     *
     * @return $this
     */
    public function setClassName($o_className)
    {
        $this->o_className = $o_className;

        return $this;
    }

    /**
     * @return bool
     */
    public function getPublished()
    {
        return (bool) $this->o_published;
    }

    /**
     * @return bool
     */
    public function isPublished()
    {
        return (bool) $this->getPublished();
    }

    /**
     * @param bool $o_published
     *
     * @return $this
     */
    public function setPublished($o_published)
    {
        $this->o_published = (bool) $o_published;

        return $this;
    }

    /**
     * @param bool $omitMandatoryCheck
     *
     * @return self
     */
    public function setOmitMandatoryCheck($omitMandatoryCheck)
    {
        $this->omitMandatoryCheck = $omitMandatoryCheck;

        return $this;
    }

    /**
     * @return bool
     */
    public function getOmitMandatoryCheck()
    {
        if ($this->omitMandatoryCheck === null) {
            return !$this->isPublished();
        }

        return $this->omitMandatoryCheck;
    }

    /**
     * @return Model\Schedule\Task[]
     */
    public function getScheduledTasks()
    {
        if ($this->scheduledTasks === null) {
            $taskList = new Model\Schedule\Task\Listing();
            $taskList->setCondition("cid = ? AND ctype='object'", $this->getId());
            $this->scheduledTasks = $taskList->load();
        }

        return $this->scheduledTasks;
    }

    /**
     * @param array $scheduledTasks
     *
     * @return self
     */
    public function setScheduledTasks($scheduledTasks)
    {
        $this->scheduledTasks = $scheduledTasks;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $params
     *
     * @return mixed
     *
     * @throws InheritanceParentNotFoundException
     */
    public function getValueFromParent($key, $params = null)
    {
        $parent = $this->getNextParentForInheritance();
        if ($parent) {
            $method = 'get' . $key;
            if (method_exists($parent, $method)) {
                return $parent->$method($params);
            }

            throw new InheritanceParentNotFoundException(sprintf('Parent object does not have a method called `%s()`, unable to retrieve value for key `%s`', $method, $key));
        }

        throw new InheritanceParentNotFoundException('No parent object available to get a value from');
    }

    /**
     * @return AbstractObject|null
     */
    public function getNextParentForInheritance()
    {
        return $this->getClosestParentOfClass($this->getClassId());
    }

    /**
     * @param string $classId
     *
     * @return Concrete|null
     */
    public function getClosestParentOfClass(string $classId)
    {
        $parent = $this->getParent();
        if ($parent instanceof AbstractObject) {
            while ($parent && (!$parent instanceof Concrete || $parent->getClassId() !== $classId)) {
                $parent = $parent->getParent();
            }

            if ($parent && in_array($parent->getType(), [self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_VARIANT], true)) {
                /** @var Concrete $parent */
                if ($parent->getClassId() === $classId) {
                    return $parent;
                }
            }
        }

        return null;
    }

    /**
     * get object relation data as array for a specific field
     *
     * @param string $fieldName
     * @param bool $forOwner
     * @param string $remoteClassId
     *
     * @return array
     */
    public function getRelationData($fieldName, $forOwner, $remoteClassId)
    {
        $relationData = $this->getDao()->getRelationData($fieldName, $forOwner, $remoteClassId);

        return $relationData;
    }

    /**
     * @param string $method
     * @param array $arguments
     *
     * @return Model\Listing\AbstractListing|Concrete|null
     *
     * @throws \Exception
     */
    public static function __callStatic($method, $arguments)
    {
        // check for custom static getters like DataObject::getByMyfield()
        $propertyName = lcfirst(preg_replace('/^getBy/i', '', $method));
        $classDefinition = ClassDefinition::getById(self::classId());

        // get real fieldname (case sensitive)
        $fieldnames = [];
        $defaultCondition = '';
        foreach ($classDefinition->getFieldDefinitions() as $fd) {
            $fieldnames[] = $fd->getName();
        }
        $realPropertyName = implode('', preg_grep('/^' . preg_quote($propertyName, '/') . '$/i', $fieldnames));

        if (!$classDefinition->getFieldDefinition($realPropertyName) instanceof Model\DataObject\ClassDefinition\Data) {
            $localizedField = $classDefinition->getFieldDefinition('localizedfields');
            if ($localizedField instanceof Model\DataObject\ClassDefinition\Data\Localizedfields) {
                $fieldnames = [];
                foreach ($localizedField->getFieldDefinitions() as $fd) {
                    $fieldnames[] = $fd->getName();
                }
                $realPropertyName = implode('', preg_grep('/^' . preg_quote($propertyName, '/') . '$/i', $fieldnames));
                $localizedFieldDefinition = $localizedField->getFieldDefinition($realPropertyName);
                if ($localizedFieldDefinition instanceof Model\DataObject\ClassDefinition\Data) {
                    $realPropertyName = 'localizedfields';
                    \array_unshift($arguments, $localizedFieldDefinition->getName());
                }
            }
        }

        if ($classDefinition->getFieldDefinition($realPropertyName) instanceof Model\DataObject\ClassDefinition\Data) {
            $field = $classDefinition->getFieldDefinition($realPropertyName);
            if (!$field->isFilterable()) {
                throw new \Exception("Static getter '::getBy".ucfirst($realPropertyName)."' is not allowed for fieldtype '" . $field->getFieldType() . "'");
            }

            if ($field instanceof Model\DataObject\ClassDefinition\Data\Localizedfields) {
                $arguments = array_pad($arguments, 5, 0);

                [$localizedPropertyName, $value, $locale, $limit, $offset] = $arguments;

                $localizedField = $field->getFieldDefinition($localizedPropertyName);

                if (!$localizedField instanceof Model\DataObject\ClassDefinition\Data) {
                    Logger::error('Class: DataObject\\Concrete => call to undefined static method ' . $method);
                    throw new \Exception('Call to undefined static method ' . $method . ' in class DataObject\\Concrete');
                }

                if (!$localizedField->isFilterable()) {
                    throw new \Exception("Static getter '::getBy".ucfirst($realPropertyName)."' is not allowed for fieldtype '" . $localizedField->getFieldType() . "'");
                }

                $defaultCondition = $localizedPropertyName . ' = ' . Db::get()->quote($value) . ' ';
                $listConfig = [
                    'condition' => $defaultCondition,
                ];

                if ($locale) {
                    $listConfig['locale'] = $locale;
                }
            } else {
                $arguments = array_pad($arguments, 3, 0);
                [$value, $limit, $offset] = $arguments;

                if (!$field instanceof AbstractRelations) {
                    $defaultCondition = $realPropertyName . ' = ' . Db::get()->quote($value) . ' ';
                }
                $listConfig = [
                    'condition' => $defaultCondition,
                ];
            }

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
                $listConfig['condition'] = $defaultCondition . $limitCondition;
            }

            $list = static::getList($listConfig);

            if ($field instanceof AbstractRelations && $field->isFilterable()) {
                $list = $field->addListingFilter($list, $value);
            }

            if (isset($listConfig['limit']) && $listConfig['limit'] == 1) {
                $elements = $list->getObjects();

                return isset($elements[0]) ? $elements[0] : null;
            }

            return $list;
        }

        // there is no property for the called method, so throw an exception
        Logger::error('Class: DataObject\\Concrete => call to undefined static method ' . $method);
        throw new \Exception('Call to undefined static method ' . $method . ' in class DataObject\\Concrete');
    }

    /**
     * @return $this
     *
     * @throws \Exception
     */
    public function save()
    {
        $isDirtyDetectionDisabled = AbstractObject::isDirtyDetectionDisabled();

        // if the class is newer then better disable the dirty detection. This should fix issues with the query table if
        // the inheritance enabled flag has been changed in the meantime
        if ($this->getClass()->getModificationDate() >= $this->getModificationDate() && $this->getId()) {
            AbstractObject::disableDirtyDetection();
        }
        try {
            $params = [];
            if (func_num_args() && is_array(func_get_arg(0))) {
                $params = func_get_arg(0);
            }

            parent::save($params);
            if ($this instanceof DirtyIndicatorInterface) {
                $this->resetDirtyMap();
            }
        } finally {
            AbstractObject::setDisableDirtyDetection($isDirtyDetectionDisabled);
        }

        return $this;
    }

    /**
     * @internal
     * @inheritdoc
     */
    public function getLazyLoadedFieldNames(): array
    {
        $lazyLoadedFieldNames = [];
        $fields = $this->getClass()->getFieldDefinitions(['suppressEnrichment' => true]);
        foreach ($fields as $field) {
            if (($field instanceof LazyLoadingSupportInterface || method_exists($field, 'getLazyLoading'))
                                && $field->getLazyLoading()) {
                $lazyLoadedFieldNames[] = $field->getName();
            }
        }

        return $lazyLoadedFieldNames;
    }

    /**
     * @inheritDoc
     */
    public function isAllLazyKeysMarkedAsLoaded(): bool
    {
        if (!$this->getId()) {
            return true;
        }

        return $this->allLazyKeysMarkedAsLoaded;
    }

    public function markAllLazyLoadedKeysAsLoaded()
    {
        $this->allLazyKeysMarkedAsLoaded = true;
    }

    public function __sleep()
    {
        $parentVars = parent::__sleep();

        $finalVars = [];

        $blockedVars = [];

        if (!$this->isInDumpState()) {
            $blockedVars = ['loadedLazyKeys', 'allLazyKeysMarkedAsLoaded'];
            // do not dump lazy loaded fields for caching
            $lazyLoadedFields = $this->getLazyLoadedFieldNames();
            $blockedVars = array_merge($lazyLoadedFields, $blockedVars);
        }

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    public function __wakeup()
    {
        parent::__wakeup();

        // renew localized fields
        // do not use the getter ($this->getLocalizedfields()) as it somehow slows down the process around a sec
        // no clue why this happens
        if (property_exists($this, 'localizedfields') && $this->localizedfields instanceof Localizedfield) {
            $this->localizedfields->setObject($this, false);
        }
    }

    /**
     * load lazy loaded fields before cloning
     */
    public function __clone()
    {
        parent::__clone();
        $this->o_class = null;
        $this->o_versions = null;
        $this->scheduledTasks = null;
    }

    /**
     * @internal
     *
     * @param int $objectId
     * @param int $modificationDate
     * @param int $versionCount
     * @param bool $force
     *
     * @return Model\Version|void
     */
    public static function getLatestVersionByObjectIdAndLatestModificationDate($objectId, $modificationDate, $versionCount, $force = false)
    {
        $db = Db::get();
        $versionData = $db->fetchRow("SELECT id,date,versionCount FROM versions WHERE cid = ? AND ctype='object' ORDER BY `versionCount` DESC, `id` DESC LIMIT 1", $objectId);

        if (!empty($versionData['id']) && ($versionData['date'] > $modificationDate || $versionData['versionCount'] > $versionCount || $force)) {
            $version = Model\Version::getById($versionData['id']);

            return $version;
        }

        return;
    }

    /**
     * @internal
     *
     * @param array $descriptor
     * @param string $table
     *
     * @return array
     */
    protected function doRetrieveData(array $descriptor, string $table)
    {
        $db = Db::get();
        $conditionParts = Service::buildConditionPartsFromDescriptor($descriptor);

        $query = 'SELECT * FROM ' . $table . ' WHERE ' . implode(' AND ', $conditionParts);
        $result = $db->fetchAll($query);

        return $result;
    }

    /**
     * @internal
     *
     * @param array $descriptor
     *
     * @return array
     */
    public function retrieveSlugData($descriptor)
    {
        $descriptor['objectId'] = $this->getId();

        return $this->doRetrieveData($descriptor, 'object_url_slugs');
    }

    /**
     * @internal
     *
     * @param array $descriptor
     *
     * @return array
     */
    public function retrieveRelationData($descriptor)
    {
        $descriptor['src_id'] = $this->getId();

        $unfilteredData = $this->__getRawRelationData();

        $likes = [];
        foreach ($descriptor as $column => $expectedValue) {
            if (is_string($expectedValue)) {
                $trimmed = rtrim($expectedValue, '%');
                if (strlen($trimmed) < strlen($expectedValue)) {
                    $likes[$column] = $trimmed;
                }
            }
        }

        $filterFn = static function ($row) use ($descriptor, $likes) {
            foreach ($descriptor as $column => $expectedValue) {
                $actualValue = $row[$column];
                if (isset($likes[$column])) {
                    $expectedValue = $likes[$column];
                    if (strpos($actualValue, $expectedValue) !== 0) {
                        return false;
                    }
                } elseif ($actualValue != $expectedValue) {
                    return false;
                }
            }

            return true;
        };

        $filteredData = array_filter($unfilteredData, $filterFn);

        return $filteredData;
    }

    /**
     * @internal
     *
     * @return array
     */
    public function __getRawRelationData(): array
    {
        if ($this->__rawRelationData === null) {
            $db = Db::get();
            $relations = $db->fetchAll('SELECT * FROM object_relations_' . $this->getClassId() . ' WHERE src_id = ?', [$this->getId()]);
            $this->__rawRelationData = $relations ?? [];
        }

        return $this->__rawRelationData;
    }
}
