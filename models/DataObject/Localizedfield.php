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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject;

use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\LazyLoadingSupportInterface;
use Pimcore\Model\Element\DirtyIndicatorInterface;
use Pimcore\Tool;

/**
 * @method Localizedfield\Dao getDao()*
 * @method void delete($deleteQuery = true, $isUpdate = true)
 * @method void load($object, $params = [])
 * @method void save($params = [])
 * @method void createUpdateTable($params = [])
 */
final class Localizedfield extends Model\AbstractModel implements
    DirtyIndicatorInterface,
    LazyLoadedFieldsInterface,
    Model\Element\ElementDumpStateInterface,
    OwnerAwareFieldInterface
{
    use Model\DataObject\Traits\OwnerAwareFieldTrait;
    use Model\DataObject\Traits\LazyLoadedRelationTrait;
    use Model\Element\Traits\DirtyIndicatorTrait;
    use Model\Element\ElementDumpStateTrait;

    /**
     * @internal
     */
    const STRICT_DISABLED = 0;

    /**
     * @internal
     */
    const STRICT_ENABLED = 1;

    /**
     * @var bool
     */
    private static bool $getFallbackValues = false;

    /**
     * @internal
     *
     * @var array
     */
    protected array $items = [];

    /**
     * @internal
     *
     * @var Concrete|null
     */
    protected ?Concrete $object = null;

    /**
     * @internal
     *
     * @var ClassDefinition|null
     */
    protected ?ClassDefinition $class = null;

    /**
     * @internal
     *
     * @var array
     */
    protected array $context = [];

    /**
     * @internal
     *
     * @var int|null
     */
    protected ?int $objectId = null;

    /**
     * @var bool
     */
    private static bool $strictMode = false;

    /**
     * list of dirty languages. if null then no language is dirty. if empty array then all languages are dirty
     *
     * @internal
     *
     * @var array|null
     */
    protected ?array $o_dirtyLanguages = null;

    /**
     * @internal
     *
     * @var bool
     */
    protected bool $_loadedAllLazyData = false;

    /**
     * @param bool $getFallbackValues
     */
    public static function setGetFallbackValues(bool $getFallbackValues): void
    {
        self::$getFallbackValues = $getFallbackValues;
    }

    /**
     * @return bool
     */
    public static function getGetFallbackValues(): bool
    {
        return self::$getFallbackValues;
    }

    /**
     * @return bool
     */
    public static function isStrictMode(): bool
    {
        return self::$strictMode;
    }

    /**
     * @param bool $strictMode
     */
    public static function setStrictMode(bool $strictMode): void
    {
        self::$strictMode = $strictMode;
    }

    /**
     * @return bool
     */
    public static function doGetFallbackValues(): bool
    {
        return self::$getFallbackValues;
    }

    /**
     * @param array|null $items
     */
    public function __construct(array $items = null)
    {
        if ($items) {
            $this->setItems($items);
        }
        $this->markFieldDirty('_self');
        $this->markAllLanguagesAsDirty();
    }

    /**
     * @param mixed $item
     */
    public function addItem($item)
    {
        $this->items[] = $item;
        $this->markFieldDirty('_self');
        $this->markAllLanguagesAsDirty();
    }

    /**
     * @param array $items
     *
     * @return $this
     */
    public function setItems(array $items)
    {
        $this->items = $items;
        $this->markFieldDirty('_self');
        $this->markAllLanguagesAsDirty();

        return $this;
    }

    /**
     * @internal
     */
    public function loadLazyData(): void
    {
        $this->getInternalData(true);
    }

    /**
     * Note: this is for pimcore/pimcore use only.
     *
     * @internal
     *
     * @param bool $loadLazyFields
     *
     * @return array
     */
    public function getInternalData(bool $loadLazyFields = false): array
    {
        $loadLazyFieldNames = $this->getLazyLoadedFieldNames();

        if ($loadLazyFields && !empty($loadLazyFieldNames) && !$this->_loadedAllLazyData) {
            $isDirtyDetectionDisabled = DataObject::isDirtyDetectionDisabled();
            DataObject::disableDirtyDetection();

            foreach ($loadLazyFieldNames as $name) {
                foreach (Tool::getValidLanguages() as $language) {
                    $fieldDefinition = $this->getFieldDefinition($name, $this->getContext());
                    $this->loadLazyField($fieldDefinition, $name, $language);
                }
            }

            DataObject::setDisableDirtyDetection($isDirtyDetectionDisabled);
            $this->_loadedAllLazyData = true;
        }

        foreach ($this->getFieldDefinitions($this->getContext(), ['suppressEnrichment' => true]) as $fieldDefinition) {
            if ($fieldDefinition instanceof Model\DataObject\ClassDefinition\Data\CalculatedValue) {
                foreach (Tool::getValidLanguages() as $language) {
                    $this->setLocalizedValue($fieldDefinition->getName(), null, $language, false);
                }
            }
        }

        return $this->items;
    }

    /**
     * @param Concrete|null $object
     * @param bool $markAsDirty
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setObject(?Concrete $object, bool $markAsDirty = true)
    {
        if ($markAsDirty) {
            $this->markAllLanguagesAsDirty();
        }
        $this->object = $object;
        $this->objectId = $object ? $object->getId() : null;
        $this->setClass($object ? $object->getClass() : null);

        return $this;
    }

    /**
     * @return Concrete|null
     */
    public function getObject(): ?Concrete
    {
        if ($this->objectId && !$this->object) {
            $this->setObject(Concrete::getById($this->objectId));
        }

        return $this->object;
    }

    /**
     * @param ClassDefinition|null $class
     *
     * @return $this
     */
    public function setClass(?ClassDefinition $class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return ClassDefinition|null
     */
    public function getClass(): ?ClassDefinition
    {
        if (!$this->class && $this->getObject()) {
            $this->class = $this->getObject()->getClass();
        }

        return $this->class;
    }

    /**
     * @throws \Exception
     *
     * @param string|null $language
     *
     * @return string
     */
    public function getLanguage(string $language = null): string
    {
        if ($language) {
            return $language;
        }

        // try to get the language from the service container
        try {
            $locale = \Pimcore::getContainer()->get(LocaleServiceInterface::class)->getLocale();

            if (Tool::isValidLanguage($locale)) {
                return $locale;
            }

            if (\Pimcore::inAdmin()) {
                foreach (Tool::getValidLanguages() as $validLocale) {
                    if (str_starts_with($validLocale, $locale.'_')) {
                        return $validLocale;
                    }
                }
            }

            throw new \Exception('Not supported language');
        } catch (\Exception $e) {
            return Tool::getDefaultLanguage();
        }
    }

    /**
     * @param string $language
     *
     * @return bool
     */
    public function languageExists(string $language): bool
    {
        return array_key_exists($language, $this->items);
    }

    /**
     * @param string $name
     * @param array $context
     *
     * @return ClassDefinition\Data|null
     */
    public function getFieldDefinition(string $name, $context = [])
    {
        if (isset($context['containerType']) && $context['containerType'] === 'fieldcollection') {
            $containerKey = $context['containerKey'];
            $container = Model\DataObject\Fieldcollection\Definition::getByKey($containerKey);
        } elseif (isset($context['containerType']) && $context['containerType'] === 'objectbrick') {
            $containerKey = $context['containerKey'];
            $container = Model\DataObject\Objectbrick\Definition::getByKey($containerKey);
        } elseif (isset($context['containerType']) && $context['containerType'] === 'block') {
            $containerKey = $context['containerKey'];
            $object = $this->getObject();
            $blockDefinition = $object->getClass()->getFieldDefinition($containerKey);
            $container = $blockDefinition;
        } else {
            $object = $this->getObject();
            $container = $object->getClass();
        }

        /** @var Model\DataObject\ClassDefinition\Data\Localizedfields|null $localizedFields */
        $localizedFields = $container->getFieldDefinition('localizedfields');

        if ($localizedFields) {
            return $localizedFields->getFieldDefinition($name);
        }

        return null;
    }

    /**
     * @param array $context
     * @param array $params
     *
     * @return ClassDefinition\Data[]
     *
     * @throws \Exception
     */
    protected function getFieldDefinitions($context = [], $params = []): array
    {
        if (isset($context['containerType']) && $context['containerType'] === 'fieldcollection') {
            $containerKey = $context['containerKey'];
            $fcDef = Model\DataObject\Fieldcollection\Definition::getByKey($containerKey);
            /** @var Model\DataObject\ClassDefinition\Data\Localizedfields $container */
            $container = $fcDef->getFieldDefinition('localizedfields');
        } elseif (isset($context['containerType']) && $context['containerType'] === 'objectbrick') {
            $containerKey = $context['containerKey'];
            $brickDef = Model\DataObject\Objectbrick\Definition::getByKey($containerKey);
            /** @var Model\DataObject\ClassDefinition\Data\Localizedfields $container */
            $container = $brickDef->getFieldDefinition('localizedfields');
        } elseif (isset($context['containerType']) && $context['containerType'] === 'block') {
            $containerKey = $context['containerKey'];
            $object = $this->getObject();
            /** @var Model\DataObject\ClassDefinition\Data\Block $block */
            $block = $object->getClass()->getFieldDefinition($containerKey);
            /** @var Model\DataObject\ClassDefinition\Data\Localizedfields $container */
            $container = $block->getFieldDefinition('localizedfields');
        } else {
            $class = $this->getClass();
            /** @var Model\DataObject\ClassDefinition\Data\Localizedfields $container */
            $container = $class->getFieldDefinition('localizedfields');
        }

        return $container->getFieldDefinitions($params);
    }

    /**
     * @param ClassDefinition\Data $fieldDefinition
     * @param string $name
     * @param string $language
     *
     * @internal
     */
    private function loadLazyField(Model\DataObject\ClassDefinition\Data $fieldDefinition, string $name, string $language): void
    {
        $lazyKey = $this->buildLazyKey($name, $language);
        if (!$this->isLazyKeyLoaded($lazyKey) && $fieldDefinition instanceof Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface) {
            $params['language'] = $language;
            $params['object'] = $this->getObject();
            $params['context'] = $this->getContext();
            $params['owner'] = $this;
            $params['fieldname'] = $name;

            $isDirtyDetectionDisabled = DataObject::isDirtyDetectionDisabled();
            DataObject::disableDirtyDetection();

            $data = $fieldDefinition->load($this, $params);

            if ($data === 0 || !empty($data)) {
                $this->setLocalizedValue($name, $data, $language, false);
            }

            DataObject::setDisableDirtyDetection($isDirtyDetectionDisabled);

            $this->markLazyKeyAsLoaded($lazyKey);
        }
    }

    /**
     * @param string $name
     * @param string|null $language
     * @param bool $ignoreFallbackLanguage
     *
     * @return mixed
     */
    public function getLocalizedValue(string $name, string $language = null, bool $ignoreFallbackLanguage = false)
    {
        $data = null;
        $language = $this->getLanguage($language);

        $context = $this->getContext();
        $fieldDefinition = $this->getFieldDefinition($name, $context);

        if ($fieldDefinition instanceof Model\DataObject\ClassDefinition\Data\CalculatedValue) {
            $valueData = new Model\DataObject\Data\CalculatedValue($fieldDefinition->getName());
            $valueData->setContextualData('localizedfield', 'localizedfields', null, $language, null, null, $fieldDefinition);
            $data = Service::getCalculatedFieldValue($this->getObject(), $valueData);

            return $data;
        }

        if ($fieldDefinition instanceof LazyLoadingSupportInterface && $fieldDefinition->getLazyLoading()) {
            $this->loadLazyField($fieldDefinition, $name, $language);
        }

        if ($this->languageExists($language)) {
            if (array_key_exists($name, $this->items[$language])) {
                $data = $this->items[$language][$name];
            }
        }

        // check for inherited value
        $doGetInheritedValues = DataObject::doGetInheritedValues();

        $allowInheritance = $fieldDefinition->supportsInheritance();
        if (isset($context['containerType']) && ($context['containerType'] === 'block' || $context['containerType'] === 'fieldcollection')) {
            $allowInheritance = false;
        }

        if ($fieldDefinition->isEmpty($data) && $doGetInheritedValues && $allowInheritance && $this->getObject() instanceof Concrete) {
            $object = $this->getObject();
            $class = $object->getClass();
            $allowInherit = $class->getAllowInherit();

            if ($allowInherit) {
                if ($object->getParent() instanceof AbstractObject) {
                    $parent = $object->getParent();
                    while ($parent && $parent->getType() == AbstractObject::OBJECT_TYPE_FOLDER) {
                        $parent = $parent->getParent();
                    }

                    if ($parent && ($parent->getType() == AbstractObject::OBJECT_TYPE_OBJECT || $parent->getType() == AbstractObject::OBJECT_TYPE_VARIANT)) {
                        /** @var Concrete $parent */
                        if ($parent->getClassId() == $object->getClassId()) {
                            $method = 'getLocalizedfields';

                            $parentContainer = $parent;

                            if (isset($context['containerType']) && $context['containerType'] === 'objectbrick') {
                                if (!empty($context['fieldname'])) {
                                    $brickContainerGetter = 'get' . ucfirst($context['fieldname']);
                                    $brickContainer = $parent->$brickContainerGetter();
                                    $brickGetter = 'get' . $context['containerKey'];
                                    $brickData = $brickContainer->$brickGetter();
                                    $parentContainer = $brickData;
                                }
                            }

                            if (method_exists($parentContainer, $method)) {
                                $localizedFields = $parentContainer->getLocalizedFields();
                                if ($localizedFields instanceof Localizedfield) {
                                    if ($localizedFields->getObject()->getId() != $this->getObject()->getId()) {
                                        $localizedFields->setContext($this->getContext());
                                        $data = $localizedFields->getLocalizedValue($name, $language, true);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // check for fallback value
        if ($fieldDefinition->isEmpty($data) && !$ignoreFallbackLanguage && self::doGetFallbackValues()) {
            foreach (Tool::getFallbackLanguagesFor($language) as $l) {
                // fallback-language may not exist yet for lazy-loaded field (relation)
                if ($this->languageExists($l) || ($fieldDefinition instanceof LazyLoadingSupportInterface && $fieldDefinition->getLazyLoading())) {
                    if ($data = $this->getLocalizedValue($name, $l)) {
                        break;
                    }
                }
            }
        }

        if ($fieldDefinition && method_exists($fieldDefinition, 'preGetData')) {
            $data = $fieldDefinition->preGetData(
                $this,
                [
                    'data' => $data,
                    'language' => $language,
                    'name' => $name,
                ]
            );
        }

        return $data;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param string|null $language
     * @param bool $markFieldAsDirty
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setLocalizedValue(string $name, $value, string $language = null, bool $markFieldAsDirty = true)
    {
        if ($markFieldAsDirty) {
            $this->markFieldDirty('_self');
        }

        if (self::$strictMode) {
            if (!$language || !in_array($language, Tool::getValidLanguages())) {
                throw new \Exception('Language '.$language.' not accepted in strict mode');
            }
        }

        $language = $this->getLanguage($language);
        if (!$this->languageExists($language)) {
            $this->items[$language] = [];
            $this->markLanguageAsDirty($language);
        }

        $contextInfo = $this->getContext();
        if (isset($contextInfo['containerType']) && $contextInfo['containerType'] === 'block') {
            $classId = $contextInfo['classId'];
            $containerDefinition = ClassDefinition::getById($classId);
            /** @var Model\DataObject\ClassDefinition\Data\Block $blockDefinition */
            $blockDefinition = $containerDefinition->getFieldDefinition($contextInfo['fieldname']);

            /** @var Model\DataObject\ClassDefinition\Data\Localizedfields $fieldDefinition */
            $fieldDefinition = $blockDefinition->getFieldDefinition('localizedfields');
        } else {
            if (isset($contextInfo['containerType']) && $contextInfo['containerType'] === 'fieldcollection') {
                $containerKey = $contextInfo['containerKey'];
                $containerDefinition = Fieldcollection\Definition::getByKey($containerKey);
            } elseif (isset($contextInfo['containerType']) && $contextInfo['containerType'] === 'objectbrick') {
                $containerKey = $contextInfo['containerKey'];
                $containerDefinition = Model\DataObject\Objectbrick\Definition::getByKey($containerKey);
            } else {
                $containerDefinition = $this->getObject()->getClass();
            }

            /** @var Model\DataObject\ClassDefinition\Data\Localizedfields $localizedFieldDefinition */
            $localizedFieldDefinition = $containerDefinition->getFieldDefinition('localizedfields');
            $fieldDefinition = $localizedFieldDefinition->getFieldDefinition($name, ['object' => $this->getObject()]);
        }

        // if a lazy loaded field hasn't been loaded we cannot rely on the dirty check
        // note that preSetData will just overwrite it with the new data and mark it as loaded
        $forceLanguageDirty = false;
        $isLazyLoadedField = $fieldDefinition instanceof LazyLoadingSupportInterface && $fieldDefinition->getLazyLoading();
        $lazyKey = $this->buildLazyKey($name, $language);

        if ($isLazyLoadedField) {
            if (!$this->isLazyKeyLoaded($lazyKey)) {
                $forceLanguageDirty = true;
            }
        }

        if (method_exists($fieldDefinition, 'preSetData')) {
            $value = $fieldDefinition->preSetData(
                $this,
                $value,
                [
                    'language' => $language,
                    'name' => $name,
                ]
            );
        }

        $isEqual = false;

        if ($fieldDefinition instanceof Model\DataObject\ClassDefinition\Data\EqualComparisonInterface) {
            $isEqual = $fieldDefinition->isEqual($this->items[$language][$name] ?? null, $value);
        }

        if ($markFieldAsDirty && ($forceLanguageDirty || !$isEqual)) {
            $this->markLanguageAsDirty($language);
        }
        $this->items[$language][$name] = $value;

        if ($isLazyLoadedField) {
            $this->markLazyKeyAsLoaded($lazyKey);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllLazyKeysMarkedAsLoaded(): bool
    {
        $object = $this->getObject();
        if ($object instanceof Concrete) {
            return $this->getObject()->isAllLazyKeysMarkedAsLoaded();
        }

        return true;
    }

    /**
     * @return array
     */
    public function __sleep(): array
    {
        if (!$this->isInDumpState()) {
            /**
             * Remove all lazy loaded fields if item gets serialized for the cache (not for versions)
             * This is actually not perfect, but currently we don't have an alternative
             */
            $lazyLoadedFields = $this->getLazyLoadedFieldNames();
            foreach ($lazyLoadedFields as $fieldName) {
                foreach (Tool::getValidLanguages() as $language) {
                    unset($this->items[$language][$fieldName]);

                    $lazyKey = $this->buildLazyKey($fieldName, $language);
                    $this->unmarkLazyKeyAsLoaded($lazyKey);
                }
            }
        }

        return ['items', 'context', 'objectId'];
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array $context
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * @internal
     *
     * @return bool
     */
    public function hasDirtyLanguages(): bool
    {
        if (DataObject::isDirtyDetectionDisabled()) {
            return true;
        }

        return is_array($this->o_dirtyLanguages) && count($this->o_dirtyLanguages) > 0;
    }

    /**
     * @internal
     *
     * @param string $language
     *
     * @return bool
     */
    public function isLanguageDirty(string $language): bool
    {
        if (DataObject::isDirtyDetectionDisabled()) {
            return true;
        }

        if (is_array($this->o_dirtyLanguages)) {
            if (count($this->o_dirtyLanguages) == 0) {
                return true;
            }
            if (isset($this->o_dirtyLanguages[$language])) {
                return $this->o_dirtyLanguages[$language];
            }
        }

        return false;
    }

    /**
     * @internal
     */
    public function resetLanguageDirtyMap(): void
    {
        $this->o_dirtyLanguages = null;
    }

    /**
     * @internal
     *
     * @return array|null
     */
    public function getDirtyLanguages(): ?array
    {
        return $this->o_dirtyLanguages;
    }

    /**
     * @internal
     */
    public function markAllLanguagesAsDirty(): void
    {
        $this->o_dirtyLanguages = [];
    }

    /**
     * @internal
     *
     * @return bool
     */
    public function allLanguagesAreDirty(): bool
    {
        if (DataObject::isDirtyDetectionDisabled()) {
            return true;
        }

        return is_array($this->o_dirtyLanguages) && count($this->o_dirtyLanguages) === 0;
    }

    /**
     * @internal
     *
     * @param string $language
     * @param bool $dirty
     */
    public function markLanguageAsDirty(string $language, bool $dirty = true): void
    {
        if (DataObject::isDirtyDetectionDisabled()) {
            return;
        }

        if (!is_array($this->o_dirtyLanguages) && $dirty) {
            $this->o_dirtyLanguages = [];
        }

        if ($dirty) {
            $this->o_dirtyLanguages[$language] = true;
        }

        if (!$this->o_dirtyLanguages) {
            $this->o_dirtyLanguages = null;
        }
    }

    /**
     * @internal
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function getLazyLoadedFieldNames(): array
    {
        $lazyLoadedFieldNames = [];

        if (isset($this->context['containerType']) && $this->context['containerType'] === 'block') {
            // if localized field is embedded in a block element there is no lazy loading. Maybe we can
            // prevent this already in the class definition editor
            return $lazyLoadedFieldNames;
        }

        $fields = $this->getFieldDefinitions($this->getContext(), ['suppressEnrichment' => true]);
        foreach ($fields as $field) {
            if ($field instanceof LazyLoadingSupportInterface && $field->getLazyLoading()) {
                $lazyLoadedFieldNames[] = $field->getName();
            }
        }

        return $lazyLoadedFieldNames;
    }

    /**
     * @return int|null
     */
    public function getObjectId(): ?int
    {
        return $this->objectId;
    }
}
