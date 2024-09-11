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

use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\PropertyNameMatcher;
use Exception;
use Pimcore;
use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\ConfigElementInterface;
use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator\AbstractOperator;
use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Service as GridColumnConfigService;
use Pimcore\Bundle\AdminBundle\Service\GridData;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Extension\Bundle\Exception\AdminClassicBundleNotFoundException;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\IdRewriterInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\LayoutDefinitionEnrichmentInterface;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\Element;
use Pimcore\Model\Element\DirtyIndicatorInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tool;
use Pimcore\Tool\Admin as AdminTool;
use Pimcore\Tool\Session;
use stdClass;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Throwable;

/**
 * @method \Pimcore\Model\Element\Dao getDao()
 */
class Service extends Model\Element\Service
{
    /**
     * @internal
     */
    protected array $_copyRecursiveIds = [];

    /**
     * @internal
     */
    protected ?Model\User $_user;

    /**
     * System fields used by filter conditions
     *
     */
    protected static array $systemFields = ['path', 'key', 'id', 'published', 'creationDate', 'modificationDate', 'fullpath'];

    /**
     * TODO Bc layer for bundles to support both Pimcore 10 & 11, remove with Pimcore 12
     *
     * @var string[]
     */
    private const BC_VERSION_DEPENDENT_DATABASE_COLUMNS = ['id', 'parentid', 'type', 'key', 'path', 'index', 'published',
                                                                'creationdate', 'modificationdate', 'userowner', 'usermodification',
                                                                'classid', 'childrensortby', 'classname', 'childrensortorder',
                                                                'versioncount', ];

    public function __construct(Model\User $user = null)
    {
        $this->_user = $user;
    }

