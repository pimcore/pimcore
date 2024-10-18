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

use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Db;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Messenger\VersionDeleteMessage;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\LazyLoadingSupportInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Link;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\Element\DirtyIndicatorInterface;
use Pimcore\SystemSettingsConfig;

/**
 * @method Model\DataObject\Concrete\Dao getDao()
 * @method Model\Version|null getLatestVersion(?int $userId = null, bool $includingPublished = false)
 */
class Concrete extends DataObject implements LazyLoadedFieldsInterface
{
    use Model\DataObject\Traits\LazyLoadedRelationTrait;
    use Model\Element\Traits\ScheduledTasksTrait;

    /**
     * @internal
     *
     * Necessary for assigning object reference to corresponding fields while wakeup
     *
     */
    public array $__objectAwareFields = [];

    /**
     * @internal
     *
     * @var array
     */
    public const SYSTEM_COLUMN_NAMES = ['id', 'fullpath', 'key', 'published', 'creationDate', 'modificationDate', 'filename', 'classname', 'index'];

    /**
     * @internal
     *
     */
    protected bool $published = false;

    /**
     * @internal
     */
    protected ?ClassDefinition $class = null;

    /**
     * @internal
     *
     * @var string|null
     */
    protected $className = null;

    /**
     * @internal
     *
     */
    protected ?array $versions = null;

    /**
     * @internal
     *
     */
    protected ?bool $omitMandatoryCheck = null;

    /**
     * @internal
     *
     */
    protected bool $allLazyKeysMarkedAsLoaded = false;

    /**
     * returns the class ID of the current object class
     *
     */
    public static function classId(): string
    {
        $v = get_class_vars(get_called_class());

        return $v['classId'];
    }

    protected function update(bool $isUpdate = null, array $params = []): void
    {
        $fieldDefinitions = $this->getClass()->getFieldDefinitions();

        $validationExceptions = [];

        foreach ($fieldDefinitions as $fd) {
            try {
                if ($fd instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $this->__objectAwareFields['localizedfields'] = true;
                }

                $getter = 'get' . ucfirst($fd->getName());

                if (method_exists($this, $getter)) {
                    $value = $this->$getter();

                    $omitMandatoryCheck = $this->getOmitMandatoryCheck();
                    // when adding a new object, skip check on mandatory fields with default value
                    if (empty($value) && !$isUpdate && method_exists($fd, 'getDefaultValue') && !empty($fd->getDefaultValue())
                    ) {
                        $omitMandatoryCheck = true;
                    }

                    //check throws Exception
                    try {
                        if ($fd instanceof Link) {
                            $params['resetInvalidFields'] = true;
                        }
                        $fd->checkValidity($value, $omitMandatoryCheck, $params);
                    } catch (Exception $e) {
                        if ($this->getClass()->getAllowInherit() && $fd->supportsInheritance() && $fd->isEmpty($value)) {
                            //try again with parent data when inheritance is activated
                            try {
                                $getInheritedValues = DataObject::doGetInheritedValues();
                                DataObject::setGetInheritedValues(true);

                                $value = $this->$getter();
                                $fd->checkValidity($value, $omitMandatoryCheck, $params);

                                DataObject::setGetInheritedValues($getInheritedValues);
                            } catch (Exception $e) {
                                if (!$e instanceof Model\Element\ValidationException) {
                                    throw $e;
                                }
                                $exceptionClass = get_class($e);
                                $newException = new $exceptionClass($e->getMessage() . ' fieldname=' . $fd->getName(), $e->getCode(), $e->getPrevious());
                                $newException->setSubItems($e->getSubItems());

                                throw $newException;
                            }
                        } else {
                            throw $e;
                        }
                    }
                }
            } catch (Model\Element\ValidationException $ve) {
                $validationExceptions[] = $ve;
            }
        }

        $preUpdateEvent = new DataObjectEvent($this, [
            'validationExceptions' => $validationExceptions,
            'message' => 'Validation failed: ',
            'separator' => ' / ',
        ]);
        Pimcore::getEventDispatcher()->dispatch($preUpdateEvent, DataObjectEvents::PRE_UPDATE_VALIDATION_EXCEPTION);
        $validationExceptions = $preUpdateEvent->getArgument('validationExceptions');

        if ($validationExceptions) {
            $message = $preUpdateEvent->getArgument('message');
            $errors = [];

            /** @var Model\Element\ValidationException $e */
            foreach ($validationExceptions as $e) {
                $errors[] = $e->getAggregatedMessage();
            }
            $message .= implode($preUpdateEvent->getArgument('separator'), $errors);

            throw new Model\Element\ValidationException($message);
        }

        $isDirtyDetectionDisabled = self::isDirtyDetectionDisabled();

        try {
            $oldVersionCount = $this->getVersionCount();

            parent::update($isUpdate, $params);

            $newVersionCount = $this->getVersionCount();

            if (($newVersionCount != $oldVersionCount + 1) || ($this instanceof DirtyIndicatorInterface && $this->isFieldDirty('parentId'))) {
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

    private function saveChildData(): void
    {
        if ($this->getClass()->getAllowInherit()) {
            $this->getDao()->saveChildData();
        }
    }

    protected function doDelete(): void
    {
        // Dispatch Symfony Message Bus to delete versions
        Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
            new VersionDeleteMessage(Model\Element\Service::getElementType($this), $this->getId())
        );

        $this->getDao()->deleteAllTasks();

        parent::doDelete();
    }

    /**
     * $callPluginHook is true when the method is called from outside (eg. directly in the controller "save only version")
     * it is false when the method is called by $this->update()
     *
     * @param string|null $versionNote version note
     *
     */
    public function saveVersion(bool $setModificationDate = true, bool $saveOnlyVersion = true, string $versionNote = null, bool $isAutoSave = false): ?Model\Version
    {
        try {
            if ($setModificationDate) {
                $this->setModificationDate(time());
            }

            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                $preUpdateEvent = new DataObjectEvent($this, [
                    'saveVersionOnly' => true,
                    'isAutoSave' => $isAutoSave,
                ]);
                Pimcore::getEventDispatcher()->dispatch($preUpdateEvent, DataObjectEvents::PRE_UPDATE);
            }

            // scheduled tasks are saved always, they are not versioned!
            $this->saveScheduledTasks();

            $version = null;

            // only create a new version if there is at least 1 allowed
            // or if saveVersion() was called directly (it's a newer version of the object)
            $objectsConfig = SystemSettingsConfig::get()['objects'];
            if ((is_null($objectsConfig['versions']['days'] ?? null) && is_null($objectsConfig['versions']['steps'] ?? null))
                || (!empty($objectsConfig['versions']['steps']))
                || !empty($objectsConfig['versions']['days'])
                || $setModificationDate) {
                $saveStackTrace = !($objectsConfig['versions']['disable_stack_trace'] ?? false);
                $version = $this->doSaveVersion($versionNote, $saveOnlyVersion, $saveStackTrace, $isAutoSave);
            }

            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                $postUpdateEvent = new DataObjectEvent($this, [
                    'saveVersionOnly' => true,
                    'isAutoSave' => $isAutoSave,
                ]);
                Pimcore::getEventDispatcher()->dispatch($postUpdateEvent, DataObjectEvents::POST_UPDATE);
            }

            return $version;
        } catch (Exception $e) {
            $postUpdateFailureEvent = new DataObjectEvent($this, [
                'saveVersionOnly' => true,
                'exception' => $e,
                'isAutoSave' => $isAutoSave,
            ]);
            Pimcore::getEventDispatcher()->dispatch($postUpdateFailureEvent, DataObjectEvents::POST_UPDATE_FAILURE);

            throw $e;
        }
    }

