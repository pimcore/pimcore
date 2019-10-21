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

use Pimcore\Config;
use Pimcore\Db;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;

/**
 * @method \Pimcore\Model\DataObject\Concrete\Dao getDao()
 * @method \Pimcore\Model\Version getLatestVersion()
 */
class Concrete extends AbstractObject implements LazyLoadedFieldsInterface
{
    use Model\DataObject\Traits\LazyLoadedRelationTrait;

    /**
     * @var array
     */
    public static $systemColumnNames = ['id', 'fullpath', 'key', 'published', 'creationDate', 'modificationDate', 'filename', 'classname'];

    /**
     * @var bool
     */
    protected $o_published;

    /**
     * @var ClassDefinition
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
     * @var array
     */
    protected $o_versions = null;

    /**
     * Contains all scheduled tasks
     *
     * @var array
     */
    protected $scheduledTasks = null;

    /**
     * @var bool
     */
    protected $omitMandatoryCheck = false;

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
     * @param $isUpdate
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
                $setter = 'set' . ucfirst($fd->getName());

                if (method_exists($this, $getter)) {

                    //To make sure, inherited values are not set again
                    $inheritedValues = AbstractObject::doGetInheritedValues();
                    AbstractObject::setGetInheritedValues(false);

                    $value = $this->$getter();

                    if (is_array($value) && ($fd instanceof ClassDefinition\Data\ManyToManyRelation || $fd instanceof ClassDefinition\Data\ManyToManyObjectRelation)) {
                        //don't save relations twice, if multiple assignments not allowed
                        if (!method_exists($fd, 'getAllowMultipleAssignments') || !$fd->getAllowMultipleAssignments()) {
                            $relationItems = [];
                            foreach ($value as $item) {
                                $elementHash = null;
                                if ($item instanceof Model\DataObject\Data\ObjectMetadata || $item instanceof Model\DataObject\Data\ElementMetadata) {
                                    if ($item->getElement() instanceof Model\Element\ElementInterface) {
                                        $elementHash = Model\Element\Service::getElementHash($item->getElement());
                                    }
                                } elseif ($item instanceof Model\Element\ElementInterface) {
                                    $elementHash = Model\Element\Service::getElementHash($item);
                                }

                                if ($elementHash && !isset($relationItems[$elementHash])) {
                                    $relationItems[$elementHash] = $item;
                                }
                            }

                            $value = array_values($relationItems);
                        }
                        $this->$setter($value);
                    }
                    AbstractObject::setGetInheritedValues($inheritedValues);

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
                                if ($e instanceof Model\Element\ValidationException) {
                                    throw $e;
                                }
                                $exceptionClass = get_class($e);
                                throw new $exceptionClass($e->getMessage() . ' fieldname=' . $fd->getName(), $e->getCode(), $e->getPrevious());
                            }
                        } else {
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
            $aggregatedExceptions = new Model\Element\ValidationException();
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
    public function delete(bool $isNested = false)
    {
        $this->beginTransaction();

        try {
            // delete all versions
            foreach ($this->getVersions() as $v) {
                $v->delete();
            }

            $this->getDao()->deleteAllTasks();

            parent::delete(true);

            $this->commit();
        } catch (\Exception $e) {
            $this->rollBack();
            \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_DELETE_FAILURE, new DataObjectEvent($this));
            Logger::crit($e);
            throw $e;
        }
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
                    'saveVersionOnly' => true
                ]));
            }

            // scheduled tasks are saved always, they are not versioned!
            $this->saveScheduledTasks();

            $version = null;

            // only create a new version if there is at least 1 allowed
            // or if saveVersion() was called directly (it's a newer version of the object)
            if (Config::getSystemConfig()->objects->versions->steps
                || Config::getSystemConfig()->objects->versions->days
                || $setModificationDate) {
                $version = $this->doSaveVersion($versionNote, $saveOnlyVersion);
            }

            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_UPDATE, new DataObjectEvent($this, [
                    'saveVersionOnly' => true
                ]));
            }

            return $version;
        } catch (\Exception $e) {
            \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_UPDATE_FAILURE, new DataObjectEvent($this, [
                'saveVersionOnly' => true,
                'exception' => $e
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

        return false;
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
            if (!method_exists($def, 'getLazyLoading') || !$def->getLazyLoading()) {
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
        return $this->omitMandatoryCheck;
    }

    /**
     * @return array
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
     */
    public function setScheduledTasks($scheduledTasks)
    {
        $this->scheduledTasks = $scheduledTasks;

        return $this;
    }

    /**
     * @param $key
     * @param null $params
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
        if ($this->getParent() instanceof AbstractObject) {
            $parent = $this->getParent();
            while ($parent && $parent->getType() === self::OBJECT_TYPE_FOLDER) {
                $parent = $parent->getParent();
            }

            if ($parent && in_array($parent->getType(), [self::OBJECT_TYPE_OBJECT, self::OBJECT_TYPE_VARIANT], true)) {
                if ($parent->getClassId() === $this->getClassId()) {
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
     * @param $remoteClassId
     *
     * @return array
     */
    public function getRelationData($fieldName, $forOwner, $remoteClassId)
    {
        $relationData = $this->getDao()->getRelationData($fieldName, $forOwner, $remoteClassId);

        return $relationData;
    }

    /**
     * @param $method
     * @param $arguments
     *
     * @throws \Exception
     */
    public static function __callStatic($method, $arguments)
    {

        // check for custom static getters like DataObject::getByMyfield()
        $propertyName = lcfirst(preg_replace('/^getBy/i', '', $method));
        $tmpObj = new static();

        // get real fieldname (case sensitive)
        $fieldnames = [];
        foreach ($tmpObj->getClass()->getFieldDefinitions() as $fd) {
            $fieldnames[] = $fd->getName();
        }
        $propertyName = implode('', preg_grep('/^' . preg_quote($propertyName, '/') . '$/i', $fieldnames));

        if (property_exists($tmpObj, $propertyName)) {
            // check if the given fieldtype is valid for this shorthand
            $allowedDataTypes = ['input', 'numeric', 'checkbox', 'country', 'date', 'datetime', 'image', 'language', 'manyToManyRelation', 'multiselect', 'select', 'slider', 'time', 'user', 'email', 'firstname', 'lastname', 'localizedfields'];

            $field = $tmpObj->getClass()->getFieldDefinition($propertyName);
            if (!in_array($field->getFieldType(), $allowedDataTypes, true)) {
                throw new \Exception("Static getter '::getBy".ucfirst($propertyName)."' is not allowed for fieldtype '" . $field->getFieldType() . "', it's only allowed for the following fieldtypes: " . implode(',', $allowedDataTypes));
            }

            if ($field instanceof Model\DataObject\ClassDefinition\Data\Localizedfields) {
                $arguments = array_pad($arguments, 5, 0);

                list($localizedPropertyName, $value, $locale, $limit, $offset) = $arguments;

                $localizedField = $field->getFielddefinition($localizedPropertyName);

                if (!$localizedField instanceof Model\DataObject\ClassDefinition\Data) {
                    Logger::error('Class: DataObject\\Concrete => call to undefined static method ' . $method);
                    throw new \Exception('Call to undefined static method ' . $method . ' in class DataObject\\Concrete');
                }

                if (!in_array($localizedField->getFieldType(), $allowedDataTypes)) {
                    throw new \Exception("Static getter '::getBy".ucfirst($propertyName)."' is not allowed for fieldtype '" . $localizedField->getFieldType() . "', it's only allowed for the following fieldtypes: " . implode(',', $allowedDataTypes));
                }

                $defaultCondition = $localizedPropertyName . ' = ' . Db::get()->quote($value) . ' ';
                $listConfig = [
                    'condition' => $defaultCondition
                ];

                if ($locale) {
                    $listConfig['locale'] = $locale;
                }
            } else {
                $arguments = array_pad($arguments, 3, 0);
                list($value, $limit, $offset) = $arguments;

                $defaultCondition = $propertyName . ' = ' . Db::get()->quote($value) . ' ';
                $listConfig = [
                    'condition' => $defaultCondition
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
                $listConfig['condition'] = $defaultCondition . $limit['condition'];
            }

            $list = static::getList($listConfig);

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
            if (method_exists($field, 'getLazyLoading') && $field->getLazyLoading()) {
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
        $blockedVars = ['loadedLazyKeys', 'allLazyKeysMarkedAsLoaded'];

        if (!isset($this->_fulldump)) {
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
    }

    /**
     * @var bool
     */
    protected static $disableLazyLoading = false;

    /**
     * @internal
     * Disables lazy loading
     */
    public static function disableLazyLoading()
    {
        self::$disableLazyLoading = true;
    }

    /**
     * @internal
     * Enables the lazy loading
     */
    public static function enableLazyloading()
    {
        self::$disableLazyLoading = false;
    }

    /**
     * @internal
     *
     * @return bool
     */
    public static function isLazyLoadingDisabled()
    {
        return self::$disableLazyLoading;
    }

    /**
     * @internal
     *
     * @param $objectId
     * @param $modificationDate
     * @param $versionCount
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
}
