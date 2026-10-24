<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject;

use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\PropertyNameMatcher;
use Exception;
use Pimcore;
use Pimcore\Db;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\IdRewriterInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\LayoutDefinitionEnrichmentInterface;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Helper\WysiwygPersistingScope;
use Pimcore\Model\Element;
use Pimcore\Model\Element\DirtyIndicatorInterface;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Pimcore\Tool;
use Pimcore\Tool\Admin as AdminTool;
use Pimcore\Tool\Session;
use stdClass;
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

    public function __construct(?Model\User $user = null)
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

        try {
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
        } finally {
            DataObject::setDisableDirtyDetection($isDirtyDetectionDisabled);
        }

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

        // reading block fields outside admin mode would write the frontend-rendered Wysiwyg text back into the copy
        WysiwygPersistingScope::run(fn () => $this->keepNonEditableLanguages($new, $target));

        $new->save();

        $target = Concrete::getById($new->getId());

        return $target;
    }

    /**
     * Restores the localized values of the languages the user is not allowed to edit (lEdit workspace permission)
     * from the target, in the object itself as well as in its object bricks, field collections and blocks.
     */
    private function keepNonEditableLanguages(Concrete $new, Concrete $target): void
    {
        if (!$this->_user || $this->_user->isAdmin()) {
            return;
        }

        $editableLanguages = self::getLanguagePermissions($target, $this->_user, 'lEdit');
        if ($editableLanguages === null) {
            return;
        }

        // like the editors: for localized fields a list with only the language-independent 'default' means
        // "no restriction", while the classification store still limits itself to that one value
        $restrictLocalizedfields = array_keys($editableLanguages) !== ['default'];

        self::loadAllObjectFields($target);

        foreach ($new->getClass()->getFieldDefinitions() as $name => $definition) {
            $newValue = $new->get($name);
            $targetValue = $target->get($name);

            if ($newValue instanceof DataObject\Classificationstore) {
                // like the editors, only localized stores are limited; a store without languages only holds 'default'
                if ($definition instanceof ClassDefinition\Data\Classificationstore && $definition->isLocalized()) {
                    $this->restoreClassificationstoreLanguages($newValue, $targetValue, $editableLanguages);
                }
            } elseif (!$restrictLocalizedfields) {
                continue;
            } elseif ($definition instanceof ClassDefinition\Data\Localizedfields) {
                $this->restoreLocalizedfields($newValue, $targetValue, $editableLanguages);
            } elseif ($definition instanceof ClassDefinition\Data\Block) {
                $this->restoreBlockLanguages($definition, $newValue, $targetValue, $editableLanguages);
            } elseif ($newValue instanceof DataObject\Objectbrick) {
                $this->restoreBrickLanguages($newValue, $targetValue, $editableLanguages);
            } elseif ($newValue instanceof DataObject\Fieldcollection) {
                $this->restoreFieldcollectionLanguages($newValue, $targetValue, $editableLanguages);
            }
        }
    }

    /**
     * The values of a localized classification store are keyed by group, key and language ('default' is the
     * language-independent value, which is only editable if the user's list contains it).
     *
     * @param array<string, int> $editableLanguages
     */
    private function restoreClassificationstoreLanguages(
        DataObject\Classificationstore $new,
        mixed $target,
        array $editableLanguages
    ): void {
        // the object is saved with inheritance off, so own values must not be mixed with the parent's
        [$items, $activeGroups] = self::useInheritedValues(false, static fn (): array => [$new->getItems(), $new->getActiveGroups()]);
        $targetItems = $target instanceof DataObject\Classificationstore
            ? self::useInheritedValues(false, static fn (): array => $target->getItems())
            : [];

        foreach ($items as $groupId => $group) {
            foreach ($group as $keyId => $values) {
                $items[$groupId][$keyId] = array_intersect_key($values, $editableLanguages);
            }
        }

        foreach ($targetItems as $groupId => $group) {
            foreach ($group as $keyId => $values) {
                $kept = array_diff_key($values, $editableLanguages);
                if ($kept) {
                    $items[$groupId][$keyId] = ($items[$groupId][$keyId] ?? []) + $kept;
                    $activeGroups[$groupId] = true;
                    if (!$new->getGroupCollectionMapping($groupId) && $target instanceof DataObject\Classificationstore) {
                        $new->setGroupCollectionMapping($groupId, $target->getGroupCollectionMapping($groupId));
                    }
                }
            }
        }

        foreach ($items as $groupId => $group) {
            $items[$groupId] = array_filter($group);
        }

        $new->setItems(array_filter($items));
        $new->setActiveGroups($activeGroups);
    }

    /**
     * @param array<string, int> $editableLanguages
     */
    private function restoreBrickLanguages(DataObject\Objectbrick $new, mixed $target, array $editableLanguages): void
    {
        foreach ($new->getItems() as $brick) {
            $targetBrick = $target instanceof DataObject\Objectbrick ? $target->get($brick->getType()) : null;
            $this->restoreLocalizedfields(
                $this->getLocalizedfieldsOf($brick),
                $targetBrick ? $this->getLocalizedfieldsOf($targetBrick) : null,
                $editableLanguages
            );
            $this->restoreContainerBlockLanguages($brick, $targetBrick, $editableLanguages);
        }
    }

    /**
     * Blocks inside an object brick or field collection item.
     *
     * @param array<string, int> $editableLanguages
     */
    private function restoreContainerBlockLanguages(
        Objectbrick\Data\AbstractData|Fieldcollection\Data\AbstractData $new,
        Objectbrick\Data\AbstractData|Fieldcollection\Data\AbstractData|null $target,
        array $editableLanguages
    ): void {
        foreach ($new->getDefinition()->getFieldDefinitions() as $name => $definition) {
            if ($definition instanceof ClassDefinition\Data\Block) {
                $this->restoreBlockLanguages($definition, $new->get($name), $target?->get($name), $editableLanguages);
            }
        }
    }

    /**
     * Only blocks with a localizedfields child hold languages. Block items are matched by index.
     *
     * @param array<string, int> $editableLanguages
     */
    private function restoreBlockLanguages(ClassDefinition\Data\Block $definition, mixed $new, mixed $target, array $editableLanguages): void
    {
        if (!$definition->getFieldDefinition('localizedfields') || !is_array($new)) {
            return;
        }

        foreach ($new as $index => $item) {
            $element = $item['localizedfields'] ?? null;
            $targetElement = is_array($target) ? ($target[$index]['localizedfields'] ?? null) : null;
            if ($element instanceof DataObject\Data\BlockElement) {
                $this->restoreLocalizedfields(
                    $element->getData(),
                    $targetElement instanceof DataObject\Data\BlockElement ? $targetElement->getData() : null,
                    $editableLanguages
                );
            }
        }
    }

    /**
     * Items are matched by index and type.
     *
     * @param DataObject\Fieldcollection<DataObject\Fieldcollection\Data\AbstractData> $new
     * @param array<string, int> $editableLanguages
     */
    private function restoreFieldcollectionLanguages(DataObject\Fieldcollection $new, mixed $target, array $editableLanguages): void
    {
        $targetItems = $target instanceof DataObject\Fieldcollection ? $target->getItems() : [];
        foreach ($new->getItems() as $index => $item) {
            $targetItem = $targetItems[$index] ?? null;
            if ($targetItem?->getType() !== $item->getType()) {
                $targetItem = null;
            }
            $this->restoreLocalizedfields(
                $this->getLocalizedfieldsOf($item),
                $targetItem ? $this->getLocalizedfieldsOf($targetItem) : null,
                $editableLanguages
            );
            $this->restoreContainerBlockLanguages($item, $targetItem, $editableLanguages);
        }
    }

    /**
     * The getter only exists on generated brick and field collection classes that define localized fields.
     */
    private function getLocalizedfieldsOf(Objectbrick\Data\AbstractData|Fieldcollection\Data\AbstractData $container): ?DataObject\Localizedfield
    {
        return method_exists($container, 'getLocalizedfields') ? $container->getLocalizedfields() : null;
    }

    /**
     * @param array<string, int> $editableLanguages
     */
    private function restoreLocalizedfields(mixed $new, mixed $target, array $editableLanguages): void
    {
        if (!$new instanceof DataObject\Localizedfield) {
            return;
        }

        $items = $new->getItems();
        $targetItems = $target instanceof DataObject\Localizedfield ? $target->getItems() : [];

        foreach (array_unique(array_merge(array_keys($items), array_keys($targetItems))) as $language) {
            if (isset($editableLanguages[$language])) {
                continue;
            }

            if (isset($targetItems[$language])) {
                $items[$language] = $targetItems[$language];
            } else {
                unset($items[$language]);
            }
        }

        $new->setItems($items);
    }

    /**
     * @internal
     */
    public static function isHelperGridColumnConfig(string $field): bool
    {
        return str_starts_with($field, '#');
    }

    public static function getHelperDefinitions(): array
    {
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
                $languageAllowed = [];

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
                $layoutPermissions = [];

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
    private static function getValueForObject(Concrete $object, string $key, ?string $brickType = null, ?string $brickKey = null, ?ClassDefinition\Data $fieldDefinition = null, array $context = [], ?array $brickDescriptor = null, ?string $requestedLanguage = null): stdClass
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
            // the values read here are copied and stored, wysiwyg links to unpublished elements must survive that
            WysiwygPersistingScope::run(static function () use ($object): void {
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
            });
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

    public static function pathExists(string $path, ?string $type = null): bool
    {
        if (!$path) {
            return false;
        }

        $path = Element\Service::correctPath($path);

        try {
            $object = new DataObject();

            if (self::isValidPath($path, 'object')) {
                Element\Service::getByPathWithNfcFallback(
                    fn (string $candidate) => $object->getDao()->getByPath($candidate),
                    $path
                );

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

            // the rewritten values are stored, wysiwyg links to unpublished elements must survive that
            WysiwygPersistingScope::run(static function () use ($object, $fields, $rewriteConfig): void {
                foreach ($fields as $field) {
                    if ($field instanceof IdRewriterInterface) {
                        $setter = 'set' . ucfirst($field->getName());
                        if (method_exists($object, $setter)) { // check for non-owner-objects
                            $object->$setter($field->rewriteIds($object, $rewriteConfig));
                        }
                    }
                }
            });
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
            $resultList[$customLayout->getId()] = $customLayout;
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
        $superLayout = self::cloneDefinition($mainLayout);

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
    public static function getCustomGridFieldDefinitions(string $classId, int $objectId, ?User $user = null): ?array
    {
        $object = DataObject::getById($objectId);

        $class = ClassDefinition::getById($classId);
        $mainFieldDefinition = $class->getFieldDefinitions();

        if (!$object) {
            return null;
        }

        $user = self::getUser($user);

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
                // layout id 0 is the class' main layout, i.e. the user is not restricted to custom
                // layouts - same as in getValidLayouts(). The grid must not be reduced to the
                // intersection of the remaining custom layouts then.
                if (isset($allowedLayoutIds[0])) {
                    continue;
                }

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
                        self::resetContainerFieldDefinitions($locValue);
                    }
                    $def->setChildren($mergedLocalizedFieldDefinitions);
                } else {
                    $def->setInvisible(false);
                    $def->setNotEditable(false);
                    self::resetContainerFieldDefinitions($def);
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
                        self::mergeContainerFieldDefinitions($mergedLocalizedFieldDefinitions[$locKey] ?? null, $customLocalizedFieldDefinitions[$locKey] ?? null);
                    }
                    $mergedFieldDefinition[$key]->setChildren($mergedLocalizedFieldDefinitions);
                } else {
                    self::mergeFieldDefinition($mergedFieldDefinition, $customFieldDefinitions, $key);
                    self::mergeContainerFieldDefinitions($mergedFieldDefinition[$key] ?? null, $def);
                }
            }
        }

        return $mergedFieldDefinition;
    }

    /**
     * The children of a Block (or a Localizedfields inside it) start out as visible and editable,
     * the custom layouts restrict them afterwards
     */
    private static function resetContainerFieldDefinitions(ClassDefinition\Data $def): void
    {
        if (!$def instanceof ClassDefinition\Data\Block && !$def instanceof ClassDefinition\Data\Localizedfields) {
            return;
        }

        foreach ($def->getFieldDefinitions(['suppressEnrichment' => true]) as $child) {
            $child->setInvisible(false);
            $child->setNotEditable(false);
            self::resetContainerFieldDefinitions($child);
        }
    }

    /**
     * Applies the children of a Block (or a Localizedfields inside it) in a custom layout to the children of the merged one
     */
    private static function mergeContainerFieldDefinitions(?ClassDefinition\Data $mergedDef, ?ClassDefinition\Data $customDef): void
    {
        if (
            !($mergedDef instanceof ClassDefinition\Data\Block && $customDef instanceof ClassDefinition\Data\Block)
            && !($mergedDef instanceof ClassDefinition\Data\Localizedfields && $customDef instanceof ClassDefinition\Data\Localizedfields)
        ) {
            return;
        }

        $customFieldDefinitions = $customDef->getFieldDefinitions(['suppressEnrichment' => true]);
        $mergedFieldDefinitions = $mergedDef->getFieldDefinitions(['suppressEnrichment' => true]);

        foreach (array_keys($mergedFieldDefinitions) as $key) {
            self::mergeFieldDefinition($mergedFieldDefinitions, $customFieldDefinitions, (string) $key);
            self::mergeContainerFieldDefinitions($mergedFieldDefinitions[$key] ?? null, $customFieldDefinitions[$key] ?? null);
        }

        // the children already hold the fields of all aggregated sections, a retained reference would add the unfiltered ones again
        $mergedDef->setReferencedFields([]);
        $mergedDef->setChildren(array_values($mergedFieldDefinitions));
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

            if (
                ($layout instanceof ClassDefinition\Data\Block && $fieldDefinitions[$name] instanceof ClassDefinition\Data\Block)
                || ($layout instanceof ClassDefinition\Data\Localizedfields && $fieldDefinitions[$name] instanceof ClassDefinition\Data\Localizedfields)
            ) {
                // the children of a Block or a Localizedfields inside it are no top-level fields,
                // they are checked against the container's own definitions
                $fieldDefinitions = $fieldDefinitions[$name]->getFieldDefinitions(['suppressEnrichment' => true]);
            }
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
    public static function getCustomLayoutDefinitionForGridColumnConfig(
        ClassDefinition $class,
        int $objectId,
        ?User $user = null
    ): array {
        // work on a copy, the class definition is shared and callers modify the returned layout
        $layoutDefinitions = self::cloneDefinition($class->getLayoutDefinitions());

        $result = [
            'layoutDefinition' => $layoutDefinitions,
        ];

        if (!$objectId) {
            return $result;
        }

        $user = self::getUser($user);

        if ($user->isAdmin()) {
            return $result;
        }

        $mergedFieldDefinition = self::getCustomGridFieldDefinitions($class->getId(), $objectId, $user);
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
    public static function enrichLayoutDefinition(
        ClassDefinition\Data|ClassDefinition\Layout|null &$layout,
        ?Concrete $object = null,
        array $context = [],
        ?User $user = null
    ): void {
        if (is_null($layout)) {
            return;
        }

        // Some callers (e.g. grid column configuration) cannot supply a Concrete $object - field
        // enrichment requires that - but can still provide a permission subject via context['object']
        // (a Folder or Concrete). Capture it before it gets overwritten below.
        $contextObject = $context['object'] ?? null;
        $permissionSubject = $object ?? ($contextObject instanceof AbstractObject ? $contextObject : null);

        $context['object'] = $object;

        if ($layout instanceof LayoutDefinitionEnrichmentInterface) {
            $layout->enrichLayoutDefinition($object, $context);
        }

        if ($layout instanceof Model\DataObject\ClassDefinition\Data\Localizedfields || $layout instanceof Model\DataObject\ClassDefinition\Data\Classificationstore && $layout->localized === true) {
            $user = self::getUser($user);
            if (!$user->isAdmin() && $permissionSubject) {
                $allowedView = self::getLanguagePermissions($permissionSubject, $user, 'lView');
                $allowedEdit = self::getLanguagePermissions($permissionSubject, $user, 'lEdit');
                self::enrichLayoutPermissions($layout, $allowedView, $allowedEdit, $user);
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
                    self::enrichLayoutDefinition($child, $object, $context, $user);
                }
            }
        }
    }

    /**
     * @internal
     */
    public static function enrichLayoutPermissions(
        ClassDefinition\Data &$layout,
        ?array $allowedView,
        ?array $allowedEdit,
        ?User $user = null
    ): void {
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
                            self::getUser($user),
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
                            self::getUser($user),
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
                        self::enrichLayoutPermissions($child, $allowedView, $allowedEdit, self::getUser($user));
                    }
                }
            }
        }
    }

    private static function evaluateExpression(Model\DataObject\ClassDefinition\Data\CalculatedValue $fd, Concrete $object, ?DataObject\Data\CalculatedValue $data): mixed
    {
        // TODO refactor in a future PR to allow these to be injected
        $container = Pimcore::getContainer();
        $expressionLanguage = $container->get('pimcore.calculated_value.expression_language');

        return $expressionLanguage->evaluate(
            $fd->getCalculatorExpression(),
            [
                'object' => $object,
                'data' => $data,
            ]
        );
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

        $data->setCalculatorData($fd->getCalculatorData());

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

        $data->setCalculatorData($fd->getCalculatorData());

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

        // Resetting dirty flags only needs the field names, so skip the enrichment that would run
        // dynamic options providers (without an object context) for every field.
        $fieldDefinitions = $fd->getFieldDefinitions(['suppressEnrichment' => true]);

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
        $object->resetDirtyMap();

        if ($object instanceof Concrete) {
            $class = $object->getClass();
            self::doResetDirtyMap($object, $class);
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
                $conditionParts[] = $key . ' = ' . $db->quote((string)$value);
            }
        }

        return $conditionParts;
    }

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

    private static function getUser(?User $user = null): ?User
    {
        return $user ?? AdminTool::getCurrentUser();
    }
}