    /**
     * @return Model\Version[]
     */
    public function getVersions(): array
    {
        if ($this->versions === null) {
            $this->setVersions($this->getDao()->getVersions());
        }

        return $this->versions;
    }

    /**
     * @param Model\Version[] $versions
     *
     * @return $this
     */
    public function setVersions(array $versions): static
    {
        $this->versions = $versions;

        return $this;
    }

    public function getValueForFieldName(string $key): mixed
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

    public function getCacheTags(array $tags = []): array
    {
        $tags = parent::getCacheTags($tags);

        $tags['class_' . $this->getClassId()] = 'class_' . $this->getClassId();
        foreach ($this->getClass()->getFieldDefinitions() as $name => $def) {
            // no need to add lazy-loading fields to the cache tags
            if (!$def instanceof LazyLoadingSupportInterface || !$def->getLazyLoading()) {
                $tags = $def->getCacheTags($this->getValueForFieldName($name), $tags);
            }
        }

        return $tags;
    }

    public function resolveDependencies(): array
    {
        $dependencies = [parent::resolveDependencies()];

        // check in fields
        if ($this->getClass() instanceof ClassDefinition) {
            foreach ($this->getClass()->getFieldDefinitions() as $field) {
                $key = $field->getName();
                $getter = 'get' . ucfirst($key);
                $dependencies[] = $field->resolveDependencies($this->$getter());
            }
        }

        return array_merge(...$dependencies);
    }