    /**
     * finds all objects which hold a reference to a specific user
     *
     * @return Concrete[]
     */
    public static function getObjectsReferencingUser(int $userId): array
    {
        $userObjects = [[]];
        $classesList = new ClassDefinition\Listing();
        $classesList->setOrderKey('name');
        $classesList->setOrder('asc');

        $classesToCheck = [];
        foreach ($classesList as $class) {
            $fieldDefinitions = $class->getFieldDefinitions();
            $dataKeys = [];
            foreach ($fieldDefinitions as $tag) {
                if ($tag instanceof ClassDefinition\Data\User) {
                    $dataKeys[] = $tag->getName();
                }
            }
            if ($dataKeys) {
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

        return array_merge(...$userObjects);
    }

    public function copyRecursive(AbstractObject $target, AbstractObject $source, bool $initial = true): ?AbstractObject
    {
        // avoid recursion
        if ($initial) {
            $this->_copyRecursiveIds = [];
        }
        if (in_array($source->getId(), $this->_copyRecursiveIds)) {
            return null;
        }

        $source->getProperties();
        //load all in case of lazy loading fields
        self::loadAllObjectFields($source);

        // triggers actions before object cloning
        $event = new DataObjectEvent($source, [
            'target_element' => $target,
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, DataObjectEvents::PRE_COPY);
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
            $this->copyRecursive($new, $child, false);
        }

        $this->updateChildren($target, $new);

        // triggers actions after the complete document cloning
        $event = new DataObjectEvent($new, [
            'base_element' => $source, // the element used to make a copy
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, DataObjectEvents::POST_COPY);

        return $new;
    }

    /**
     * @return AbstractObject copied object
     */
    public function copyAsChild(AbstractObject $target, AbstractObject $source): AbstractObject
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
        Pimcore::getEventDispatcher()->dispatch($event, DataObjectEvents::PRE_COPY);
        $target = $event->getArgument('target_element');

        $new = $this->copy($source, $target);

        DataObject::setDisableDirtyDetection($isDirtyDetectionDisabled);

        $this->updateChildren($target, $new);

        // triggers actions after the complete object cloning
        $event = new DataObjectEvent($new, [
            'base_element' => $source, // the element used to make a copy
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, DataObjectEvents::POST_COPY);

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

    public function copyContents(Concrete $target, Concrete $source): Concrete
    {
        // check if the type is the same
        if (get_class($source) !== get_class($target)) {
            throw new Exception('Source and target have to be the same type');
        }

        // triggers actions before object cloning
        $event = new DataObjectEvent($source, [
            'target_element' => $target,
        ]);
        Pimcore::getEventDispatcher()->dispatch($event, DataObjectEvents::PRE_COPY);
        $target = $event->getArgument('target_element');

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
     * @internal
     */
    public static function isHelperGridColumnConfig(string $field): bool
    {
        return str_starts_with($field, '#');
    }

    /**
     * @deprecated Keeping in here to avoid bundles to require 11.3 or set a compatibility layer when bundles support both 10 & 11
     *
     * @todo remove in pimcore 12.0
     *
     * @internal
     */
    public static function gridObjectData(AbstractObject $object, array $fields = null, string $requestedLanguage = null, array $params = []): array
    {
        if (class_exists(GridData\DataObject::class)) {
            return GridData\DataObject::getData($object, $fields, $requestedLanguage, $params);
        } else {
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

                    if (str_starts_with($key, '#')) {
                        if (!$haveHelperDefinition) {
                            $helperDefinitions = self::getHelperDefinitions();
                            $haveHelperDefinition = true;
                        }
                        if (!empty($helperDefinitions[$key])) {
                            $context['fieldname'] = $key;
                            $data[$key] = self::calculateCellValue($object, $helperDefinitions, $key, $context);
                        }
                    } elseif (str_starts_with($key, '~')) {
                        $type = $keyParts[1];
                        if ($type === 'classificationstore') {
                            $data[$key] = self::getStoreValueForObject($object, $key, $requestedLanguage);
                        }
                    } elseif (count($keyParts) > 1) {
                        // brick
                        $brickType = $keyParts[0];
                        if (str_contains($brickType, '?')) {
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
                                $valueObject = self::getValueForObject($object, $key, $brickType, $brickKey, $def, $context, $brickDescriptor, $requestedLanguage);
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
                                        if (
                                            $def instanceof Model\DataObject\ClassDefinition\Data\Select
                                            && !$def->useConfiguredOptions()
                                            && $def->getOptionsProviderClass()
                                        ) {
                                            $data[$dataKey . '%options'] = $def->getOptions();
                                        }
                                    }
                                } else {
                                    $data[$dataKey] = $valueObject->value;
                                }
                            }
                        }

                        // because the key for the classification store has not a direct getter, you have to check separately if the data is inheritable
                        if (str_starts_with($key, '~') && empty($data[$key])) {
                            $type = $keyParts[1];

                            if ($type === 'classificationstore') {
                                if (!empty($inheritedData = self::getInheritedData($object, $key, $requestedLanguage))) {
                                    $data[$dataKey] = $inheritedData['value'];
                                    $data['inheritedFields'][$dataKey] = ['inherited' => $inheritedData['parent']->getId() != $object->getId(), 'objectid' => $inheritedData['parent']->getId()];
                                }
                            }
                        }
                        if ($needLocalizedPermissions) {
                            if (!$user->isAdmin()) {
                                $locale = Pimcore::getContainer()->get(LocaleServiceInterface::class)->findLocale();

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
    }

    /**
     * @return string[]|null
     *
     * @internal
     */
    public static function expandGridColumnForExport(array $helperDefinitions, string $key): ?array
    {
        $config = self::getConfigForHelperDefinition($helperDefinitions, $key);
        if (class_exists(AbstractOperator::class) && $config instanceof AbstractOperator && $config->expandLocales()) {
            return $config->getValidLanguages();
        }

        return null;
    }

    /**
     * @internal
     */
    public static function getConfigForHelperDefinition(array $helperDefinitions, string $key, array $context = []): ?ConfigElementInterface
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
            $service = Pimcore::getContainer()->get(GridColumnConfigService::class);
            if (!$service) {
                throw new AdminClassicBundleNotFoundException('Admin Bundle not found. Please install the package pimcore/admin-ui-classic-bundle.');
            }
            $config = $service->buildOutputDataConfig([$attributes], $context);

            if (!$config) {
                return null;
            }
            $config = $config[0];
            RuntimeCache::save($config, $cacheKey);
        }

        return $config;
    }

    public static function calculateCellValue(AbstractObject $object, array $helperDefinitions, string $key, array $context = []): mixed
    {
        if (!$config = static::getConfigForHelperDefinition($helperDefinitions, $key, $context)) {
            return null;
        }

        return self::useInheritedValues(true, static function () use ($object, $config) {
            $labeledValue = $config->getLabeledValue($object);
            if (!$labeledValue || !isset($labeledValue->value) || !$result = $labeledValue->value) {
                return null;
            }

            if (!empty($config->getRenderer())) {
                $classname = 'Pimcore\\Model\\DataObject\\ClassDefinition\\Data\\' . ucfirst($config->getRenderer());
                /** @var Model\DataObject\ClassDefinition\Data $rendererImpl */
                $rendererImpl = new $classname();
                if (method_exists($rendererImpl, 'getDataForGrid')) {
                    $result = $rendererImpl->getDataForGrid($result, $object, []);
                }
            }

            return $result;
        });
    }

    /**
     * @deprecated Since 11.3, please use GridData\DataObject::getHelperDefinitions() instead (requires pimcore/admin-ui-classic-bundle v1.5)
     */
    public static function getHelperDefinitions(): array
    {
        if (class_exists(GridData\DataObject::class)) {
            return GridData\DataObject::getHelperDefinitions();
        }

        trigger_deprecation(
            'pimcore/pimcore',
            '11.3.0',
            sprintf('The "%s" method is deprecated here and moved to admin-ui-classc-bundle v1.5, use "%s" instead.', __METHOD__, 'Pimcore\Bundle\AdminBundle\Service\GridData::getHelperDefinitions()')
        );

        $stack = Pimcore::getContainer()->get('request_stack');
        if ($stack->getMainRequest()?->hasSession()) {
            $session = $stack->getSession();

            return Session::useBag($session, function (AttributeBagInterface $session) {
                return $session->get('helpercolumns', []);
            }, 'pimcore_gridconfig');
        }

        return [];

    }

    public static function getLanguagePermissions(Fieldcollection\Data\AbstractData|Objectbrick\Data\AbstractData|AbstractObject $object, Model\User $user, string $type): ?array
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

    public static function getLayoutPermissions(string $classId, ?array $permissionSet = null): ?array
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

    public static function getFieldForBrickType(ClassDefinition $class, string $bricktype): int|string|null
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
     * @return stdClass value and objectid where the value comes from
     */
    private static function getValueForObject(Concrete $object, string $key, string $brickType = null, string $brickKey = null, ClassDefinition\Data $fieldDefinition = null, array $context = [], array $brickDescriptor = null, string $requestedLanguage = null): stdClass
    {
        $getter = 'get' . ucfirst($key);
        $value = null;

        try {
            $value = $object->$getter($requestedLanguage ?? AdminTool::getCurrentUser()?->getLanguage());
        } catch (Throwable) {
        }

        if (empty($value)) {
            $value = $object->$getter();
        }

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
                return self::getValueForObject($parent, $key, $brickType, $brickKey, $fieldDefinition, $context, $brickDescriptor, $requestedLanguage);
            }
        }

        $result = new stdClass();
        $result->value = $value;
        $result->objectid = $object->getId();

        return $result;
    }

    /**
     * gets store value for given object and key
     */
    private static function getStoreValueForObject(Concrete $object, string $key, ?string $requestedLanguage): mixed
    {
        $keyParts = explode('~', $key);

        if (str_starts_with($key, '~')) {
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
                    $definition = json_decode($keyConfig->getDefinition(), true);
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

    public static function hasInheritableParentObject(Concrete $object): ?Concrete
    {
        if ($object->getClass()->getAllowInherit()) {
            return $object->getNextParentForInheritance();
        }

        return null;
    }

    /**
     * call the getters of each object field, in case some of the are lazy loading and we need the data to be loaded
     */
    public static function loadAllObjectFields(AbstractObject $object): void
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

    public static function getOptionsForSelectField(string|Concrete $object, ClassDefinition\Data\Multiselect|ClassDefinition\Data\Select|string $definition): array
    {
        $options = [];

        if (!$object instanceof Concrete) {
            $object = '\\' . ltrim($object, '\\');
            $object = new $object();
        }
        $class = $object->getClass();

        if ($class) {
            if (is_string($definition)) {
                $definition = $class->getFieldDefinition($definition);
            }

            if ($definition instanceof ClassDefinition\Data\Select || $definition instanceof ClassDefinition\Data\Multiselect) {
                $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
                    $definition->getOptionsProviderClass(),
                    DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_MULTISELECT
                );

                if (!$definition->useConfiguredOptions() && $optionsProvider instanceof SelectOptionsProviderInterface) {
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
     */
    public static function getOptionsForMultiSelectField(string|Concrete $object, ClassDefinition\Data\Multiselect|ClassDefinition\Data\Select|string $fieldname): array
    {
        return self::getOptionsForSelectField($object, $fieldname);
    }

    public static function pathExists(string $path, string $type = null): bool
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
        } catch (Exception $e) {
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
     */
    public static function rewriteIds(AbstractObject $object, array $rewriteConfig, array $params = []): AbstractObject
    {
        // rewriting elements only for snippets and pages
        if ($object instanceof Concrete) {
            $fields = $object->getClass()->getFieldDefinitions();

            foreach ($fields as $field) {
                if ($field instanceof IdRewriterInterface
                    && $field instanceof DataObject\ClassDefinition\Data) {
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
     * @return array<string, DataObject\ClassDefinition\CustomLayout>
     */
    public static function getValidLayouts(Concrete $object): array
    {
        $layoutIds = null;
        $user = AdminTool::getCurrentUser();

        $resultList = [];
        $isMainAllowed = $user->getAdmin();

        $permissionSet = $object->getPermissions('layouts', $user);
        $layoutPermissions = self::getLayoutPermissions($object->getClassId(), $permissionSet);
        if (!$layoutPermissions || isset($layoutPermissions[0])) {
            $isMainAllowed = true;
        }

        if ($isMainAllowed) {
            $main = new ClassDefinition\CustomLayout();
            $main->setId('0');
            $main->setName('Main');
            $resultList[0] = $main;
        }

        if ($user->getAdmin()) {
            $superLayout = new ClassDefinition\CustomLayout();
            $superLayout->setId('-1');
            $superLayout->setName('Main (Admin Mode)');
            $resultList[-1] = $superLayout;
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
     * @param ClassDefinition\Data[] $targetList
     *
     * @return ClassDefinition\Data[]
     */
    public static function extractFieldDefinitions(ClassDefinition\Data|ClassDefinition\Layout $layout, string $targetClass, array $targetList, bool $insideDataType): array
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

    /**
     * Calculates the super layout definition for the given object.
     */
    public static function getSuperLayoutDefinition(Concrete $object): mixed
    {
        $mainLayout = $object->getClass()->getLayoutDefinitions();
        $superLayout = unserialize(serialize($mainLayout));

        self::createSuperLayout($superLayout);

        return $superLayout;
    }

    public static function createSuperLayout(ClassDefinition\Data|ClassDefinition\Layout $layout): void
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

    private static function synchronizeCustomLayoutFieldWithMain(array $mainDefinition, ClassDefinition\Data|ClassDefinition\Layout|null &$layout): bool
    {
        if (is_null($layout)) {
            return true;
        }

        if ($layout instanceof ClassDefinition\Data) {
            $fieldname = $layout->name;
            if (empty($mainDefinition[$fieldname])) {
                return false;
            }

            if ($layout->getFieldtype() !== $mainDefinition[$fieldname]->getFieldType()) {
                $layout->adoptMainDefinition($mainDefinition[$fieldname]);
            } else {
                $layout->synchronizeWithMainDefinition($mainDefinition[$fieldname]);
            }
        }

        if (method_exists($layout, 'getChildren')) {
            $children = $layout->getChildren();
            if (is_array($children)) {
                $count = count($children);
                for ($i = $count - 1; $i >= 0; $i--) {
                    $child = $children[$i];
                    if (!self::synchronizeCustomLayoutFieldWithMain($mainDefinition, $child)) {
                        unset($children[$i]);
                    }
                    $layout->setChildren($children);
                }
            }
        }

        return true;
    }

    /**
     * Synchronizes a custom layout with its main layout
     */
    public static function synchronizeCustomLayout(ClassDefinition\CustomLayout $customLayout): void
    {
        $classId = $customLayout->getClassId();
        $class = ClassDefinition::getById($classId);
        if ($class && ($class->getModificationDate() > $customLayout->getModificationDate())) {
            $mainDefinition = $class->getFieldDefinitions();
            $customLayoutDefinition = $customLayout->getLayoutDefinitions();

            foreach (['Localizedfields', 'Block'] as $dataType) {
                $targetList = self::extractFieldDefinitions($class->getLayoutDefinitions(), '\Pimcore\Model\DataObject\ClassDefinition\Data\\' . $dataType, [], false);
                $mainDefinition = array_merge($mainDefinition, $targetList);
            }

            self::synchronizeCustomLayoutFieldWithMain($mainDefinition, $customLayoutDefinition);
            $customLayout->save();
        }
    }

    /**
     * @internal
     */
    public static function getCustomGridFieldDefinitions(string $classId, int $objectId): ?array
    {
        $object = DataObject::getById($objectId);

        $class = ClassDefinition::getById($classId);
        $mainFieldDefinition = $class->getFieldDefinitions();

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

        $mergedFieldDefinition = self::cloneDefinition($mainFieldDefinition);

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
                    if (empty($mergedFieldDefinition[$key])) {
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
    public static function cloneDefinition(mixed $definition): mixed
    {
        $deepCopy = new \DeepCopy\DeepCopy();
        $deepCopy->addFilter(new SetNullFilter(), new PropertyNameMatcher('fieldDefinitionsCache'));
        $theCopy = $deepCopy->copy($definition);

        return $theCopy;
    }

    private static function mergeFieldDefinition(array &$mergedFieldDefinition, array &$customFieldDefinitions, string $key): void
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

    private static function doFilterCustomGridFieldDefinitions(ClassDefinition\Data|ClassDefinition\Layout &$layout, array $fieldDefinitions): bool
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

    /**
     * Determines the custom layout definition (if necessary) for the given class
     *
     * @return array layout
     *
     * @internal
     */
    public static function getCustomLayoutDefinitionForGridColumnConfig(ClassDefinition $class, int $objectId): array
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
                $children = $mergedFieldDefinition['localizedfields']->getFieldDefinitions();
                if (is_array($children)) {
                    foreach ($children as $locKey => $locValue) {
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

    public static function getUniqueKey(ElementInterface $element, int $nr = 0): string
    {
        $list = new Listing();
        $list->setUnpublished(true);
        $list->setObjectTypes(DataObject::$types);
        $key = Element\Service::getValidKey($element->getKey(), 'object');
        if (!$key) {
            throw new Exception('No item key set.');
        }
        if ($nr) {
            $key .= '_'.$nr;
        }

        $parent = $element->getParent();
        if (!$parent) {
            throw new Exception('You have to set a parent Object to determine a unique Key');
        }

        if (!$element->getId()) {
            $list->setCondition('parentId = ? AND `key` = ? ', [$parent->getId(), $key]);
        } else {
            $list->setCondition('parentId = ? AND `key` = ? AND id != ? ', [$parent->getId(), $key, $element->getId()]);
        }
        $check = $list->loadIdList();
        if (!empty($check)) {
            $nr++;
            $key = self::getUniqueKey($element, $nr);
        }

        return $key;
    }

    /**
     * Enriches the layout definition before it is returned to the admin interface.
     *
     * @param Model\DataObject\ClassDefinition\Data|Model\DataObject\ClassDefinition\Layout|null $layout
     * @param array<string, mixed> $context additional contextual data
     *
     * @internal
     */
    public static function enrichLayoutDefinition(ClassDefinition\Data|ClassDefinition\Layout|null &$layout, Concrete $object = null, array $context = []): void
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
                // Send information when we have block or similar element
                if ($layout instanceof \Pimcore\Model\DataObject\ClassDefinition\Data && empty($context['subContainerType'])) {
                    $context['subContainerKey'] = $layout->getName();
                    $context['subContainerType'] = $layout->getFieldtype();
                }

                foreach ($children as $child) {
                    self::enrichLayoutDefinition($child, $object, $context);
                }
            }
        }
    }

    /**
     * @internal
     */
    public static function enrichLayoutPermissions(ClassDefinition\Data &$layout, ?array $allowedView, ?array $allowedEdit): void
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

    private static function evaluateExpression(Model\DataObject\ClassDefinition\Data\CalculatedValue $fd, Concrete $object, ?DataObject\Data\CalculatedValue $data): mixed
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
     * @param Model\DataObject\Data\CalculatedValue|null $data
     *
     * @internal
     */
    public static function getCalculatedFieldValueForEditMode(Concrete $object, array $params, ?Data\CalculatedValue $data): ?string
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

        return DataObject\Service::useInheritedValues(true, static function () use ($fd, $object, $data) {
            switch ($fd->getCalculatorType()) {
                case DataObject\ClassDefinition\Data\CalculatedValue::CALCULATOR_TYPE_CLASS:
                    $className = $fd->getCalculatorClass();
                    $calculator = Model\DataObject\ClassDefinition\Helper\CalculatorClassResolver::resolveCalculatorClass($className);
                    if (!$calculator instanceof DataObject\ClassDefinition\CalculatorClassInterface) {
                        Logger::error('Class does not exist or is not valid: ' . $className);

                        return null;
                    }

                    return $calculator->getCalculatedValueForEditMode($object, $data);

                case DataObject\ClassDefinition\Data\CalculatedValue::CALCULATOR_TYPE_EXPRESSION:

                    try {
                        return (string) self::evaluateExpression($fd, $object, $data);
                    } catch (SyntaxError $exception) {
                        return $exception->getMessage();
                    }

                default:
                    return null;
            }
        });
    }

    public static function getCalculatedFieldValue(Fieldcollection\Data\AbstractData|Objectbrick\Data\AbstractData|Concrete $object, ?Data\CalculatedValue $data): mixed
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

        if (
            $object instanceof Model\DataObject\Fieldcollection\Data\AbstractData ||
            $object instanceof Model\DataObject\Objectbrick\Data\AbstractData
        ) {
            $object = $object->getObject();
        }

        return DataObject\Service::useInheritedValues(true, static function () use ($object, $fd, $data) {
            switch ($fd->getCalculatorType()) {
                case DataObject\ClassDefinition\Data\CalculatedValue::CALCULATOR_TYPE_CLASS:
                    $className = $fd->getCalculatorClass();
                    $calculator = Model\DataObject\ClassDefinition\Helper\CalculatorClassResolver::resolveCalculatorClass($className);
                    if (!$calculator instanceof DataObject\ClassDefinition\CalculatorClassInterface) {
                        Logger::error('Class does not exist or is not valid: ' . $className);

                        return null;
                    }

                    return $calculator->compute($object, $data);

                case DataObject\ClassDefinition\Data\CalculatedValue::CALCULATOR_TYPE_EXPRESSION:

                    try {
                        return self::evaluateExpression($fd, $object, $data);
                    } catch (SyntaxError $exception) {
                        return $exception->getMessage();
                    }

                default:
                    return null;
            }
        });
    }

    public static function getSystemFields(): array
    {
        return self::$systemFields;
    }

    public static function doResetDirtyMap(Model\AbstractModel $container, ClassDefinition|ClassDefinition\Data $fd): void
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

    public static function recursiveResetDirtyMap(AbstractObject $object): void
    {
        if ($object instanceof DirtyIndicatorInterface) {
            $object->resetDirtyMap();
        }

        if ($object instanceof Concrete) {
            if (($class = $object->getClass()) !== null) {
                self::doResetDirtyMap($object, $class);
            }
        }
    }

    /**
     * @internal
     */
    public static function buildConditionPartsFromDescriptor(array $descriptor): array
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
     * @internal
     */
    public static function getCsvDataForObject(Concrete $object, string $requestedLanguage, array $fields, array $helperDefinitions, LocaleServiceInterface $localeService, string $header, bool $returnMappedFieldNames = false, array $context = []): array
    {
        $objectData = [];
        $mappedFieldnames = [];
        foreach ($fields as $field) {
            $key = $field['key'];
            if (static::isHelperGridColumnConfig($key) && $validLanguages = static::expandGridColumnForExport($helperDefinitions, $key)) {
                $currentLocale = $localeService->getLocale();
                $mappedFieldnameBase = self::mapFieldname($field, $helperDefinitions, $header);

                foreach ($validLanguages as $validLanguage) {
                    $localeService->setLocale($validLanguage);
                    $fieldData = self::getCsvFieldData($currentLocale, $key, $object, $validLanguage, $helperDefinitions);
                    $localizedFieldKey = $key . '-' . $validLanguage;
                    if (!isset($mappedFieldnames[$localizedFieldKey])) {
                        $mappedFieldnames[$localizedFieldKey] = $mappedFieldnameBase . '-' . $validLanguage;
                    }
                    $objectData[$localizedFieldKey] = $fieldData;
                }

                $localeService->setLocale($currentLocale);
            } else {
                $fieldData = self::getCsvFieldData($requestedLanguage, $key, $object, $requestedLanguage, $helperDefinitions);
                if (!isset($mappedFieldnames[$key])) {
                    $mappedFieldnames[$key] = self::mapFieldname($field, $helperDefinitions, $header);
                }

                $objectData[$key] = $fieldData;
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

        Pimcore::getEventDispatcher()->dispatch($event, DataObjectEvents::POST_CSV_ITEM_EXPORT);
        $objectData = $event->getArgument('objectData');

        return $objectData;
    }

    /**
     * @param DataObject\Listing $list
     * @param string[] $fields
     *
     * @internal
     */
    public static function getCsvData(string $requestedLanguage, LocaleServiceInterface $localeService, Listing $list, array $fields, string $header = '', bool $addTitles = true, array $context = []): array
    {
        $data = [];
        Logger::debug('objects in list:' . count($list->getObjects()));

        if (class_exists(GridData\DataObject::class)) {
            $helperDefinitions = GridData\DataObject::getHelperDefinitions();
        } else {
            $helperDefinitions = self::getHelperDefinitions();
        }

        foreach ($list->getObjects() as $object) {
            if ($fields) {
                if ($addTitles && empty($data)) {
                    $tmp = [];
                    $mapped = self::getCsvDataForObject($object, $requestedLanguage, $fields, $helperDefinitions, $localeService, $header, true, $context);
                    foreach ($mapped as $key => $value) {
                        $tmp[] = '"' . $key . '"';
                    }
                    $data[] = $tmp;
                }

                $rowData = self::getCsvDataForObject($object, $requestedLanguage, $fields, $helperDefinitions, $localeService, $header, false, $context);
                $rowData = self::escapeCsvRecord($rowData);
                $data[] = $rowData;
            }
        }

        return $data;
    }

    protected static function mapFieldname(array $field, array $helperDefinitions, string $header): string
    {
        if ($header === 'no_header') {
            return '';
        }

        $key = $field['key'];
        $title = $field['label'];
        if (str_starts_with($key, '#')) {
            if (isset($helperDefinitions[$key])) {
                if ($helperDefinitions[$key]->attributes) {
                    return $helperDefinitions[$key]->attributes->label ? $helperDefinitions[$key]->attributes->label : $title;
                }

                return $title;
            }
        } elseif (str_starts_with($key, '~')) {
            $fieldParts = explode('~', $key);
            $type = $fieldParts[1];

            if ($type == 'classificationstore') {
                $fieldname = $fieldParts[2];
                $groupKeyId = explode('-', $fieldParts[3]);
                $groupId = (int) $groupKeyId[0];
                $keyId = (int) $groupKeyId[1];

                $groupConfig = DataObject\Classificationstore\GroupConfig::getById($groupId);
                $keyConfig = DataObject\Classificationstore\KeyConfig::getById($keyId);

                $key = $fieldname . '~' . $groupConfig->getName() . '~' . $keyConfig->getName();
            }
        }

        if ($header === 'name') {
            return $key;
        }

        return $title;
    }

    /**
     * @internal
     */
    protected static function getCsvFieldData(string $fallbackLanguage, string $field, Concrete $object, string $requestedLanguage, array $helperDefinitions): string
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

            return (string) $object->$getter();
        } else {
            //check if field is standard object field
            $fieldDefinition = $object->getClass()->getFieldDefinition($field);
            if ($fieldDefinition) {
                return $fieldDefinition->getForCsvExport($object, ['language' => $requestedLanguage]);
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

                        return (string) $cellValue;
                    }
                } elseif (str_starts_with($field, '~')) {
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
                            $definition = json_decode($keyConfig->getDefinition(), true);
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

                    if (str_contains($brickType, '?')) {
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

                                    if ($value instanceof Localizedfield) {
                                        $params['language'] = $requestedLanguage;
                                    }
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

        return '';
    }

    /**
     * TODO Bc layer for bundles to support both Pimcore 10 & 11, remove with Pimcore 12
     *
     * Returns the version dependent field name for all system fields defined in $versionDependentSystemFields.
     *
     * E.g.
     * Pass o_id in Pimcore 10, get o_id
     * Pass id in Pimcore 10, get o_id
     * Pass o_id in Pimcore 11, get id
     * Pass id in Pimcore 11, get id
     */
    public static function getVersionDependentDatabaseColumnName(string $fieldName): string
    {
        $newFieldName = $fieldName;
        if (str_starts_with($newFieldName, 'o_')) {
            $newFieldName = substr($newFieldName, 2);
        }

        if (in_array(strtolower($newFieldName), self::BC_VERSION_DEPENDENT_DATABASE_COLUMNS)) {
            return $newFieldName;
        }

        return $fieldName;
    }

    /**
     * @deprecated Since 11.3, please use \Pimcore\Bundle\AdminBundle\Service\DataObject::getInheritedData() instead
     */
    protected static function getInheritedData(Concrete $object, string $key, string $requestedLanguage): array
    {
        if (!$parent = self::hasInheritableParentObject($object)) {
            return [];
        }

        if ($inheritedValue = self::getStoreValueForObject($parent, $key, $requestedLanguage)) {
            return [
                'parent' => $parent,
                'value' => $inheritedValue,
            ];
        }

        return self::getInheritedData($parent, $key, $requestedLanguage);
    }

    public static function useInheritedValues(bool $inheritValues, callable $fn, array $fnArgs = []): mixed
    {
        $backup = DataObject::getGetInheritedValues();
        DataObject::setGetInheritedValues($inheritValues);

        try {
            return $fn(...$fnArgs);
        } finally {
            DataObject::setGetInheritedValues($backup);
        }
    }
}
