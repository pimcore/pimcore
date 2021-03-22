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

use Pimcore\Model;
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
class Localizedfield extends Model\AbstractModel implements
    DirtyIndicatorInterface,
    LazyLoadedFieldsInterface,
    Model\Element\ElementDumpStateInterface,
                        OwnerAwareFieldInterface
{
    use Model\DataObject\Traits\OwnerAwareFieldTrait;

    use Model\DataObject\Traits\LazyLoadedRelationTrait;

    use Model\Element\Traits\DirtyIndicatorTrait;

    use Model\Element\ElementDumpStateTrait;

    const STRICT_DISABLED = 0;

    const STRICT_ENABLED = 1;

    /**
     * @var bool
     */
    private static $getFallbackValues = false;

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var Model\DataObject\Concrete
     */
    protected $object;

    /**
     * @var Model\DataObject\ClassDefinition
     */
    protected $class;

    /** @var array */
    protected $context;

    /** @var int */
    protected $objectId;

    /**
     * @var bool
     */
    private static $strictMode;

    /**
     * list of dirty languages. if null then no language is dirty. if empty array then all languages are dirty
     *
     * @var array|null
     */
    protected $o_dirtyLanguages;

    /**
     * @var bool
     */
    protected $_loadedAllLazyData = false;

    /**
     * @param bool $getFallbackValues
     */
    public static function setGetFallbackValues($getFallbackValues)
    {
        self::$getFallbackValues = $getFallbackValues;
    }

    /**
     * @return bool
     */
    public static function getGetFallbackValues()
    {
        return self::$getFallbackValues;
    }

    /**
     * @return bool
     */
    public static function isStrictMode()
    {
        return self::$strictMode;
    }

    /**
     * @param bool $strictMode
     */
    public static function setStrictMode($strictMode)
    {
        self::$strictMode = $strictMode;
    }

    /**
     * @return bool
     */
    public static function doGetFallbackValues()
    {
        return self::$getFallbackValues;
    }

    /**
     * @param array $items
     */
    public function __construct($items = null)
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
     * @param  array $items
     *
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;
        $this->markFieldDirty('_self');
        $this->markAllLanguagesAsDirty();

        return $this;
    }

    /**
     * @internal
     */
    public function loadLazyData()
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
    public function getInternalData($loadLazyFields = false)
    {
        $loadLazyFieldNames = $this->getLazyLoadedFieldNames();

        if ($loadLazyFields && !empty($loadLazyFieldNames) && !$this->_loadedAllLazyData) {
            $isDirtyDetectionDisabled = AbstractObject::isDirtyDetectionDisabled();
            AbstractObject::disableDirtyDetection();

            foreach ($loadLazyFieldNames as $name) {
                foreach (Tool::getValidLanguages() as $language) {
                    $fieldDefinition = $this->getFieldDefinition($name, $this->getContext());
                    $this->loadLazyField($fieldDefinition, $name, $language);
                }
            }

            AbstractObject::setDisableDirtyDetection($isDirtyDetectionDisabled);
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
     * @param Concrete $object
     * @param bool $markAsDirty
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setObject($object, $markAsDirty = true)
    {
        if ($object && !$object instanceof Concrete) {
            throw new \Exception('must be instance of object concrete');
        }
        if ($markAsDirty) {
            $this->markAllLanguagesAsDirty();
        }
        $this->object = $object;
        $this->objectId = $object ? $object->getId() : null;
        $this->setClass($object ? $object->getClass() : null);

        return $this;
    }

    /**
     * @return Concrete
     */
    public function getObject()
    {
        if ($this->objectId && !$this->object) {
            $this->setObject(Concrete::getById($this->objectId));
        }

        return $this->object;
    }

    /**
     * @param Model\DataObject\ClassDefinition $class
     *
     * @return $this
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return Model\DataObject\ClassDefinition
     */
    public function getClass()
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
    public function getLanguage($language = null)
    {
        if ($language) {
            return (string) $language;
        }

        // try to get the language from the service container
        try {
            $locale = \Pimcore::getContainer()->get('pimcore.locale')->getLocale();

            if (Tool::isValidLanguage($locale)) {
                return (string) $locale;
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
    public function languageExists($language)
    {
        return array_key_exists($language, $this->items);
    }

    /**
     * @param string $name
     * @param array $context
     *
     * @return ClassDefinition\Data|null
     */
    public function getFieldDefinition($name, $context = [])
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
    protected function getFieldDefinitions($context = [], $params = [])
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
     */
    private function loadLazyField(Model\DataObject\ClassDefinition\Data $fieldDefinition, $name, $language)
    {
        $lazyKey = $this->buildLazyKey($name, $language);
        if (!$this->isLazyKeyLoaded($lazyKey) && $fieldDefinition instanceof Model\DataObject\ClassDefinition\Data\CustomResourcePersistingInterface) {
            $params['language'] = $language;
            $params['object'] = $this->getObject();
            $params['context'] = $this->getContext();
            $params['owner'] = $this;
            $params['fieldname'] = $name;

            $isDirtyDetectionDisabled = AbstractObject::isDirtyDetectionDisabled();
            AbstractObject::disableDirtyDetection();

            $data = $fieldDefinition->load($this, $params);

            if ($data === 0 || !empty($data)) {
                $this->setLocalizedValue($name, $data, $language, false);
            }

            AbstractObject::setDisableDirtyDetection($isDirtyDetectionDisabled);

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
    public function getLocalizedValue($name, $language = null, $ignoreFallbackLanguage = false)
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
        $doGetInheritedValues = AbstractObject::doGetInheritedValues();

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
                    while ($parent && $parent->getType() == 'folder') {
                        $parent = $parent->getParent();
                    }

                    if ($parent && ($parent->getType() == 'object' || $parent->getType() == 'variant')) {
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
    public function setLocalizedValue($name, $value, $language = null, $markFieldAsDirty = true)
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
        $isLazyLoadedField = ($fieldDefinition instanceof LazyLoadingSupportInterface || method_exists($fieldDefinition, 'getLazyLoading'))
                                    && $fieldDefinition->getLazyLoading();
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

        if ($markFieldAsDirty && ($forceLanguageDirty || !$fieldDefinition->isEqual($this->items[$language][$name] ?? null, $value))) {
            $this->markLanguageAsDirty($language);
        }
        $this->items[$language][$name] = $value;

        if ($isLazyLoadedField) {
            $this->markLazyKeyAsLoaded($lazyKey);
        }

        return $this;
    }

    /**
     * @inheritDoc
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
    public function __sleep()
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
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param array $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return bool
     */
    public function hasDirtyLanguages()
    {
        if (AbstractObject::isDirtyDetectionDisabled()) {
            return true;
        }

        return is_array($this->o_dirtyLanguages) && count($this->o_dirtyLanguages) > 0;
    }

    /**
     * @param string $language
     *
     * @return bool
     */
    public function isLanguageDirty($language)
    {
        if (AbstractObject::isDirtyDetectionDisabled()) {
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

    public function resetLanguageDirtyMap()
    {
        $this->o_dirtyLanguages = null;
    }

    /**
     * @return array|null
     */
    public function getDirtyLanguages()
    {
        return $this->o_dirtyLanguages;
    }

    public function markAllLanguagesAsDirty()
    {
        $this->o_dirtyLanguages = [];
    }

    public function allLanguagesAreDirty()
    {
        if (AbstractObject::isDirtyDetectionDisabled()) {
            return true;
        }

        return is_array($this->o_dirtyLanguages) && count($this->o_dirtyLanguages) == 0;
    }

    /**
     * @param string $language
     * @param bool $dirty
     */
    public function markLanguageAsDirty($language, $dirty = true)
    {
        if (AbstractObject::isDirtyDetectionDisabled()) {
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
     * @inheritdoc
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
            if (($field instanceof LazyLoadingSupportInterface || method_exists($field, 'getLazyLoading'))
                                            && $field->getLazyLoading()) {
                $lazyLoadedFieldNames[] = $field->getName();
            }
        }

        return $lazyLoadedFieldNames;
    }

    /**
     * @return int
     */
    public function getObjectId(): int
    {
        return $this->objectId;
    }
}