    /**
     * @return $this
     */
    public function setClass(?ClassDefinition $class): static
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function getClass(): ClassDefinition
    {
        if (!$this->class) {
            $class = ClassDefinition::getById($this->getClassId());
            if (!$class instanceof ClassDefinition) {
                throw new Model\Exception\NotFoundException('class not found for object id: ' . $this->getId());
            }

            $this->setClass($class);
        }

        return $this->class;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * @return $this
     */
    public function setClassName(string $className): static
    {
        $this->className = $className;

        return $this;
    }

    public function getPublished(): bool
    {
        return $this->published;
    }

    public function isPublished(): bool
    {
        return $this->getPublished();
    }

    /**
     * @return $this
     */
    public function setPublished(bool $published): static
    {
        $this->markFieldDirty('published');
        $this->published = $published;

        return $this;
    }

    public function setOmitMandatoryCheck(bool $omitMandatoryCheck): static
    {
        $this->omitMandatoryCheck = $omitMandatoryCheck;

        return $this;
    }

    public function getOmitMandatoryCheck(): bool
    {
        if ($this->omitMandatoryCheck === null) {
            return !$this->isPublished();
        }

        return $this->omitMandatoryCheck;
    }

    /**
     *
     * @throws InheritanceParentNotFoundException
     */
    public function getValueFromParent(string $key, mixed $params = null): mixed
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
     * @internal
     *
     */
    public function getNextParentForInheritance(): ?Concrete
    {
        return $this->getClosestParentOfClass($this->getClassId());
    }

    public function getClosestParentOfClass(string $classId): ?self
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
     *
     */
    public function getRelationData(string $fieldName, bool $forOwner, ?string $remoteClassId = null): array
    {
        $relationData = $this->getDao()->getRelationData($fieldName, $forOwner, $remoteClassId);

        return $relationData;
    }

    /**
     *
     * @return Model\Listing\AbstractListing|Concrete|null
     *
     * @throws Exception
     */
    public static function __callStatic(string $method, array $arguments)
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
                    array_unshift($arguments, $localizedFieldDefinition->getName());
                }
            }
        }

        if ($classDefinition->getFieldDefinition($realPropertyName) instanceof Model\DataObject\ClassDefinition\Data) {
            $field = $classDefinition->getFieldDefinition($realPropertyName);
            if (!$field->isFilterable()) {
                throw new Exception("Static getter '::getBy".ucfirst($realPropertyName)."' is not allowed for fieldtype '" . $field->getFieldType() . "'");
            }

            $db = Db::get();

            if ($field instanceof Model\DataObject\ClassDefinition\Data\Localizedfields) {
                $localizedPropertyName = empty($arguments[0]) ? throw new InvalidArgumentException('Mandatory argument $field not set.') : $arguments[0];
                $value = array_key_exists(1, $arguments) ? $arguments[1] : throw new InvalidArgumentException('Mandatory argument $value not set.');
                $locale = $arguments[2] ?? null;
                $limit = $arguments[3] ?? null;
                $offset = $arguments[4] ?? 0;
                $objectTypes = $arguments[5] ?? null;

                $localizedField = $field->getFieldDefinition($localizedPropertyName);

                if (!$localizedField instanceof Model\DataObject\ClassDefinition\Data) {
                    Logger::error('Class: DataObject\\Concrete => call to undefined static method ' . $method);

                    throw new Exception('Call to undefined static method ' . $method . ' in class DataObject\\Concrete');
                }

                if (!$localizedField->isFilterable()) {
                    throw new Exception("Static getter '::getBy".ucfirst($realPropertyName)."' is not allowed for fieldtype '" . $localizedField->getFieldType() . "'");
                }

                $defaultCondition = $db->quoteIdentifier($localizedPropertyName) . ' = ' . $db->quote($value) . ' ';
                $listConfig = [
                    'condition' => $defaultCondition,
                ];

                $listConfig['locale'] = $locale;
            } else {
                $value = array_key_exists(0, $arguments) ? $arguments[0] : throw new InvalidArgumentException('Mandatory argument $value not set.');
                $limit = $arguments[1] ?? null;
                $offset = $arguments[2] ?? 0;
                $objectTypes = $arguments[3] ?? null;

                if (!$field instanceof AbstractRelations) {
                    $defaultCondition = $db->quoteIdentifier($realPropertyName) . ' = ' . $db->quote($value) . ' ';
                }
                $listConfig = [
                    'condition' => $defaultCondition,
                ];
            }

            if (!is_array($limit)) {
                $listConfig['limit'] = $limit;
                $listConfig['offset'] = $offset;
            } else {
                $listConfig = array_merge($listConfig, $limit);
                $limitCondition = $limit['condition'] ?? '';
                $listConfig['condition'] = $defaultCondition . $limitCondition;
            }

            $list = static::makeList($listConfig, $objectTypes);

            if ($field instanceof AbstractRelations) {
                $list = $field->addListingFilter($list, $value);
            }

            if (isset($listConfig['limit']) && $listConfig['limit'] == 1) {
                $elements = $list->getObjects();

                return isset($elements[0]) ? $elements[0] : null;
            }

            return $list;
        }

        try {
            return call_user_func_array([parent::class, $method], $arguments);
        } catch (Exception $e) {
            // there is no property for the called method, so throw an exception
            Logger::error('Class: DataObject\\Concrete => call to undefined static method '.$method);

            throw new Exception('Call to undefined static method '.$method.' in class DataObject\\Concrete');
        }
    }

    /**
     * @throws Exception
     */
    public function save(array $parameters = []): static
    {
        $isDirtyDetectionDisabled = DataObject::isDirtyDetectionDisabled();

        // if the class is newer then better disable the dirty detection. This should fix issues with the query table if
        // the inheritance enabled flag has been changed in the meantime
        if ($this->getClass()->getModificationDate() >= $this->getModificationDate() && $this->getId()) {
            DataObject::disableDirtyDetection();
        } elseif ($this->getClass()->getAllowInherit() && $this->isFieldDirty('parentId')) {
            // if inherit is enabled and the data object is moved the query table should be updated
            DataObject::disableDirtyDetection();
        }

        try {
            parent::save($parameters);

            if ($this instanceof DirtyIndicatorInterface) {
                $this->resetDirtyMap();
            }
        } finally {
            DataObject::setDisableDirtyDetection($isDirtyDetectionDisabled);
        }

        return $this;
    }

    /**
     * @internal
     *
     */
    public function getLazyLoadedFieldNames(): array
    {
        $lazyLoadedFieldNames = [];
        $fields = $this->getClass()->getFieldDefinitions(['suppressEnrichment' => true]);
        foreach ($fields as $field) {
            if ($field instanceof LazyLoadingSupportInterface
                && $field->getLazyLoading()
                && $field instanceof DataObject\ClassDefinition\Data) {
                $lazyLoadedFieldNames[] = $field->getName();
            }
        }

        return $lazyLoadedFieldNames;
    }

    public function isAllLazyKeysMarkedAsLoaded(): bool
    {
        if (!$this->getId()) {
            return true;
        }

        return $this->allLazyKeysMarkedAsLoaded;
    }

    public function markAllLazyLoadedKeysAsLoaded(): void
    {
        $this->allLazyKeysMarkedAsLoaded = true;
    }

    public function __sleep(): array
    {
        $parentVars = parent::__sleep();
        $finalVars = [];
        $blockedVars = ['__rawRelationData'];

        if (!$this->isInDumpState()) {
            $blockedVars = array_merge(['loadedLazyKeys', 'allLazyKeysMarkedAsLoaded'], $blockedVars);
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

    public function __wakeup(): void
    {
        // parent::__wakeup() will call $this->setInDumpState(false) but we'll need the original value below
        $wasInDumpState = $this->isInDumpState();

        parent::__wakeup();

        // Renew object reference to object aware fields
        if ($wasInDumpState) {
            // We're reloading version data, there might be fields that now implement the ObjectAwareFieldInterface but
            // aren't included in the $this->__objectAwareFields array - for example versions created in Pimcore <= 10.x
            // containing LocalizedFields. Verify all fields in this object.
            foreach (get_object_vars($this) as $propertyValue) {
                if ($propertyValue instanceof ObjectAwareFieldInterface) {
                    $propertyValue->setObject($this);
                }
            }
        } else {
            // We're reloading from cache, optimize by only reloading known object aware fields (instead of verifying
            // all fields within this object).
            foreach ($this->__objectAwareFields as $objectAwareField => $exists) {
                if (isset($this->$objectAwareField) && $this->$objectAwareField instanceof ObjectAwareFieldInterface) {
                    $this->$objectAwareField->setObject($this);
                }
            }
        }
    }

    /**
     * load lazy loaded fields before cloning
     */
    public function __clone(): void
    {
        parent::__clone();
        $this->class = null;
        $this->versions = null;
        $this->scheduledTasks = null;
    }

    /**
     * @internal
     *
     *
     */
    protected function doRetrieveData(array $descriptor, string $table): array
    {
        $db = Db::get();
        $conditionParts = Service::buildConditionPartsFromDescriptor($descriptor);

        $query = 'SELECT * FROM ' . $table . ' WHERE ' . implode(' AND ', $conditionParts);
        $result = $db->fetchAllAssociative($query);

        return $result;
    }

    /**
     *
     *
     * @internal
     */
    public function retrieveSlugData(array $descriptor): array
    {
        $descriptor['objectId'] = $this->getId();

        return $this->doRetrieveData($descriptor, DataObject\Data\UrlSlug::TABLE_NAME);
    }

    /**
     *
     *
     * @internal
     */
    public function retrieveRelationData(array $descriptor): array
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
                    if (!str_starts_with($actualValue, $expectedValue)) {
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
}
