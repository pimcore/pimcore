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

use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\PropertyNameMatcher;
use Pimcore\Cache\RuntimeCache;
use Pimcore\DataObject\GridColumnConfig\ConfigElementInterface;
use Pimcore\DataObject\GridColumnConfig\Operator\AbstractOperator;
use Pimcore\DataObject\GridColumnConfig\Service as GridColumnConfigService;
use Pimcore\Db;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\IdRewriterInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\LayoutDefinitionEnrichmentInterface;
use Pimcore\Model\Element;
use Pimcore\Model\Element\DirtyIndicatorInterface;
use Pimcore\Tool;
use Pimcore\Tool\Admin as AdminTool;
use Pimcore\Tool\Session;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * @method \Pimcore\Model\Element\Dao getDao()
 */
class Service extends Model\Element\Service
{
    /**
     * @var array
     */
    protected $_copyRecursiveIds;

    /**
     * @var Model\User|null
     */
    protected $_user;

    /**
     * System fields used by filter conditions
     *
     * @var array
     */
    protected static $systemFields = ['o_path', 'o_key', 'o_id', 'o_published', 'o_creationDate', 'o_modificationDate', 'o_fullpath'];

    /**
     * @param Model\User $user
     */
    public function __construct($user = null)
    {
        $this->_user = $user;
    }

    /**
     * finds all objects which hold a reference to a specific user
     *
     * @static
     *
     * @param  int $userId
     *
     * @return Concrete[]
     */
    public static function getObjectsReferencingUser($userId)
    {
        $userObjects = [[]];
        $classesList = new ClassDefinition\Listing();
        $classesList->setOrderKey('name');
        $classesList->setOrder('asc');

        $classesToCheck = [];
        foreach ($classesList as $class) {
            $fieldDefinitions = $class->getFieldDefinitions();
            $dataKeys = [];
            if (is_array($fieldDefinitions)) {
                foreach ($fieldDefinitions as $tag) {
                    if ($tag instanceof ClassDefinition\Data\User) {
                        $dataKeys[] = $tag->getName();
                    }
                }
            }
            if (is_array($dataKeys) && count($dataKeys) > 0) {
                $classesToCheck[$class->getName()] = $dataKeys;
            }
        }

        foreach ($classesToCheck as $classname => $fields) {
            $listName = '\\Pimcore\\Model\\DataObject\\' . ucfirst($classname) . '\\Listing';
            $list = new $listName();
            $conditionParts = [];
            foreach ($fields as $field) {
                $conditionParts[] = $field . ' = ?';
            }
            $list->setCondition(implode(' AND ', $conditionParts), array_fill(0, count($conditionParts), $userId));
            $objects = $list->load();
            $userObjects[] = $objects;
        }

        return \array_merge(...$userObjects);
    }

