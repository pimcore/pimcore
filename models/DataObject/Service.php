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

use Pimcore\Cache\Runtime;
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
use Pimcore\Model\Element;
use Pimcore\Tool\Admin as AdminTool;
use Pimcore\Tool\Session;
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
            if (is_array($dataKeys) and count($dataKeys) > 0) {
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
        if ($userObjects) {
            $userObjects = \array_merge(...$userObjects);
        }

        return $userObjects;
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

        /** @var Concrete $new */
        $new = Element\Service::cloneMe($source);
        $new->setId(null);
        $new->setChildren(null);
        $new->setKey(Element\Service::getSaveCopyName('object', $new->getKey(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user ? $this->_user->getId() : 0);
        $new->setUserModification($this->_user ? $this->_user->getId() : 0);
        $new->setDao(null);
        $new->setLocked(false);
        $new->setCreationDate(time());

        if ($new instanceof Concrete) {
            foreach ($new->getClass()->getFieldDefinitions() as $fieldDefinition) {
                if ($fieldDefinition->getUnique()) {
                    $new->set($fieldDefinition->getName(), null);
                    $new->setPublished(false);
                }
            }
        }

        $new->save();

        // add to store
        $this->_copyRecursiveIds[] = $new->getId();

        $children = $source->getChildren([
            AbstractObject::OBJECT_TYPE_OBJECT,
            AbstractObject::OBJECT_TYPE_VARIANT,
            AbstractObject::OBJECT_TYPE_FOLDER,
        ], true);

        foreach ($children as $child) {
            $this->copyRecursive($new, $child);
        }

        $this->updateChildren($target, $new);

        // triggers actions after the complete document cloning
        \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_COPY, new DataObjectEvent($new, [
            'base_element' => $source, // the element used to make a copy
        ]));

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
        $isDirtyDetectionDisabled = AbstractObject::isDirtyDetectionDisabled();
        AbstractObject::setDisableDirtyDetection(true);

        //load properties
        $source->getProperties();

        //load all in case of lazy loading fields
        self::loadAllObjectFields($source);

        /** @var Concrete $new */
        $new = Element\Service::cloneMe($source);
        $new->setId(null);

        $new->setChildren(null);
        $new->setKey(Element\Service::getSaveCopyName('object', $new->getKey(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user ? $this->_user->getId() : 0);
        $new->setUserModification($this->_user ? $this->_user->getId() : 0);
        $new->setDao(null);
        $new->setLocked(false);
        $new->setCreationDate(time());

        if ($new instanceof Concrete) {
            foreach ($new->getClass()->getFieldDefinitions() as $fieldDefinition) {
                if ($fieldDefinition->getUnique()) {
                    $new->set($fieldDefinition->getName(), null);
                    $new->setPublished(false);
                }
            }
        }

        $new->save();

        AbstractObject::setDisableDirtyDetection($isDirtyDetectionDisabled);

        $this->updateChildren($target, $new);

        // triggers actions after the complete object cloning
        \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_COPY, new DataObjectEvent($new, [
            'base_element' => $source, // the element used to make a copy
        ]));

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
        $new->setProperties($source->getProperties());
        $new->setUserModification($this->_user ? $this->_user->getId() : 0);

        $new->save();

        $target = Concrete::getById($new->getId());

        return $target;
    }

    /**
     * @param string $field
     *
     * @return bool
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
     */
    public static function gridObjectData($object, $fields = null, $requestedLanguage = null, $params = [])
    {
        $data = Element\Service::gridElementData($object);
        $csvMode = $params['csvMode'] ?? false;

        if ($object instanceof Concrete) {
            $context = ['object' => $object,
                'purpose' => 'gridview',
                'language' => $requestedLanguage, ];
            $data['classname'] = $object->getClassName();
            $data['idPath'] = Element\Service::getIdPath($object);
            $data['inheritedFields'] = [];
            $data['permissions'] = $object->getUserPermissions();
            $data['locked'] = $object->isLocked();

            $user = AdminTool::getCurrentUser();

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
                        if (in_array($key, Concrete::$systemColumnNames)) {
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
                            $locale = \Pimcore::getContainer()->get('pimcore.locale')->findLocale();

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
     */
    public static function getConfigForHelperDefinition($helperDefinitions, $key, $context = [])
    {
        $cacheKey = 'gridcolumn_config_' . $key;
        if (isset($context['language'])) {
            $cacheKey .= '_' . $context['language'];
        }
        if (Runtime::isRegistered($cacheKey)) {
            $config = Runtime::get($cacheKey);
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
            Runtime::save($config, $cacheKey);
        }

        return $config;
    }

    /**
     * @param AbstractObject $object
     * @param array $helperDefinitions
     * @param string $key
     * @param array $context
     *
     * @return \stdClass|null
     */
    public static function calculateCellValue($object, $helperDefinitions, $key, $context = [])
    {
        $config = static::getConfigForHelperDefinition($helperDefinitions, $key, $context);
        if (!$config) {
            return null;
        }

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
     * @param AbstractObject $object
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

                        if ($layoutPermissions === null) {
                            $layoutPermissions = [];
                        }
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

                $groupId = $groupKeyId[0];
                $keyid = $groupKeyId[1];
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
     * @return AbstractObject|null
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
                /**
                 * @var ClassDefinition\Data\Select $definition
                 */
                $definition = $class->getFieldDefinition($definition);
            }

            if ($definition instanceof ClassDefinition\Data\Select || $definition instanceof ClassDefinition\Data\Multiselect) {
                $_options = $definition->getOptions();

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
        $path = Element\Service::correctPath($path);

        try {
            $object = new AbstractObject();

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
     *
     * @return AbstractObject
     */
    public static function rewriteIds($object, $rewriteConfig)
    {
        // rewriting elements only for snippets and pages
        if ($object instanceof Concrete) {
            $fields = $object->getClass()->getFieldDefinitions();

            foreach ($fields as $field) {
                if (method_exists($field, 'rewriteIds')) {
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
     * @return array
     */
    public static function getValidLayouts(Concrete $object)
    {
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
            $superLayout->setId(-1);
            $superLayout->setName('Master (Admin Mode)');
            $resultList[-1] = $superLayout;
        }

        if ($isMasterAllowed) {
            $master = new ClassDefinition\CustomLayout();
            $master->setId(0);
            $master->setName('Master');
            $resultList[0] = $master;
        }

        $classId = $object->getClassId();
        $list = new ClassDefinition\CustomLayout\Listing();
        $list->setOrderKey('name');
        $condition = 'classId = ' . $list->quote($classId);
        if (is_array($layoutPermissions) && count($layoutPermissions)) {
            $layoutIds = array_values($layoutPermissions);
            $condition .= ' AND id IN (' . implode(',', array_map([$list, 'quote'], $layoutIds)) . ')';
        }
        $list->setCondition($condition);
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
        if ($insideDataType && $layout instanceof ClassDefinition\Data and !is_a($layout, $targetClass)) {
            $targetList[$layout->getName()] = $layout;
        }

        if (method_exists($layout, 'getChildren')) {
            $children = $layout->getChildren();
            $insideDataType |= is_a($layout, $targetClass);
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
    public static function createSuperLayout(&$layout)
    {
        if ($layout instanceof ClassDefinition\Data) {
            $layout->setInvisible(false);
            $layout->setNoteditable(false);
        }

        if ($layout instanceof Model\DataObject\ClassDefinition\Data\Fieldcollections) {
            unset($layout->disallowAddRemove);
            unset($layout->disallowReorder);
            $layout->layoutId = -1;
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
     * @param ClassDefinition\Data|ClassDefinition\Layout $layout
     *
     * @return bool
     */
    private static function synchronizeCustomLayoutFieldWithMaster($masterDefinition, &$layout)
    {
        if ($layout instanceof ClassDefinition\Data) {
            $fieldname = $layout->name;
            if (!$masterDefinition[$fieldname]) {
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
     */
    public static function getCustomGridFieldDefinitions($classId, $objectId)
    {
        $object = AbstractObject::getById($objectId);

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

        $parentPermissionSet = $object->getPermissions(null, $user, true);
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
                        if (!$layoutDefinitions[$allowedLayoutId]) {
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
            foreach ($mergedFieldDefinition as $key => $def) {
                if ($def instanceof ClassDefinition\Data\Localizedfields) {
                    $mergedLocalizedFieldDefinitions = $mergedFieldDefinition[$key]->getFieldDefinitions();

                    foreach ($mergedLocalizedFieldDefinitions as $locKey => $locValue) {
                        $mergedLocalizedFieldDefinitions[$locKey]->setInvisible(false);
                        $mergedLocalizedFieldDefinitions[$locKey]->setNotEditable(false);
                    }
                    $mergedFieldDefinition[$key]->setChilds($mergedLocalizedFieldDefinitions);
                } else {
                    $mergedFieldDefinition[$key]->setInvisible(false);
                    $mergedFieldDefinition[$key]->setNotEditable(false);
                }
            }
        }

        foreach ($layoutDefinitions as $customLayoutDefinition) {
            $layoutDefinitions = $customLayoutDefinition->getLayoutDefinitions();
            $dummyClass = new ClassDefinition();
            $dummyClass->setLayoutDefinitions($layoutDefinitions);
            $customFieldDefinitions = $dummyClass->getFieldDefinitions();

            foreach ($mergedFieldDefinition as $key => $value) {
                if (!$customFieldDefinitions[$key]) {
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
                    $mergedFieldDefinition[$key]->setChilds($mergedLocalizedFieldDefinitions);
                } else {
                    self::mergeFieldDefinition($mergedFieldDefinition, $customFieldDefinitions, $key);
                }
            }
        }

        return $mergedFieldDefinition;
    }

    /**
     * @param array $definition
     *
     * @return array
     */
    public static function cloneDefinition($definition)
    {
        $deepCopy = new \DeepCopy\DeepCopy();
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
        if (!$customFieldDefinitions[$key]) {
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
            if (!$fieldDefinitions[$name] || $fieldDefinitions[$name]->getInvisible()) {
                return false;
            }

            $layout->setNoteditable($layout->getNoteditable() | $fieldDefinitions[$name]->getNoteditable());
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
        $list->setObjectTypes([AbstractObject::OBJECT_TYPE_OBJECT, AbstractObject::OBJECT_TYPE_FOLDER, AbstractObject::OBJECT_TYPE_VARIANT]);
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
     * @param Model\DataObject\ClassDefinition\Data|Model\DataObject\ClassDefinition\Layout $layout
     * @param Concrete|null $object
     * @param array $context additional contextual data
     */
    public static function enrichLayoutDefinition(&$layout, $object = null, $context = [])
    {
        $context['object'] = $object;

        if (method_exists($layout, 'enrichLayoutDefinition')) {
            $layout->enrichLayoutDefinition($object, $context);
        }

        if ($layout instanceof Model\DataObject\ClassDefinition\Data\Localizedfields) {
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
     */
    public static function enrichLayoutPermissions(&$layout, $allowedView, $allowedEdit)
    {
        if ($layout instanceof Model\DataObject\ClassDefinition\Data\Localizedfields) {
            if (is_array($allowedView) && count($allowedView) > 0) {
                $haveAllowedViewDefault = null;
                if ($layout->{'fieldtype'} === 'localizedfields') {
                    $haveAllowedViewDefault = isset($allowedView['default']);
                    if ($haveAllowedViewDefault) {
                        unset($allowedView['default']);
                    }
                }
                if (!($haveAllowedViewDefault && count($allowedView) == 0)) {
                    $layout->{'permissionView'} = AdminTool::reorderWebsiteLanguages(
                        AdminTool::getCurrentUser(),
                        array_keys($allowedView),
                        true
                    );
                }
            }
            if (is_array($allowedEdit) && count($allowedEdit) > 0) {
                $haveAllowedEditDefault = null;
                if ($layout->{'fieldtype'} === 'localizedfields') {
                    $haveAllowedEditDefault = isset($allowedEdit['default']);
                    if ($haveAllowedEditDefault) {
                        unset($allowedEdit['default']);
                    }
                }

                if (!($haveAllowedEditDefault && count($allowedEdit) == 0)) {
                    $layout->{'permissionEdit'} = AdminTool::reorderWebsiteLanguages(
                        AdminTool::getCurrentUser(),
                        array_keys($allowedEdit),
                        true
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

    /**
     * @param Concrete $object
     * @param array $params
     * @param Model\DataObject\Data\CalculatedValue $data
     *
     * @return string|null
     */
    public static function getCalculatedFieldValueForEditMode($object, $params = [], $data)
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
        $className = $fd->getCalculatorClass();
        $calculator = Model\DataObject\ClassDefinition\Helper\CalculatorClassResolver::resolveCalculatorClass($className);
        if (!$className || $calculator === null) {
            Logger::error('Class does not exist: ' . $className);

            return null;
        }

        if (!$calculator instanceof Model\DataObject\ClassDefinition\CalculatorClassInterface) {
            @trigger_error('Using a calculator class which does not implement '.Model\DataObject\ClassDefinition\CalculatorClassInterface::class.' is deprecated', \E_USER_DEPRECATED);
        }

        $inheritanceEnabled = Model\DataObject\Concrete::getGetInheritedValues();
        Model\DataObject\Concrete::setGetInheritedValues(true);

        if (method_exists($calculator, 'getCalculatedValueForEditMode')) {
            $result = call_user_func([$calculator, 'getCalculatedValueForEditMode'], $object, $data);
        } else {
            $result = self::getCalculatedFieldValue($object, $data);
        }
        Model\DataObject\Concrete::setGetInheritedValues($inheritanceEnabled);

        return $result;
    }

    /**
     * @param Concrete $object
     * @param Model\DataObject\Data\CalculatedValue $data
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
        $className = $fd->getCalculatorClass();
        $calculator = Model\DataObject\ClassDefinition\Helper\CalculatorClassResolver::resolveCalculatorClass($className);
        if (!$className || $calculator === null) {
            Logger::error('Calculator class "' . $className.'" does not exist -> '.$fieldname.'=null');

            return null;
        }

        if (method_exists($calculator, 'compute')) {
            $inheritanceEnabled = Model\DataObject\Concrete::getGetInheritedValues();
            Model\DataObject\Concrete::setGetInheritedValues(true);

            if ($object instanceof Model\DataObject\Fieldcollection\Data\AbstractData
                || $object instanceof Model\DataObject\Objectbrick\Data\AbstractData) {
                $object = $object->getObject();
            }
            $result = call_user_func([$calculator, 'compute'], $object, $data);
            Model\DataObject\Concrete::setGetInheritedValues($inheritanceEnabled);

            return $result;
        }

        return null;
    }

    /**
     * @return array
     */
    public static function getSystemFields()
    {
        return self::$systemFields;
    }

    /**
     * @param Concrete $container
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

                if ($value instanceof DirtyIndicatorInterface) {
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
     * @deprecated
     *
     * @param int $objectId
     *
     * @return AbstractObject|null
     */
    public static function getObjectFromSession($objectId)
    {
        return self::getElementFromSession('object', $objectId);
    }

    /**
     * @deprecated
     *
     * @param int $objectId
     */
    public static function removeObjectFromSession($objectId)
    {
        self::removeElementFromSession('object', $objectId);
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
     * @param AbstractObject $object
     * @param string $requestedLanguage
     * @param array $fields
     * @param array $helperDefinitions
     * @param LocaleServiceInterface $localeService
     * @param bool $returnMappedFieldNames
     * @param array $context
     *
     * @return array
     */
    public static function getCsvDataForObject(AbstractObject $object, $requestedLanguage, $fields, $helperDefinitions, LocaleServiceInterface $localeService, $returnMappedFieldNames = false, $context = [])
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

        \Pimcore::getEventDispatcher()->dispatch(DataObjectEvents::POST_CSV_ITEM_EXPORT, $event);
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
     */
    public static function getCsvData($requestedLanguage, LocaleServiceInterface $localeService, $list, $fields, $addTitles = true, $context = [])
    {
        $mappedFieldnames = [];

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

                $data[] = self::getCsvDataForObject($object, $requestedLanguage, $fields, $helperDefinitions, $localeService, $context);
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
                $groupId = $groupKeyId[0];
                $keyId = $groupKeyId[1];

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
                        $groupId = $groupKeyId[0];
                        $keyId = $groupKeyId[1];
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