    /**
     * @param AbstractObject $target
     * @param AbstractObject $source
     *
     * @return AbstractObject|void
     */
    public function copyRecursive($target, $source)
    {
        // avoid recursion
        if (!$this->_copyRecursiveIds) {
            $this->_copyRecursiveIds = [];
        }
        if (in_array($source->getId(), $this->_copyRecursiveIds)) {
            return;
        }

        $source->getProperties();
        //load all in case of lazy loading fields
        self::loadAllObjectFields($source);

        // triggers actions before object cloning
        $event = new DataObjectEvent($source, [
            'target_element' => $target,
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, DataObjectEvents::PRE_COPY);
        $target = $event->getArgument('target_element');

        $new = $this->copy($source, $target);

        // add to store
        $this->_copyRecursiveIds[] = $new->getId();

        $children = $source->getChildren([
            DataObject::OBJECT_TYPE_OBJECT,
            DataObject::OBJECT_TYPE_VARIANT,
            DataObject::OBJECT_TYPE_FOLDER,
        ], true);

        foreach ($children as $child) {
            $this->copyRecursive($new, $child);
        }

        $this->updateChildren($target, $new);

        // triggers actions after the complete document cloning
        $event = new DataObjectEvent($new, [
            'base_element' => $source, // the element used to make a copy
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, DataObjectEvents::POST_COPY);

        return $new;
    }

    /**
     * @param  AbstractObject $target
     * @param  AbstractObject $source
     *
     * @return AbstractObject copied object
     */
    public function copyAsChild($target, $source)
    {
        $isDirtyDetectionDisabled = DataObject::isDirtyDetectionDisabled();
        DataObject::setDisableDirtyDetection(true);

        //load properties
        $source->getProperties();

        //load all in case of lazy loading fields
        self::loadAllObjectFields($source);

        // triggers actions before object cloning
        $event = new DataObjectEvent($source, [
            'target_element' => $target,
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, DataObjectEvents::PRE_COPY);
        $target = $event->getArgument('target_element');

        $new = $this->copy($source, $target);

        DataObject::setDisableDirtyDetection($isDirtyDetectionDisabled);

        $this->updateChildren($target, $new);

        // triggers actions after the complete object cloning
        $event = new DataObjectEvent($new, [
            'base_element' => $source, // the element used to make a copy
        ]);
        \Pimcore::getEventDispatcher()->dispatch($event, DataObjectEvents::POST_COPY);

        return $new;
    }

    private function copy(AbstractObject $source, AbstractObject $target): AbstractObject
    {
        /** @var AbstractObject $new */
        $new = Element\Service::cloneMe($source);
        $new->setId(null);
        $new->setChildren(null);
        $new->setKey(Element\Service::getSafeCopyName($new->getKey(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user ? $this->_user->getId() : 0);
        $new->setUserModification($this->_user ? $this->_user->getId() : 0);
        $new->setDao(null);
        $new->setLocked(null);
        $new->setCreationDate(time());

        if ($new instanceof Concrete) {
            foreach ($new->getClass()->getFieldDefinitions() as $fieldDefinition) {
                if ($fieldDefinition instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    foreach ($fieldDefinition->getFieldDefinitions() as $localizedFieldDefinition) {
                        if ($localizedFieldDefinition->getUnique()) {
                            foreach (Tool::getValidLanguages() as $language) {
                                $new->set($localizedFieldDefinition->getName(), null, $language);
                            }
                            $new->setPublished(false);
                        }
                    }
                } elseif ($fieldDefinition->getUnique()) {
                    $new->set($fieldDefinition->getName(), null);
                    $new->setPublished(false);
                }
            }
        }

        $new->save();

        return $new;
    }

    /**
     * @param Concrete $target
     * @param Concrete $source
     *
     * @return Concrete
     *
     * @throws \Exception
     */
    public function copyContents($target, $source)
    {
        // check if the type is the same
        if (get_class($source) !== get_class($target)) {
            throw new \Exception('Source and target have to be the same type');
        }

        //load all in case of lazy loading fields
        self::loadAllObjectFields($source);

        /**
         * @var Concrete $new
         */
        $new = Element\Service::cloneMe($source);
        $new->setChildren($target->getChildren());
        $new->setId($target->getId());
        $new->setPath($target->getRealPath());
        $new->setKey($target->getKey());
        $new->setParentId($target->getParentId());
        $new->setScheduledTasks($source->getScheduledTasks());
        $new->setProperties(self::cloneProperties($source->getProperties()));
        $new->setUserModification($this->_user ? $this->_user->getId() : 0);

        $new->save();

        $target = Concrete::getById($new->getId());

        return $target;
    }

    /**
     * @param string $field
     *
     * @return bool
     *
     * @internal
     */
    public static function isHelperGridColumnConfig($field)
    {
        return strpos($field, '#') === 0;
    }

    /**
     * Language only user for classification store !!!
     *
     * @param AbstractObject $object
     * @param array|null $fields
     * @param string|null $requestedLanguage
     * @param array $params
     *
     * @return array
     *
     * @internal
     */
    public static function gridObjectData($object, $fields = null, $requestedLanguage = null, $params = [])
    {
        $data = Element\Service::gridElementData($object);
        $csvMode = $params['csvMode'] ?? false;

        if ($object instanceof Concrete) {
            $user = AdminTool::getCurrentUser();

            $context = ['object' => $object,
                'purpose' => 'gridview',
                'language' => $requestedLanguage, ];
            $data['classname'] = $object->getClassName();
            $data['idPath'] = Element\Service::getIdPath($object);
            $data['inheritedFields'] = [];
            $data['permissions'] = $object->getUserPermissions($user);
            $data['locked'] = $object->isLocked();

            if (is_null($fields)) {
                $fields = array_keys($object->getclass()->getFieldDefinitions());
            }

            $haveHelperDefinition = false;

            foreach ($fields as $key) {
                $brickDescriptor = null;
                $brickKey = null;
                $brickType = null;
                $brickGetter = null;
                $dataKey = $key;
                $keyParts = explode('~', $key);

                $def = $object->getClass()->getFieldDefinition($key, $context);

                if (strpos($key, '#') === 0) {
                    if (!$haveHelperDefinition) {
                        $helperDefinitions = self::getHelperDefinitions();
                        $haveHelperDefinition = true;
                    }
                    if (!empty($helperDefinitions[$key])) {
                        $context['fieldname'] = $key;
                        $data[$key] = self::calculateCellValue($object, $helperDefinitions, $key, $context);
                    }
                } elseif (strpos($key, '~') === 0) {
                    $type = $keyParts[1];
                    if ($type === 'classificationstore') {
                        $data[$key] = self::getStoreValueForObject($object, $key, $requestedLanguage);
                    }
                } elseif (count($keyParts) > 1) {
                    // brick
                    $brickType = $keyParts[0];
                    if (strpos($brickType, '?') !== false) {
                        $brickDescriptor = substr($brickType, 1);
                        $brickDescriptor = json_decode($brickDescriptor, true);
                        $brickType = $brickDescriptor['containerKey'];
                    }

                    $brickKey = $keyParts[1];

                    $key = self::getFieldForBrickType($object->getclass(), $brickType);

                    $brickClass = Objectbrick\Definition::getByKey($brickType);
                    $context['outerFieldname'] = $key;

                    if ($brickDescriptor) {
                        $innerContainer = $brickDescriptor['innerContainer'] ?? 'localizedfields';
                        /** @var Model\DataObject\ClassDefinition\Data\Localizedfields $localizedFields */
                        $localizedFields = $brickClass->getFieldDefinition($innerContainer);
                        $def = $localizedFields->getFieldDefinition($brickDescriptor['brickfield']);
                    } elseif ($brickClass instanceof Objectbrick\Definition) {
                        $def = $brickClass->getFieldDefinition($brickKey, $context);
                    }
                }

                if (!empty($key)) {
                    // some of the not editable field require a special response
                    $getter = 'get' . ucfirst($key);
                    $needLocalizedPermissions = false;

                    // if the definition is not set try to get the definition from localized fields
                    if (!$def) {
                        /** @var Model\DataObject\ClassDefinition\Data\Localizedfields|null $locFields */
                        $locFields = $object->getClass()->getFieldDefinition('localizedfields');
                        if ($locFields) {
                            $def = $locFields->getFieldDefinition($key, $context);
                            if ($def) {
                                $needLocalizedPermissions = true;
                            }
                        }
                    }

                    //relation type fields with remote owner do not have a getter
                    if (method_exists($object, $getter)) {
                        //system columns must not be inherited
                        if (in_array($key, Concrete::SYSTEM_COLUMN_NAMES)) {
                            $data[$dataKey] = $object->$getter();
                        } else {
                            $valueObject = self::getValueForObject($object, $key, $brickType, $brickKey, $def, $context, $brickDescriptor);
                            $data['inheritedFields'][$dataKey] = ['inherited' => $valueObject->objectid != $object->getId(), 'objectid' => $valueObject->objectid];

                            if ($csvMode || method_exists($def, 'getDataForGrid')) {
                                if ($brickKey) {
                                    $context['containerType'] = 'objectbrick';
                                    $context['containerKey'] = $brickType;
                                    $context['outerFieldname'] = $key;
                                }

                                $params = array_merge($params, ['context' => $context]);
                                if (!isset($params['purpose'])) {
                                    $params['purpose'] = 'gridview';
                                }

                                if ($csvMode) {
                                    $getterParams = ['language' => $requestedLanguage];
                                    $tempData = $def->getForCsvExport($object, $getterParams);
                                } elseif (method_exists($def, 'getDataForGrid')) {
                                    $tempData = $def->getDataForGrid($valueObject->value, $object, $params);
                                } else {
                                    continue;
                                }

                                if ($def instanceof ClassDefinition\Data\Localizedfields) {
                                    $needLocalizedPermissions = true;
                                    foreach ($tempData as $tempKey => $tempValue) {
                                        $data[$tempKey] = $tempValue;
                                    }
                                } else {
                                    $data[$dataKey] = $tempData;
                                    if ($def instanceof Model\DataObject\ClassDefinition\Data\Select && $def->getOptionsProviderClass()) {
                                        $data[$dataKey . '%options'] = $def->getOptions();
                                    }
                                }
                            } else {
                                $data[$dataKey] = $valueObject->value;
                            }
                        }
                    }

                    // because the key for the classification store has not a direct getter, you have to check separately if the data is inheritable
                    if (strpos($key, '~') === 0 && empty($data[$key])) {
                        $type = $keyParts[1];

                        if ($type === 'classificationstore') {
                            $parent = self::hasInheritableParentObject($object);

                            if (!empty($parent)) {
                                $data[$dataKey] = self::getStoreValueForObject($parent, $key, $requestedLanguage);
                                $data['inheritedFields'][$dataKey] = ['inherited' => $parent->getId() != $object->getId(), 'objectid' => $parent->getId()];
                            }
                        }
                    }

                    if ($needLocalizedPermissions) {
                        if (!$user->isAdmin()) {
                            $locale = \Pimcore::getContainer()->get(LocaleServiceInterface::class)->findLocale();

                            $permissionTypes = ['View', 'Edit'];
                            foreach ($permissionTypes as $permissionType) {
                                //TODO, this needs refactoring! Ideally, call it only once!
                                $languagesAllowed = self::getLanguagePermissions($object, $user, 'l' . $permissionType);

                                if ($languagesAllowed) {
                                    $languagesAllowed = array_keys($languagesAllowed);

                                    if (!in_array($locale, $languagesAllowed)) {
                                        $data['metadata']['permission'][$key]['no' . $permissionType] = 1;
                                        if ($permissionType === 'View') {
                                            $data[$key] = null;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param array $helperDefinitions
     * @param string $key
     *
     * @return string[]|null
     *
     * @internal
     */
    public static function expandGridColumnForExport($helperDefinitions, $key)
    {
        $config = self::getConfigForHelperDefinition($helperDefinitions, $key);
        if ($config instanceof AbstractOperator && $config->expandLocales()) {
            return $config->getValidLanguages();
        }

        return null;
    }

    /**
     * @param array $helperDefinitions
     * @param string $key
     * @param array $context
     *
     * @return mixed|null|ConfigElementInterface|ConfigElementInterface[]
     *
     * @internal
     */
    public static function getConfigForHelperDefinition($helperDefinitions, $key, $context = [])
    {
        $cacheKey = 'gridcolumn_config_' . $key;
        if (isset($context['language'])) {
            $cacheKey .= '_' . $context['language'];
        }
        if (RuntimeCache::isRegistered($cacheKey)) {
            $config = RuntimeCache::get($cacheKey);
        } else {
            $definition = $helperDefinitions[$key];
            $attributes = json_decode(json_encode($definition->attributes));

            // TODO refactor how the service is accessed into something non-static and inject the service there
            $service = \Pimcore::getContainer()->get(GridColumnConfigService::class);
            $config = $service->buildOutputDataConfig([$attributes], $context);

            if (!$config) {
                return null;
            }
            $config = $config[0];
            RuntimeCache::save($config, $cacheKey);
        }

        return $config;
    }

    /**
     * @param AbstractObject $object
     * @param array $helperDefinitions
     * @param string $key
     * @param array $context
     *
     * @return \stdClass|array|null
     */
    public static function calculateCellValue($object, $helperDefinitions, $key, $context = [])
    {
        $config = static::getConfigForHelperDefinition($helperDefinitions, $key, $context);
        if (!$config) {
            return null;
        }

        $inheritanceEnabled = AbstractObject::getGetInheritedValues();
        AbstractObject::setGetInheritedValues(true);
        $result = $config->getLabeledValue($object);
        if (isset($result->value)) {
            $result = $result->value;

            if (!empty($config->renderer)) {
                $classname = 'Pimcore\\Model\\DataObject\\ClassDefinition\\Data\\' . ucfirst($config->renderer);
                /** @var Model\DataObject\ClassDefinition\Data $rendererImpl */
                $rendererImpl = new $classname();
                if (method_exists($rendererImpl, 'getDataForGrid')) {
                    $result = $rendererImpl->getDataForGrid($result, $object, []);
                }
            }

            return $result;
        }
        AbstractObject::setGetInheritedValues($inheritanceEnabled);

        return null;
    }

    /**
     * @return mixed
     */
    public static function getHelperDefinitions()
    {
        return Session::useSession(function (AttributeBagInterface $session) {
            $existingColumns = $session->get('helpercolumns', []);

            return $existingColumns;
        }, 'pimcore_gridconfig');
    }

    /**
     * @param AbstractObject|Model\DataObject\Fieldcollection\Data\AbstractData|Model\DataObject\Objectbrick\Data\AbstractData $object
     * @param Model\User $user
     * @param string $type
     *
     * @return array|null
     */
    public static function getLanguagePermissions($object, $user, $type)
    {
        $languageAllowed = null;

        $object = $object instanceof Model\DataObject\Fieldcollection\Data\AbstractData ||
        $object instanceof  Model\DataObject\Objectbrick\Data\AbstractData ?
            $object->getObject() : $object;

        $permission = $object->getPermissions($type, $user);

        if ($permission !== null) {
            // backwards compatibility. If all entries are null, then the workspace rule was set up with
            // an older pimcore

            $permission = $permission[$type];
            if ($permission) {
                $permission = explode(',', $permission);
                if ($languageAllowed === null) {
                    $languageAllowed = [];
                }

                foreach ($permission as $language) {
                    $languageAllowed[$language] = 1;
                }
            }
        }

        return $languageAllowed;
    }

    /**
     * @param string $classId
     * @param array $permissionSet
     *
     * @return array|null
     */
    public static function getLayoutPermissions($classId, $permissionSet)
    {
        $layoutPermissions = null;

        if ($permissionSet !== null) {
            // backwards compatibility. If all entries are null, then the workspace rule was set up with
            // an older pimcore

            $permission = $permissionSet['layouts'];
            if ($permission) {
                $permission = explode(',', $permission);
                if ($layoutPermissions === null) {
                    $layoutPermissions = [];
                }

                foreach ($permission as $p) {
                    if (preg_match(sprintf('#^(%s)_(.*)#', $classId), $p, $setting)) {
                        $l = $setting[2];
                        $layoutPermissions[$l] = $l;
                    }
                }
            }
        }

        return $layoutPermissions;
    }

    /**
     * @param ClassDefinition $class
     * @param string $bricktype
     *
     * @return int|null|string
     */
    public static function getFieldForBrickType(ClassDefinition $class, $bricktype)
    {
        $fieldDefinitions = $class->getFieldDefinitions();
        foreach ($fieldDefinitions as $key => $fd) {
            if ($fd instanceof ClassDefinition\Data\Objectbricks && in_array($bricktype, $fd->getAllowedTypes())) {
                return $key;
            }
        }

        return null;
    }

    /**
     * gets value for given object and getter, including inherited values
     *
     * @static
     *
     * @param Concrete $object
     * @param string $key
     * @param string|null $brickType
     * @param string|null $brickKey
     * @param ClassDefinition\Data|null $fieldDefinition
     * @param array $context
     * @param array|null $brickDescriptor
     *
     * @return \stdClass, value and objectid where the value comes from
     */
    private static function getValueForObject($object, $key, $brickType = null, $brickKey = null, $fieldDefinition = null, $context = [], $brickDescriptor = null)
    {
        $getter = 'get' . ucfirst($key);
        $value = $object->$getter();
        if (!empty($value) && !empty($brickType)) {
            $getBrickType = 'get' . ucfirst($brickType);
            $value = $value->$getBrickType();
            if (!empty($value) && !empty($brickKey)) {
                if ($brickDescriptor) {
                    $innerContainer = $brickDescriptor['innerContainer'] ?? 'localizedfields';
                    $localizedFields = $value->{'get' . ucfirst($innerContainer)}();
                    $brickDefinition = Model\DataObject\Objectbrick\Definition::getByKey($brickType);
                    /** @var Model\DataObject\ClassDefinition\Data\Localizedfields $fieldDefinitionLocalizedFields */
                    $fieldDefinitionLocalizedFields = $brickDefinition->getFieldDefinition('localizedfields');
                    $fieldDefinition = $fieldDefinitionLocalizedFields->getFieldDefinition($brickKey);
                    $value = $localizedFields->getLocalizedValue($brickDescriptor['brickfield']);
                } else {
                    $brickFieldGetter = 'get' . ucfirst($brickKey);
                    $value = $value->$brickFieldGetter();
                }
            }
        }

        if (!$fieldDefinition) {
            $fieldDefinition = $object->getClass()->getFieldDefinition($key, $context);
        }

        if (!empty($brickType) && !empty($brickKey) && !$brickDescriptor) {
            $brickClass = Objectbrick\Definition::getByKey($brickType);
            $context = ['object' => $object, 'outerFieldname' => $key];
            $fieldDefinition = $brickClass->getFieldDefinition($brickKey, $context);
        }

        if ($fieldDefinition->isEmpty($value)) {
            $parent = self::hasInheritableParentObject($object);
            if (!empty($parent)) {
                return self::getValueForObject($parent, $key, $brickType, $brickKey, $fieldDefinition, $context, $brickDescriptor);
            }
        }

        $result = new \stdClass();
        $result->value = $value;
        $result->objectid = $object->getId();

        return $result;
    }

    /**
     * gets store value for given object and key
     *
     * @static
     *
     * @param Concrete $object
     * @param string $key
     * @param string|null $requestedLanguage
     *
     * @return string|null
     */
    private static function getStoreValueForObject($object, $key, $requestedLanguage)
    {
        $keyParts = explode('~', $key);

        if (strpos($key, '~') === 0) {
            $type = $keyParts[1];
            if ($type === 'classificationstore') {
                $field = $keyParts[2];
                $groupKeyId = explode('-', $keyParts[3]);

                $groupId = (int) $groupKeyId[0];
                $keyid = (int) $groupKeyId[1];
                $getter = 'get' . ucfirst($field);

                if (method_exists($object, $getter)) {
                    /** @var Classificationstore $classificationStoreData */
                    $classificationStoreData = $object->$getter();

                    /** @var Model\DataObject\ClassDefinition\Data\Classificationstore $csFieldDefinition */
                    $csFieldDefinition = $object->getClass()->getFieldDefinition($field);
                    $csLanguage = $requestedLanguage;

                    if (!$csFieldDefinition->isLocalized()) {
                        $csLanguage = 'default';
                    }

                    $fielddata = $classificationStoreData->getLocalizedKeyValue($groupId, $keyid, $csLanguage, true, true);

                    $keyConfig = Model\DataObject\Classificationstore\KeyConfig::getById($keyid);
                    $type = $keyConfig->getType();
                    $definition = json_decode($keyConfig->getDefinition());
                    $definition = \Pimcore\Model\DataObject\Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);

                    if (method_exists($definition, 'getDataForGrid')) {
                        $fielddata = $definition->getDataForGrid($fielddata, $object);
                    }

                    return $fielddata;
                }
            }
        }

        return null;
    }

    /**
     * @param Concrete $object
     *
     * @return Concrete|null
     */
    public static function hasInheritableParentObject(Concrete $object)
    {
        if ($object->getClass()->getAllowInherit()) {
            return $object->getNextParentForInheritance();
        }

        return null;
    }

    /**
     * call the getters of each object field, in case some of the are lazy loading and we need the data to be loaded
     *
     * @static
     *
     * @param AbstractObject $object
     */
    public static function loadAllObjectFields($object)
    {
        $object->getProperties();

        if ($object instanceof Concrete) {
            //load all in case of lazy loading fields
            $fd = $object->getClass()->getFieldDefinitions();
            foreach ($fd as $def) {
                $getter = 'get' . ucfirst($def->getName());
                if (method_exists($object, $getter)) {
                    $value = $object->$getter();
                    if ($value instanceof Localizedfield) {
                        $value->loadLazyData();
                    } elseif ($value instanceof Objectbrick) {
                        $value->loadLazyData();
                    } elseif ($value instanceof Fieldcollection) {
                        $value->loadLazyData();
                    }
                }
            }
        }
    }

    /**
     * @static
     *
     * @param Concrete|string $object
     * @param string|ClassDefinition\Data\Select|ClassDefinition\Data\Multiselect $definition
     *
     * @return array
     */
    public static function getOptionsForSelectField($object, $definition)
    {
        $class = null;
        $options = [];

        if (is_object($object) && method_exists($object, 'getClass')) {
            $class = $object->getClass();
        } elseif (is_string($object)) {
            $object = '\\' . ltrim($object, '\\');
            $object = new $object();
            $class = $object->getClass();
        }

        if ($class) {
            if (is_string($definition)) {
                $definition = $class->getFieldDefinition($definition);
            }

            if ($definition instanceof ClassDefinition\Data\Select || $definition instanceof ClassDefinition\Data\Multiselect) {
                $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
                    $definition->getOptionsProviderClass(),
                    DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_MULTISELECT
                );

                if ($optionsProvider instanceof DataObject\ClassDefinition\DynamicOptionsProvider\MultiSelectOptionsProviderInterface) {
                    $_options = $optionsProvider->getOptions(['fieldname' => $definition->getName()], $definition);
                } else {
                    $_options = $definition->getOptions();
                }

                foreach ($_options as $option) {
                    $options[$option['value']] = $option['key'];
                }
            }
        }

        return $options;
    }

    /**
     * alias of getOptionsForMultiSelectField
     *
     * @param Concrete|string $object
     * @param string|ClassDefinition\Data\Select|ClassDefinition\Data\Multiselect $fieldname
     *
     * @return array
     */
    public static function getOptionsForMultiSelectField($object, $fieldname)
    {
        return self::getOptionsForSelectField($object, $fieldname);
    }

    /**
     * @static
     *
     * @param string $path
     * @param string|null $type
     *
     * @return bool
     */
    public static function pathExists($path, $type = null)
    {
        if (!$path) {
            return false;
        }

        $path = Element\Service::correctPath($path);

        try {
            $object = new DataObject();

            $pathElements = explode('/', $path);
            $keyIdx = count($pathElements) - 1;
            $key = $pathElements[$keyIdx];
            $validKey = Element\Service::getValidKey($key, 'object');

            unset($pathElements[$keyIdx]);
            $pathOnly = implode('/', $pathElements);

            if ($validKey == $key && self::isValidPath($pathOnly, 'object')) {
                $object->getDao()->getByPath($path);

                return true;
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * Rewrites id from source to target, $rewriteConfig contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     * @param AbstractObject $object
     * @param array $rewriteConfig
     * @param array $params
     *
     * @return AbstractObject
     */
    public static function rewriteIds($object, $rewriteConfig, $params = [])
    {
        // rewriting elements only for snippets and pages
        if ($object instanceof Concrete) {
            $fields = $object->getClass()->getFieldDefinitions();

            foreach ($fields as $field) {
                if ($field instanceof IdRewriterInterface) {
                    $setter = 'set' . ucfirst($field->getName());
                    if (method_exists($object, $setter)) { // check for non-owner-objects
                        $object->$setter($field->rewriteIds($object, $rewriteConfig));
                    }
                }
            }
        }

        // rewriting properties
        $properties = $object->getProperties();
        foreach ($properties as &$property) {
            $property->rewriteIds($rewriteConfig);
        }
        $object->setProperties($properties);

        return $object;
    }

    /**
     * @param Concrete $object
     *
     * @return DataObject\ClassDefinition\CustomLayout[]
     */
    public static function getValidLayouts(Concrete $object)
    {
        $layoutIds = null;
        $user = AdminTool::getCurrentUser();

        $resultList = [];
        $isMasterAllowed = $user->getAdmin();

        $permissionSet = $object->getPermissions('layouts', $user);
        $layoutPermissions = self::getLayoutPermissions($object->getClassId(), $permissionSet);
        if (!$layoutPermissions || isset($layoutPermissions[0])) {
            $isMasterAllowed = true;
        }

        if ($user->getAdmin()) {
            $superLayout = new ClassDefinition\CustomLayout();
            $superLayout->setId('-1');
            $superLayout->setName('Master (Admin Mode)');
            $resultList[-1] = $superLayout;
        }

        if ($isMasterAllowed) {
            $master = new ClassDefinition\CustomLayout();
            $master->setId('0');
            $master->setName('Master');
            $resultList[0] = $master;
        }

        $classId = $object->getClassId();
        $list = new ClassDefinition\CustomLayout\Listing();
        $list->setOrder(function (ClassDefinition\CustomLayout $a, ClassDefinition\CustomLayout $b) {
            return strcmp($a->getName(), $b->getName());
        });
        if (is_array($layoutPermissions) && count($layoutPermissions)) {
            $layoutIds = array_values($layoutPermissions);
        }
        $list->setFilter(function (DataObject\ClassDefinition\CustomLayout $layout) use ($classId, $layoutIds) {
            $currentLayoutClassId = $layout->getClassId();
            $currentLayoutId = $layout->getId();
            $keep = $currentLayoutClassId === $classId && !str_contains($currentLayoutId, '.brick.');
            if ($keep && $layoutIds !== null) {
                $keep = in_array($currentLayoutId, $layoutIds);
            }

            return $keep;
        });
        $list = $list->load();

        if ((!count($resultList) && !count($list)) || (count($resultList) == 1 && !count($list))) {
            return [];
        }

        foreach ($list as $customLayout) {
            if ($customLayout instanceof ClassDefinition\CustomLayout) {
                $resultList[$customLayout->getId()] = $customLayout;
            }
        }

        return $resultList;
    }

    /**
     * Returns the fields of a datatype container (e.g. block or localized fields)
     *
     * @param ClassDefinition\Data|Model\DataObject\ClassDefinition\Layout $layout
     * @param string $targetClass
     * @param ClassDefinition\Data[] $targetList
     * @param bool $insideDataType
     *
     * @return ClassDefinition\Data[]
     */
    public static function extractFieldDefinitions($layout, $targetClass, $targetList, $insideDataType)
    {
        if ($insideDataType && $layout instanceof ClassDefinition\Data && !is_a($layout, $targetClass)) {
            $targetList[$layout->getName()] = $layout;
        }

        if (method_exists($layout, 'getChildren')) {
            $children = $layout->getChildren();
            $insideDataType = $insideDataType || is_a($layout, $targetClass);
            if (is_array($children)) {
                foreach ($children as $child) {
                    $targetList = self::extractFieldDefinitions($child, $targetClass, $targetList, $insideDataType);
                }
            }
        }

        return $targetList;
    }

    /** Calculates the super layout definition for the given object.
     * @param Concrete $object
     *
     * @return mixed
     */
    public static function getSuperLayoutDefinition(Concrete $object)
    {
        $masterLayout = $object->getClass()->getLayoutDefinitions();
        $superLayout = unserialize(serialize($masterLayout));

        self::createSuperLayout($superLayout);

        return $superLayout;
    }

    /**
     * @param ClassDefinition\Data|Model\DataObject\ClassDefinition\Layout $layout
     */
    public static function createSuperLayout($layout)
    {
        if ($layout instanceof ClassDefinition\Data) {
            $layout->setInvisible(false);
            $layout->setNoteditable(false);
        }

        if ($layout instanceof Model\DataObject\ClassDefinition\Data\Fieldcollections) {
            $layout->setDisallowAddRemove(false);
            $layout->setDisallowReorder(false);
        }

        if (method_exists($layout, 'getChildren')) {
            $children = $layout->getChildren();
            if (is_array($children)) {
                foreach ($children as $child) {
                    self::createSuperLayout($child);
                }
            }
        }
    }

    /**
     * @param ClassDefinition\Data[] $masterDefinition
     * @param ClassDefinition\Data|ClassDefinition\Layout|null $layout
     *
     * @return bool
     */
    private static function synchronizeCustomLayoutFieldWithMaster($masterDefinition, &$layout)
    {
        if (is_null($layout)) {
            return true;
        }

        if ($layout instanceof ClassDefinition\Data) {
            $fieldname = $layout->name;
            if (empty($masterDefinition[$fieldname])) {
                return false;
            }

            if ($layout->getFieldtype() !== $masterDefinition[$fieldname]->getFieldType()) {
                $layout->adoptMasterDefinition($masterDefinition[$fieldname]);
            } else {
                $layout->synchronizeWithMasterDefinition($masterDefinition[$fieldname]);
            }
        }

        if (method_exists($layout, 'getChildren')) {
            $children = $layout->getChildren();
            if (is_array($children)) {
                $count = count($children);
                for ($i = $count - 1; $i >= 0; $i--) {
                    $child = $children[$i];
                    if (!self::synchronizeCustomLayoutFieldWithMaster($masterDefinition, $child)) {
                        unset($children[$i]);
                    }
                    $layout->setChildren($children);
                }
            }
        }

        return true;
    }

    /** Synchronizes a custom layout with its master layout
     * @param ClassDefinition\CustomLayout $customLayout
     */
    public static function synchronizeCustomLayout(ClassDefinition\CustomLayout $customLayout)
    {
        $classId = $customLayout->getClassId();
        $class = ClassDefinition::getById($classId);
        if ($class && ($class->getModificationDate() > $customLayout->getModificationDate())) {
            $masterDefinition = $class->getFieldDefinitions();
            $customLayoutDefinition = $customLayout->getLayoutDefinitions();

            foreach (['Localizedfields', 'Block'] as $dataType) {
                $targetList = self::extractFieldDefinitions($class->getLayoutDefinitions(), '\Pimcore\Model\DataObject\ClassDefinition\Data\\' . $dataType, [], false);
                $masterDefinition = array_merge($masterDefinition, $targetList);
            }

            self::synchronizeCustomLayoutFieldWithMaster($masterDefinition, $customLayoutDefinition);
            $customLayout->save();
        }
    }

    /**
     * @param string $classId
     * @param int $objectId
     *
     * @return array|null
     *
     * @internal
     */
    public static function getCustomGridFieldDefinitions($classId, $objectId)
    {
        $object = DataObject::getById($objectId);

        $class = ClassDefinition::getById($classId);
        $masterFieldDefinition = $class->getFieldDefinitions();

        if (!$object) {
            return null;
        }

        $user = AdminTool::getCurrentUser();
        if ($user->isAdmin()) {
            return null;
        }

        $permissionList = [];

        $parentPermissionSet = $object->getPermissions(null, $user);
        if ($parentPermissionSet) {
            $permissionList[] = $parentPermissionSet;
        }

        $childPermissions = $object->getChildPermissions(null, $user);
        $permissionList = array_merge($permissionList, $childPermissions);

        $layoutDefinitions = [];

        foreach ($permissionList as $permissionSet) {
            $allowedLayoutIds = self::getLayoutPermissions($classId, $permissionSet);
            if (is_array($allowedLayoutIds)) {
                foreach ($allowedLayoutIds as $allowedLayoutId) {
                    if ($allowedLayoutId) {
                        if (!isset($layoutDefinitions[$allowedLayoutId])) {
                            $customLayout = ClassDefinition\CustomLayout::getById($allowedLayoutId);
                            if (!$customLayout) {
                                continue;
                            }
                            $layoutDefinitions[$allowedLayoutId] = $customLayout;
                        }
                    }
                }
            }
        }

        $mergedFieldDefinition = self::cloneDefinition($masterFieldDefinition);

        if (count($layoutDefinitions)) {
            foreach ($mergedFieldDefinition as $def) {
                if ($def instanceof ClassDefinition\Data\Localizedfields) {
                    $mergedLocalizedFieldDefinitions = $def->getFieldDefinitions();

                    foreach ($mergedLocalizedFieldDefinitions as $locValue) {
                        $locValue->setInvisible(false);
                        $locValue->setNotEditable(false);
                    }
                    $def->setChildren($mergedLocalizedFieldDefinitions);
                } else {
                    $def->setInvisible(false);
                    $def->setNotEditable(false);
                }
            }
        }

        foreach ($layoutDefinitions as $customLayoutDefinition) {
            $layoutDefinitions = $customLayoutDefinition->getLayoutDefinitions();
            $dummyClass = new ClassDefinition();
            $dummyClass->setLayoutDefinitions($layoutDefinitions);
            $customFieldDefinitions = $dummyClass->getFieldDefinitions();

            foreach ($mergedFieldDefinition as $key => $value) {
                if (empty($customFieldDefinitions[$key])) {
                    unset($mergedFieldDefinition[$key]);
                }
            }

            foreach ($customFieldDefinitions as $key => $def) {
                if ($def instanceof ClassDefinition\Data\Localizedfields) {
                    if (!$mergedFieldDefinition[$key]) {
                        continue;
                    }
                    $customLocalizedFieldDefinitions = $def->getFieldDefinitions();
                    $mergedLocalizedFieldDefinitions = $mergedFieldDefinition[$key]->getFieldDefinitions();

                    foreach ($mergedLocalizedFieldDefinitions as $locKey => $locValue) {
                        self::mergeFieldDefinition($mergedLocalizedFieldDefinitions, $customLocalizedFieldDefinitions, $locKey);
                    }
                    $mergedFieldDefinition[$key]->setChildren($mergedLocalizedFieldDefinitions);
                } else {
                    self::mergeFieldDefinition($mergedFieldDefinition, $customFieldDefinitions, $key);
                }
            }
        }

        return $mergedFieldDefinition;
    }

    /**
     * @template T
     *
     * @param T $definition
     *
     * @return T
     */
    public static function cloneDefinition($definition)
    {
        $deepCopy = new \DeepCopy\DeepCopy();
        $deepCopy->addFilter(new SetNullFilter(), new PropertyNameMatcher('fieldDefinitionsCache'));
        $theCopy = $deepCopy->copy($definition);

        return $theCopy;
    }

    /**
     * @param array $mergedFieldDefinition
     * @param array $customFieldDefinitions
     * @param string $key
     */
    private static function mergeFieldDefinition(&$mergedFieldDefinition, &$customFieldDefinitions, $key)
    {
        if (empty($customFieldDefinitions[$key])) {
            unset($mergedFieldDefinition[$key]);
        } elseif (isset($mergedFieldDefinition[$key])) {
            $def = $customFieldDefinitions[$key];
            if ($def->getNotEditable()) {
                $mergedFieldDefinition[$key]->setNotEditable(true);
            }
            if ($def->getInvisible()) {
                if ($mergedFieldDefinition[$key] instanceof ClassDefinition\Data\Objectbricks) {
                    unset($mergedFieldDefinition[$key]);

                    return;
                }

                $mergedFieldDefinition[$key]->setInvisible(true);
            }

            if ($def->title) {
                $mergedFieldDefinition[$key]->setTitle($def->title);
            }
        }
    }

    /**
     * @param ClassDefinition\Data|ClassDefinition\Layout $layout
     * @param ClassDefinition\Data[] $fieldDefinitions
     *
     * @return bool
     */
    private static function doFilterCustomGridFieldDefinitions(&$layout, $fieldDefinitions)
    {
        if ($layout instanceof ClassDefinition\Data) {
            $name = $layout->getName();
            if (empty($fieldDefinitions[$name]) || $fieldDefinitions[$name]->getInvisible()) {
                return false;
            }

            $layout->setNoteditable($layout->getNoteditable() || $fieldDefinitions[$name]->getNoteditable());
        }

        if (method_exists($layout, 'getChildren')) {
            $children = $layout->getChildren();
            if (is_array($children)) {
                $count = count($children);
                for ($i = $count - 1; $i >= 0; $i--) {
                    $child = $children[$i];
                    if (!self::doFilterCustomGridFieldDefinitions($child, $fieldDefinitions)) {
                        unset($children[$i]);
                    }
                }
                $layout->setChildren(array_values($children));
            }
        }

        return true;
    }

    /**  Determines the custom layout definition (if necessary) for the given class
     * @param ClassDefinition $class
     * @param int $objectId
     *
     * @return array layout
     *
     * @internal
     */
    public static function getCustomLayoutDefinitionForGridColumnConfig(ClassDefinition $class, $objectId)
    {
        $layoutDefinitions = $class->getLayoutDefinitions();

        $result = [
            'layoutDefinition' => $layoutDefinitions,
        ];

        if (!$objectId) {
            return $result;
        }

        $user = AdminTool::getCurrentUser();

        if ($user->isAdmin()) {
            return $result;
        }

        $mergedFieldDefinition = self::getCustomGridFieldDefinitions($class->getId(), $objectId);
        if (is_array($mergedFieldDefinition)) {
            if (isset($mergedFieldDefinition['localizedfields'])) {
                $childs = $mergedFieldDefinition['localizedfields']->getFieldDefinitions();
                if (is_array($childs)) {
                    foreach ($childs as $locKey => $locValue) {
                        $mergedFieldDefinition[$locKey] = $locValue;
                    }
                }
            }

            self::doFilterCustomGridFieldDefinitions($layoutDefinitions, $mergedFieldDefinition);
            $result['layoutDefinition'] = $layoutDefinitions;
            $result['fieldDefinition'] = $mergedFieldDefinition;
        }

        return $result;
    }

    /**
     * @param AbstractObject $item
     * @param int $nr
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function getUniqueKey($item, $nr = 0)
    {
        $list = new Listing();
        $list->setUnpublished(true);
        $list->setObjectTypes(DataObject::$types);
        $key = Element\Service::getValidKey($item->getKey(), 'object');
        if (!$key) {
            throw new \Exception('No item key set.');
        }
        if ($nr) {
            $key .= '_'.$nr;
        }

        $parent = $item->getParent();
        if (!$parent) {
            throw new \Exception('You have to set a parent Object to determine a unique Key');
        }

        if (!$item->getId()) {
            $list->setCondition('o_parentId = ? AND `o_key` = ? ', [$parent->getId(), $key]);
        } else {
            $list->setCondition('o_parentId = ? AND `o_key` = ? AND o_id != ? ', [$parent->getId(), $key, $item->getId()]);
        }
        $check = $list->loadIdList();
        if (!empty($check)) {
            $nr++;
            $key = self::getUniqueKey($item, $nr);
        }

        return $key;
    }

    /**
     * Enriches the layout definition before it is returned to the admin interface.
     *
     * @param Model\DataObject\ClassDefinition\Data|Model\DataObject\ClassDefinition\Layout|null $layout
     * @param Concrete|null $object
     * @param array $context additional contextual data
     *
     * @internal
     */
    public static function enrichLayoutDefinition(&$layout, $object = null, $context = [])
    {
        if (is_null($layout)) {
            return;
        }

        $context['object'] = $object;

        if ($layout instanceof LayoutDefinitionEnrichmentInterface) {
            $layout->enrichLayoutDefinition($object, $context);
        }

        if ($layout instanceof Model\DataObject\ClassDefinition\Data\Localizedfields || $layout instanceof Model\DataObject\ClassDefinition\Data\Classificationstore && $layout->localized === true) {
            $user = AdminTool::getCurrentUser();
            if (!$user->isAdmin() && ($context['purpose'] ?? null) !== 'gridconfig' && $object) {
                $allowedView = self::getLanguagePermissions($object, $user, 'lView');
                $allowedEdit = self::getLanguagePermissions($object, $user, 'lEdit');
                self::enrichLayoutPermissions($layout, $allowedView, $allowedEdit);
            }

            if (isset($context['containerType']) && $context['containerType'] === 'fieldcollection') {
                $context['subContainerType'] = 'localizedfield';
            } elseif (isset($context['containerType']) && $context['containerType'] === 'objectbrick') {
                $context['subContainerType'] = 'localizedfield';
            } else {
                $context['ownerType'] = 'localizedfield';
            }
            $context['ownerName'] = 'localizedfields';
        }

        if (method_exists($layout, 'getChildren')) {
            $children = $layout->getChildren();
            if (is_array($children)) {
                foreach ($children as $child) {
                    self::enrichLayoutDefinition($child, $object, $context);
                }
            }
        }
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data $layout
     * @param array $allowedView
     * @param array $allowedEdit
     *
     * @internal
     */
    public static function enrichLayoutPermissions(&$layout, $allowedView, $allowedEdit)
    {
        if ($layout instanceof Model\DataObject\ClassDefinition\Data\Localizedfields || $layout instanceof Model\DataObject\ClassDefinition\Data\Classificationstore && $layout->localized === true) {
            if (is_array($allowedView) && count($allowedView) > 0) {
                $haveAllowedViewDefault = null;
                if ($layout->getFieldtype() === 'localizedfields') {
                    $haveAllowedViewDefault = isset($allowedView['default']);
                    if ($haveAllowedViewDefault) {
                        unset($allowedView['default']);
                    }
                }
                if (!($haveAllowedViewDefault && count($allowedView) == 0)) {
                    $layout->setPermissionView(
                        AdminTool::reorderWebsiteLanguages(
                            AdminTool::getCurrentUser(),
                            array_keys($allowedView),
                            true
                        )
                    );
                }
            }
            if (is_array($allowedEdit) && count($allowedEdit) > 0) {
                $haveAllowedEditDefault = null;
                if ($layout->getFieldtype() === 'localizedfields') {
                    $haveAllowedEditDefault = isset($allowedEdit['default']);
                    if ($haveAllowedEditDefault) {
                        unset($allowedEdit['default']);
                    }
                }

                if (!($haveAllowedEditDefault && count($allowedEdit) == 0)) {
                    $layout->setPermissionEdit(
                        AdminTool::reorderWebsiteLanguages(
                            AdminTool::getCurrentUser(),
                            array_keys($allowedEdit),
                            true
                        )
                    );
                }
            }
        } else {
            if (method_exists($layout, 'getChildren')) {
                $children = $layout->getChildren();
                if (is_array($children)) {
                    foreach ($children as $child) {
                        self::enrichLayoutPermissions($child, $allowedView, $allowedEdit);
                    }
                }
            }
        }
    }

    private static function evaluateExpression(Model\DataObject\ClassDefinition\Data\CalculatedValue $fd, Concrete $object, ?DataObject\Data\CalculatedValue $data)
    {
        $expressionLanguage = new ExpressionLanguage();
        //overwrite constant function to aviod exposing internal information
        $expressionLanguage->register('constant', function ($str) {
            throw new SyntaxError('`constant` function not available');
        }, function ($arguments, $str) {
            throw new SyntaxError('`constant` function not available');
        });

        return $expressionLanguage->evaluate($fd->getCalculatorExpression(), ['object' => $object, 'data' => $data]);
    }

    /**
     * @param Concrete $object
     * @param array $params
     * @param Model\DataObject\Data\CalculatedValue|null $data
     *
     * @return string|null
     *
     * @internal
     */
    public static function getCalculatedFieldValueForEditMode($object, $params, $data)
    {
        if (!$data) {
            return null;
        }

        $fieldname = $data->getFieldname();
        $ownerType = $data->getOwnerType();
        $fd = $data->getKeyDefinition();

        if ($fd === null) {
            if ($ownerType === 'object') {
                $fd = $object->getClass()->getFieldDefinition($fieldname);
            } elseif ($ownerType === 'localizedfield') {
                /** @var Model\DataObject\ClassDefinition\Data\Localizedfields $lfDef */
                $lfDef = $object->getClass()->getFieldDefinition('localizedfields');
                $fd = $lfDef->getFieldDefinition($fieldname);
            }
        }

        if (!$fd instanceof Model\DataObject\ClassDefinition\Data\CalculatedValue) {
            return null;
        }

        $inheritanceEnabled = Model\DataObject\Concrete::getGetInheritedValues();
        Model\DataObject\Concrete::setGetInheritedValues(true);
        switch ($fd->getCalculatorType()) {
            case DataObject\ClassDefinition\Data\CalculatedValue::CALCULATOR_TYPE_CLASS:
                $className = $fd->getCalculatorClass();
                $calculator = Model\DataObject\ClassDefinition\Helper\CalculatorClassResolver::resolveCalculatorClass($className);
                if (!$calculator instanceof DataObject\ClassDefinition\CalculatorClassInterface) {
                    Logger::error('Class does not exist or is not valid: ' . $className);

                    return null;
                }

                $result = $calculator->getCalculatedValueForEditMode($object, $data);

                break;

            case DataObject\ClassDefinition\Data\CalculatedValue::CALCULATOR_TYPE_EXPRESSION:

                try {
                    $result = self::evaluateExpression($fd, $object, $data);
                } catch (SyntaxError $exception) {
                    return $exception->getMessage();
                }

                break;

            default:
                return null;
        }

        Model\DataObject\Concrete::setGetInheritedValues($inheritanceEnabled);

        return $result;
    }

    /**
     * @param Concrete|Model\DataObject\Fieldcollection\Data\AbstractData|Model\DataObject\Objectbrick\Data\AbstractData $object
     * @param Model\DataObject\Data\CalculatedValue|null $data
     *
     * @return mixed|null
     */
    public static function getCalculatedFieldValue($object, $data)
    {
        if (!$data) {
            return null;
        }
        $fieldname = $data->getFieldname();
        $ownerType = $data->getOwnerType();

        $fd = $data->getKeyDefinition();
        if ($fd === null) {
            if ($ownerType === 'object') {
                $fd = $object->getClass()->getFieldDefinition($fieldname);
            } elseif ($ownerType === 'localizedfield') {
                /** @var Model\DataObject\ClassDefinition\Data\Localizedfields $lfDef */
                $lfDef = $object->getClass()->getFieldDefinition('localizedfields');
                $fd = $lfDef->getFieldDefinition($fieldname);
            }
        }

        if (!$fd instanceof Model\DataObject\ClassDefinition\Data\CalculatedValue) {
            return null;
        }

        $inheritanceEnabled = Model\DataObject\Concrete::getGetInheritedValues();
        Model\DataObject\Concrete::setGetInheritedValues(true);

        if (
            $object instanceof Model\DataObject\Fieldcollection\Data\AbstractData ||
            $object instanceof Model\DataObject\Objectbrick\Data\AbstractData
        ) {
            $object = $object->getObject();
        }

        switch ($fd->getCalculatorType()) {
            case DataObject\ClassDefinition\Data\CalculatedValue::CALCULATOR_TYPE_CLASS:
                $className = $fd->getCalculatorClass();
                $calculator = Model\DataObject\ClassDefinition\Helper\CalculatorClassResolver::resolveCalculatorClass($className);
                if (!$calculator instanceof DataObject\ClassDefinition\CalculatorClassInterface) {
                    Logger::error('Class does not exist or is not valid: ' . $className);

                    return null;
                }
                $result = $calculator->compute($object, $data);

                break;

            case DataObject\ClassDefinition\Data\CalculatedValue::CALCULATOR_TYPE_EXPRESSION:

                try {
                    $result = self::evaluateExpression($fd, $object, $data);
                } catch (SyntaxError $exception) {
                    return $exception->getMessage();
                }

                break;

            default:
                return null;
        }

        Model\DataObject\Concrete::setGetInheritedValues($inheritanceEnabled);

        return $result;
    }

    /**
     * @return array
     */
    public static function getSystemFields()
    {
        return self::$systemFields;
    }

    /**
     * @param Model\AbstractModel $container
     * @param ClassDefinition|ClassDefinition\Data $fd
     */
    public static function doResetDirtyMap($container, $fd)
    {
        if (!method_exists($fd, 'getFieldDefinitions')) {
            return;
        }

        $fieldDefinitions = $fd->getFieldDefinitions();

        if (is_array($fieldDefinitions)) {
            foreach ($fieldDefinitions as $fieldDefinition) {
                $value = $container->getObjectVar($fieldDefinition->getName());

                if ($value instanceof Localizedfield) {
                    $value->resetLanguageDirtyMap();
                }

                if ($value instanceof Model\AbstractModel && $value instanceof DirtyIndicatorInterface) {
                    $value->resetDirtyMap();
                    self::doResetDirtyMap($value, $fieldDefinitions[$fieldDefinition->getName()]);
                }
            }
        }
    }

    /**
     * @param AbstractObject $object
     */
    public static function recursiveResetDirtyMap(AbstractObject $object)
    {
        if ($object instanceof DirtyIndicatorInterface) {
            $object->resetDirtyMap();
        }

        if ($object instanceof Concrete) {
            self::doResetDirtyMap($object, $object->getClass());
        }
    }

    /**
     * @internal
     *
     * @param array $descriptor
     *
     * @return array
     */
    public static function buildConditionPartsFromDescriptor($descriptor)
    {
        $db = Db::get();
        $conditionParts = [];
        foreach ($descriptor as $key => $value) {
            $lastChar = is_string($value) ? $value[strlen($value) - 1] : null;
            if ($lastChar === '%') {
                $conditionParts[] = $key . ' LIKE ' . $db->quote($value);
            } else {
                $conditionParts[] = $key . ' = ' . $db->quote($value);
            }
        }

        return $conditionParts;
    }

    /**
     * @param Concrete $object
     * @param string $requestedLanguage
     * @param array $fields
     * @param array $helperDefinitions
     * @param LocaleServiceInterface $localeService
     * @param bool $returnMappedFieldNames
     * @param array $context
     *
     * @return array
     *
     * @internal
     */
    public static function getCsvDataForObject(Concrete $object, $requestedLanguage, $fields, $helperDefinitions, LocaleServiceInterface $localeService, $returnMappedFieldNames = false, $context = [])
    {
        $objectData = [];
        $mappedFieldnames = [];
        foreach ($fields as $field) {
            if (static::isHelperGridColumnConfig($field) && $validLanguages = static::expandGridColumnForExport($helperDefinitions, $field)) {
                $currentLocale = $localeService->getLocale();
                $mappedFieldnameBase = self::mapFieldname($field, $helperDefinitions);

                foreach ($validLanguages as $validLanguage) {
                    $localeService->setLocale($validLanguage);
                    $fieldData = self::getCsvFieldData($currentLocale, $field, $object, $validLanguage, $helperDefinitions);
                    $localizedFieldKey = $field . '-' . $validLanguage;
                    if (!isset($mappedFieldnames[$localizedFieldKey])) {
                        $mappedFieldnames[$localizedFieldKey] = $mappedFieldnameBase . '-' . $validLanguage;
                    }
                    $objectData[$localizedFieldKey] = $fieldData;
                }

                $localeService->setLocale($currentLocale);
            } else {
                $fieldData = self::getCsvFieldData($requestedLanguage, $field, $object, $requestedLanguage, $helperDefinitions);
                if (!isset($mappedFieldnames[$field])) {
                    $mappedFieldnames[$field] = self::mapFieldname($field, $helperDefinitions);
                }

                $objectData[$field] = $fieldData;
            }
        }

        if ($returnMappedFieldNames) {
            $tmp = [];
            foreach ($mappedFieldnames as $key => $value) {
                $tmp[$value] = $objectData[$key];
            }
            $objectData = $tmp;
        }

        $event = new DataObjectEvent($object, ['objectData' => $objectData,
            'context' => $context,
            'requestedLanguage' => $requestedLanguage,
            'fields' => $fields,
            'helperDefinitions' => $helperDefinitions,
            'localeService' => $localeService,
            'returnMappedFieldNames' => $returnMappedFieldNames,
        ]);

        \Pimcore::getEventDispatcher()->dispatch($event, DataObjectEvents::POST_CSV_ITEM_EXPORT);
        $objectData = $event->getArgument('objectData');

        return $objectData;
    }

    /**
     * @param string $requestedLanguage
     * @param LocaleServiceInterface $localeService
     * @param DataObject\Listing $list
     * @param string[] $fields
     * @param bool $addTitles
     * @param array $context
     *
     * @return array
     *
     * @internal
     */
    public static function getCsvData($requestedLanguage, LocaleServiceInterface $localeService, $list, $fields, $addTitles = true, $context = [])
    {
        $data = [];
        Logger::debug('objects in list:' . count($list->getObjects()));

        $helperDefinitions = static::getHelperDefinitions();

        foreach ($list->getObjects() as $object) {
            if ($fields) {
                if ($addTitles && empty($data)) {
                    $tmp = [];
                    $mapped = self::getCsvDataForObject($object, $requestedLanguage, $fields, $helperDefinitions, $localeService, true, $context);
                    foreach ($mapped as $key => $value) {
                        $tmp[] = '"' . $key . '"';
                    }
                    $data[] = $tmp;
                }

                $rowData = self::getCsvDataForObject($object, $requestedLanguage, $fields, $helperDefinitions, $localeService, false, $context);
                $rowData = self::escapeCsvRecord($rowData);
                $data[] = $rowData;
            }
        }

        return $data;
    }

    /**
     * @param string $field
     * @param array $helperDefinitions
     *
     * @return string
     */
    protected static function mapFieldname($field, $helperDefinitions)
    {
        if (strpos($field, '#') === 0) {
            if (isset($helperDefinitions[$field])) {
                if ($helperDefinitions[$field]->attributes) {
                    return $helperDefinitions[$field]->attributes->label ? $helperDefinitions[$field]->attributes->label : $field;
                }

                return $field;
            }
        } elseif (substr($field, 0, 1) == '~') {
            $fieldParts = explode('~', $field);
            $type = $fieldParts[1];

            if ($type == 'classificationstore') {
                $fieldname = $fieldParts[2];
                $groupKeyId = explode('-', $fieldParts[3]);
                $groupId = (int) $groupKeyId[0];
                $keyId = (int) $groupKeyId[1];

                $groupConfig = DataObject\Classificationstore\GroupConfig::getById($groupId);
                $keyConfig = DataObject\Classificationstore\KeyConfig::getById($keyId);

                $field = $fieldname . '~' . $groupConfig->getName() . '~' . $keyConfig->getName();
            }
        }

        return $field;
    }

    /**
     * @param string $fallbackLanguage
     * @param string $field
     * @param DataObject\Concrete $object
     * @param string $requestedLanguage
     * @param array $helperDefinitions
     *
     * @return mixed
     *
     * @internal
     */
    protected static function getCsvFieldData($fallbackLanguage, $field, $object, $requestedLanguage, $helperDefinitions)
    {
        //check if field is systemfield
        $systemFieldMap = [
            'id' => 'getId',
            'fullpath' => 'getRealFullPath',
            'published' => 'getPublished',
            'creationDate' => 'getCreationDate',
            'modificationDate' => 'getModificationDate',
            'filename' => 'getKey',
            'key' => 'getKey',
            'classname' => 'getClassname',
        ];
        if (in_array($field, array_keys($systemFieldMap))) {
            $getter = $systemFieldMap[$field];

            return $object->$getter();
        } else {
            //check if field is standard object field
            $fieldDefinition = $object->getClass()->getFieldDefinition($field);
            if ($fieldDefinition) {
                return $fieldDefinition->getForCsvExport($object);
            } else {
                $fieldParts = explode('~', $field);

                // check for objects bricks and localized fields
                if (static::isHelperGridColumnConfig($field)) {
                    if ($helperDefinitions[$field]) {
                        $cellValue = static::calculateCellValue($object, $helperDefinitions, $field, ['language' => $requestedLanguage]);

                        // Mimic grid concatenation behavior
                        if (is_array($cellValue)) {
                            $cellValue = implode(',', $cellValue);
                        }

                        return $cellValue;
                    }
                } elseif (substr($field, 0, 1) == '~') {
                    $type = $fieldParts[1];

                    if ($type == 'classificationstore') {
                        $fieldname = $fieldParts[2];
                        $groupKeyId = explode('-', $fieldParts[3]);
                        $groupId = (int) $groupKeyId[0];
                        $keyId = (int) $groupKeyId[1];
                        $getter = 'get' . ucfirst($fieldname);
                        if (method_exists($object, $getter)) {
                            $keyConfig = DataObject\Classificationstore\KeyConfig::getById($keyId);
                            $type = $keyConfig->getType();
                            $definition = json_decode($keyConfig->getDefinition());
                            $fieldDefinition = \Pimcore\Model\DataObject\Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);

                            /** @var DataObject\ClassDefinition\Data\Classificationstore $csFieldDefinition */
                            $csFieldDefinition = $object->getClass()->getFieldDefinition($fieldname);
                            $csLanguage = $requestedLanguage;
                            if (!$csFieldDefinition->isLocalized()) {
                                $csLanguage = 'default';
                            }

                            return $fieldDefinition->getForCsvExport(
                                $object,
                                ['context' => [
                                    'containerType' => 'classificationstore',
                                    'fieldname' => $fieldname,
                                    'groupId' => $groupId,
                                    'keyId' => $keyId,
                                    'language' => $csLanguage,
                                ]]
                            );
                        }
                    }
                //key value store - ignore for now
                } elseif (count($fieldParts) > 1) {
                    // brick
                    $brickType = $fieldParts[0];
                    $brickDescriptor = null;
                    $innerContainer = null;

                    if (strpos($brickType, '?') !== false) {
                        $brickDescriptor = substr($brickType, 1);
                        $brickDescriptor = json_decode($brickDescriptor, true);
                        $innerContainer = $brickDescriptor['innerContainer'] ?? 'localizedfields';
                        $brickType = $brickDescriptor['containerKey'];
                    }
                    $brickKey = $fieldParts[1];

                    $key = static::getFieldForBrickType($object->getClass(), $brickType);

                    $brickClass = DataObject\Objectbrick\Definition::getByKey($brickType);

                    if ($brickDescriptor) {
                        /** @var DataObject\ClassDefinition\Data\Localizedfields $localizedFields */
                        $localizedFields = $brickClass->getFieldDefinition($innerContainer);
                        $fieldDefinition = $localizedFields->getFieldDefinition($brickDescriptor['brickfield']);
                    } else {
                        $fieldDefinition = $brickClass->getFieldDefinition($brickKey);
                    }

                    if ($fieldDefinition) {
                        $brickContainer = $object->{'get' . ucfirst($key)}();
                        if ($brickContainer && !empty($brickKey)) {
                            $brick = $brickContainer->{'get' . ucfirst($brickType)}();
                            if ($brick) {
                                $params = [
                                    'context' => [
                                        'containerType' => 'objectbrick',
                                        'containerKey' => $brickType,
                                        'fieldname' => $brickKey,
                                    ],

                                ];

                                $value = $brick;

                                if ($brickDescriptor) {
                                    $innerContainer = $brickDescriptor['innerContainer'] ?? 'localizedfields';
                                    $value = $brick->{'get' . ucfirst($innerContainer)}();
                                }

                                return $fieldDefinition->getForCsvExport($value, $params);
                            }
                        }
                    }
                } else {
                    // if the definition is not set try to get the definition from localized fields
                    /** @var DataObject\ClassDefinition\Data\Localizedfields|null $locFields */
                    $locFields = $object->getClass()->getFieldDefinition('localizedfields');

                    if ($locFields) {
                        $fieldDefinition = $locFields->getFieldDefinition($field);
                        if ($fieldDefinition) {
                            return $fieldDefinition->getForCsvExport($object->get('localizedFields'), ['language' => $fallbackLanguage]);
                        }
                    }
                }
            }
        }

        return null;
    }
}
