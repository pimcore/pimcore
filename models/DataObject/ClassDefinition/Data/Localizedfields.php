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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool;

class Localizedfields extends Data implements CustomResourcePersistingInterface, TypeDeclarationSupportInterface, NormalizerInterface, DataContainerAwareInterface, IdRewriterInterface, PreGetDataInterface, VarExporterInterface
{
    use Element\ChildsCompatibilityTrait;
    use Layout\Traits\LabelTrait;
    use DataObject\Traits\ClassSavedTrait;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'localizedfields';

    /**
     * @internal
     *
     * @var array
     */
    public $children = [];

    /**
     * @internal
     *
     * @var string
     */
    public $name;

    /**
     * @internal
     *
     * @var string
     */
    public $region;

    /**
     * @internal
     *
     * @var string
     */
    public $layout;

    /**
     * @internal
     *
     * @var string
     */
    public $title;

    /**
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * @internal
     *
     * @var string|int
     */
    public $height = 0;

    /**
     * @internal
     *
     * @var int
     */
    public $maxTabs;

    /**
     * @internal
     *
     * @var bool
     */
    public $border = false;

    /**
     * @internal
     *
     * @var bool
     */
    public $provideSplitView;

    /**
     * @internal
     *
     * @var string|null
     */
    public $tabPosition = 'top';

    /**
     * @internal
     *
     * @var int
     */
    public $hideLabelsWhenTabsReached;

    /**
     * contains further localized field definitions if there are more than one localized fields in on class
     *
     * @internal
     *
     * @var array
     */
    protected $referencedFields = [];

    /**
     * @internal
     *
     * @var array|null
     */
    public $fieldDefinitionsCache;

    /**
     * @internal
     *
     * @var array|null
     */
    public $permissionView;

    /**
     * @internal
     *
     * @var array|null
     */
    public $permissionEdit;

    /**
     * @see Data::getDataForEditmode
     *
     * @param DataObject\Localizedfield $localizedField
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForEditmode($localizedField, $object = null, $params = [])
    {
        $fieldData = [];
        $metaData = [];

        if (!$localizedField instanceof DataObject\Localizedfield) {
            return [];
        }

        $result = $this->doGetDataForEditMode($localizedField, $object, $fieldData, $metaData, 1, $params);

        // replace the real data with the data for the editmode
        foreach ($result['data'] as $language => &$data) {
            foreach ($data as $key => &$value) {
                $fieldDefinition = $this->getFieldDefinition($key);
                if ($fieldDefinition instanceof CalculatedValue) {
                    $childData = new DataObject\Data\CalculatedValue($fieldDefinition->getName());
                    $ownerType = $params['context']['containerType'] ?? 'localizedfield';
                    $ownerName = $params['fieldname'] ?? $this->getName();
                    $index = $params['context']['containerKey'] ?? null;
                    $childData->setContextualData($ownerType, $ownerName, $index, $language, null, null, $fieldDefinition);
                    $value = $fieldDefinition->getDataForEditmode($childData, $object, $params);
                } else {
                    $value = $fieldDefinition->getDataForEditmode($value, $object, array_merge($params, $localizedField->getDao()->getFieldDefinitionParams($fieldDefinition->getName(), $language)));
                }
            }
        }

        return $result;
    }

    /**
     * @param DataObject\Localizedfield $data
     * @param DataObject\Concrete $object
     * @param array $fieldData
     * @param array $metaData
     * @param int $level
     * @param array $params
     *
     * @return array
     */
    private function doGetDataForEditMode($data, $object, &$fieldData, &$metaData, $level = 1, $params = [])
    {
        $class = $object->getClass();
        $inheritanceAllowed = $class->getAllowInherit();
        $inherited = false;

        $loadLazy = !($params['objectFromVersion'] ?? false);
        $dataItems = $data->getInternalData($loadLazy);
        foreach ($dataItems as $language => $values) {
            foreach ($this->getFieldDefinitions() as $fd) {
                if ($fd instanceof LazyLoadingSupportInterface && $fd->getLazyLoading() && $loadLazy) {
                    $lazyKey = $data->buildLazyKey($fd->getName(), $language);
                    if (!$data->isLazyKeyLoaded($lazyKey) && $fd instanceof CustomResourcePersistingInterface) {
                        $params['language'] = $language;
                        $params['object'] = $object;
                        if (!isset($params['context'])) {
                            $params['context'] = [];
                        }
                        $params['context']['object'] = $object;

                        $value = $fd->load($data, $params);
                        if ($value === 0 || !empty($value)) {
                            $data->setLocalizedValue($fd->getName(), $value, $language, false);
                            $values[$fd->getName()] = $value;
                        }

                        $data->markLazyKeyAsLoaded($lazyKey);
                    }
                }

                $key = $fd->getName();
                $fdata = isset($values[$fd->getName()]) ? $values[$fd->getName()] : null;

                if (!isset($fieldData[$language][$key]) || $fd->isEmpty($fieldData[$language][$key])) {
                    // never override existing data
                    $fieldData[$language][$key] = $fdata;
                    if (!$fd->isEmpty($fdata)) {
                        $inherited = $level > 1;
                        if (isset($params['context']['containerType']) && $params['context']['containerType'] === 'block') {
                            $inherited = false;
                        }

                        $metaData[$language][$key] = ['inherited' => $inherited, 'objectid' => $object->getId()];
                    }
                }
            }
        }

        if (isset($params['context']['containerType']) && $params['context']['containerType'] === 'block') {
            $inheritanceAllowed = false;
        }

        if ($inheritanceAllowed) {
            // check if there is a parent with the same type
            $parent = DataObject\Service::hasInheritableParentObject($object);
            if ($parent) {
                // same type, iterate over all language and all fields and check if there is something missing
                $validLanguages = Tool::getValidLanguages();
                $foundEmptyValue = false;

                foreach ($validLanguages as $language) {
                    $fieldDefinitions = $this->getFieldDefinitions();
                    foreach ($fieldDefinitions as $fd) {
                        $key = $fd->getName();

                        if ($fd->isEmpty($fieldData[$language][$key] ?? null)) {
                            $foundEmptyValue = true;
                            $inherited = true;
                            $metaData[$language][$key] = ['inherited' => true, 'objectid' => $parent->getId()];
                        }
                    }
                }

                if ($foundEmptyValue) {
                    $parentData = null;
                    // still some values are passing, ask the parent
                    if (isset($params['context']['containerType']) && $params['context']['containerType'] === 'objectbrick') {
                        $brickContainerGetter = 'get' . ucfirst($params['fieldname']);
                        $brickContainer = $parent->$brickContainerGetter();
                        $brickGetter = 'get' . ucfirst($params['context']['containerKey']);
                        $brickData = $brickContainer->$brickGetter();
                        if ($brickData) {
                            $parentData = $brickData->getLocalizedFields();
                        }
                    } else {
                        if (method_exists($parent, 'getLocalizedFields')) {
                            $parentData = $parent->getLocalizedFields();
                        }
                    }
                    if ($parentData) {
                        $parentResult = $this->doGetDataForEditMode(
                            $parentData,
                            $parent,
                            $fieldData,
                            $metaData,
                            $level + 1,
                            $params
                        );
                    }
                }
            }
        }

        $result = [
            'data' => $fieldData,
            'metaData' => $metaData,
            'inherited' => $inherited,
        ];

        return $result;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Localizedfield
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        $localizedFields = $this->getDataFromObjectParam($object, $params);

        if (!$localizedFields instanceof DataObject\Localizedfield) {
            $localizedFields = new DataObject\Localizedfield();
            $context = isset($params['context']) ? $params['context'] : null;
            $localizedFields->setContext($context);
        }

        if ($object) {
            $localizedFields->setObject($object);
        }

        if (is_array($data)) {
            foreach ($data as $language => $fields) {
                foreach ($fields as $name => $fdata) {
                    $fd = $this->getFieldDefinition($name);
                    $params['language'] = $language;
                    $localizedFields->setLocalizedValue(
                        $name,
                        $fd->getDataFromEditmode($fdata, $object, $params),
                        $language
                    );
                }
            }
        }

        return $localizedFields;
    }

    /**
     * @param DataObject\Localizedfield|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return \stdClass
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        $result = new \stdClass();
        foreach ($this->getFieldDefinitions() as $fd) {
            $key = $fd->getName();
            $context = $params['context'] ?? null;
            if (isset($context['containerType']) && $context['containerType'] === 'objectbrick') {
                $result->$key = 'NOT SUPPORTED';
            } else {
                $result->$key = $object->{'get' . ucfirst($fd->getName())}();
            }
            if (method_exists($fd, 'getDataForGrid')) {
                $result->$key = $fd->getDataForGrid($result->$key, $object, $params);
            }
        }

        return $result;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param DataObject\Localizedfield|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        // this is handled directly in the template
        // /bundles/AdminBundle/Resources/views/Admin/DataObject/DataObject/previewVersion.html.twig
        return 'LOCALIZED FIELDS';
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        return 'NOT SUPPORTED';
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        $dataString = '';
        $lfData = $this->getDataFromObjectParam($object);

        if ($lfData instanceof DataObject\Localizedfield) {
            foreach ($lfData->getInternalData(true) as $language => $values) {
                foreach ($values as $fieldname => $lData) {
                    $fd = $this->getFieldDefinition($fieldname);
                    if ($fd) {
                        $forSearchIndex = $fd->getDataForSearchIndex($lfData, [
                            'injectedData' => $lData,
                        ]);
                        if ($forSearchIndex) {
                            $dataString .= $forSearchIndex . ' ';
                        }
                    }
                }
            }
        }

        return $dataString;
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param array $children
     *
     * @return $this
     */
    public function setChildren($children)
    {
        $this->children = $children;
        $this->fieldDefinitionsCache = null;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return is_array($this->children) && count($this->children) > 0;
    }

    /**
     * @param Data|Layout $child
     */
    public function addChild($child)
    {
        $this->children[] = $child;
        $this->fieldDefinitionsCache = null;
    }

    /**
     * @param Data[] $referencedFields
     */
    public function setReferencedFields($referencedFields)
    {
        $this->referencedFields = $referencedFields;
        $this->fieldDefinitionsCache = null;
    }

    /**
     * @return Data[]
     */
    public function getReferencedFields()
    {
        return $this->referencedFields;
    }

    /**
     * @param Data $field
     */
    public function addReferencedField($field)
    {
        $this->referencedFields[] = $field;
        $this->fieldDefinitionsCache = null;
    }

    /**
     * {@inheritdoc}
     */
    public function save($object, $params = [])
    {
        $localizedFields = $this->getDataFromObjectParam($object, $params);
        if ($localizedFields instanceof DataObject\Localizedfield) {
            if ((!isset($params['newParent']) || !$params['newParent']) && isset($params['isUpdate']) && $params['isUpdate'] && !$localizedFields->hasDirtyLanguages()) {
                return;
            }

            if ($object instanceof DataObject\Fieldcollection\Data\AbstractData || $object instanceof DataObject\Objectbrick\Data\AbstractData) {
                $object = $object->getObject();
            }

            $localizedFields->setObject($object, false);
            $context = isset($params['context']) ? $params['context'] : null;
            $localizedFields->setContext($context);
            $localizedFields->loadLazyData();
            $localizedFields->save($params);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load($object, $params = [])
    {
        if ($object instanceof DataObject\Fieldcollection\Data\AbstractData || $object instanceof DataObject\Objectbrick\Data\AbstractData) {
            $object = $object->getObject();
        }

        $localizedFields = new DataObject\Localizedfield();
        $localizedFields->setObject($object);
        $context = isset($params['context']) ? $params['context'] : null;
        $localizedFields->setContext($context);
        $localizedFields->load($object, $params);

        $localizedFields->resetDirtyMap();
        $localizedFields->resetLanguageDirtyMap();

        return $localizedFields;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($object, $params = [])
    {
        $localizedFields = $this->getDataFromObjectParam($object, $params);

        if ($localizedFields instanceof DataObject\Localizedfield) {
            $localizedFields->setObject($object);
            $context = $params['context'] ?? [];
            $localizedFields->setContext($context);
            $localizedFields->delete(true, false);
        }
    }

    /**
     * This method is called in DataObject\ClassDefinition::save() and is used to create the database table for the localized data
     *
     * @param DataObject\ClassDefinition $class
     * @param array $params
     */
    public function classSaved($class, $params = [])
    {
        // create a dummy instance just for updating the tables
        $localizedFields = new DataObject\Localizedfield();
        $localizedFields->setClass($class);
        $context = $params['context'] ?? [];
        $localizedFields->setContext($context);
        $localizedFields->createUpdateTable($params);

        foreach ($this->getFieldDefinitions() as $fd) {
            //TODO Pimcore 11 remove method_exists call
            if (!$fd instanceof DataContainerAwareInterface && method_exists($fd, 'classSaved')) {
                $fd->classSaved($class, $params);
            }
        }
    }

    /**
     * { @inheritdoc }
     */
    public function preGetData(/** mixed */ $container, /** array */ $params = []) // : mixed
    {
        if (
            !$container instanceof DataObject\Concrete &&
            !$container instanceof DataObject\Fieldcollection\Data\AbstractData &&
            !$container instanceof DataObject\Objectbrick\Data\AbstractData
        ) {
            throw new \Exception('Localized Fields are only valid in Objects, Fieldcollections and Objectbricks');
        }

        $lf = $container->getObjectVar('localizedfields');
        if (!$lf instanceof DataObject\Localizedfield) {
            $lf = new DataObject\Localizedfield();

            $object = $container;
            if ($container instanceof DataObject\Objectbrick\Data\AbstractData) {
                $object = $container->getObject();

                $context = [
                    'containerType' => 'objectbrick',
                    'containerKey' => $container->getType(),
                    'fieldname' => $container->getFieldname(),
                ];
                $lf->setContext($context);
            } elseif ($container instanceof DataObject\Fieldcollection\Data\AbstractData) {
                $object = $container->getObject();

                $context = [
                    'containerType' => 'fieldcollection',
                    'containerKey' => $container->getType(),
                    'fieldname' => $container->getFieldname(),
                ];
                $lf->setContext($context);
            } elseif ($container instanceof DataObject\Concrete) {
                $context = ['object' => $container];
                $lf->setContext($context);
            }
            $lf->setObject($object);

            $container->setObjectVar('localizedfields', $lf);
        }

        return $container->getObjectVar('localizedfields');
    }

    /**
     * {@inheritdoc}
     */
    public function getGetterCode($class)
    {
        $code = '';
        if (!$class instanceof DataObject\Fieldcollection\Definition) {
            $code .= parent::getGetterCode($class);
        }

        $fieldDefinitions = $this->getFieldDefinitions();
        foreach ($fieldDefinitions as $fd) {
            $code .= $fd->getGetterCodeLocalizedfields($class);
        }

        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function getSetterCode($class)
    {
        $code = '';
        if (!$class instanceof DataObject\Fieldcollection\Definition) {
            $code .= parent::getSetterCode($class);
        }

        foreach ($this->getFieldDefinitions() as $fd) {
            $code .= $fd->getSetterCodeLocalizedfields($class);
        }

        return $code;
    }

    /**
     * @param string $name
     * @param array $context additional contextual data
     *
     * @return DataObject\ClassDefinition\Data|null
     */
    public function getFieldDefinition($name, $context = [])
    {
        $fds = $this->getFieldDefinitions($context);
        if (isset($fds[$name])) {
            $fieldDefinition = $fds[$name];
            if (!\Pimcore::inAdmin() || (isset($context['suppressEnrichment']) && $context['suppressEnrichment'])) {
                return $fieldDefinition;
            }

            $fieldDefinition = $this->doEnrichFieldDefinition($fieldDefinition, $context);

            return $fieldDefinition;
        }

        return null;
    }

    /**
     * @param array $context additional contextual data
     *
     * @return Data[]
     */
    public function getFieldDefinitions($context = [])
    {
        if (empty($this->fieldDefinitionsCache)) {
            $definitions = $this->doGetFieldDefinitions();
            foreach ($this->getReferencedFields() as $rf) {
                if ($rf instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $definitions = array_merge($definitions, $this->doGetFieldDefinitions($rf->getChildren()));
                }
            }

            $this->fieldDefinitionsCache = $definitions;
        }

        if (!\Pimcore::inAdmin() || (isset($context['suppressEnrichment']) && $context['suppressEnrichment'])) {
            return $this->fieldDefinitionsCache;
        }

        $enrichedFieldDefinitions = [];
        if (is_array($this->fieldDefinitionsCache)) {
            foreach ($this->fieldDefinitionsCache as $key => $fieldDefinition) {
                $fieldDefinition = $this->doEnrichFieldDefinition($fieldDefinition, $context);
                $enrichedFieldDefinitions[$key] = $fieldDefinition;
            }
        }

        return $enrichedFieldDefinitions;
    }

    private function doEnrichFieldDefinition($fieldDefinition, $context = [])
    {
        if ($fieldDefinition instanceof FieldDefinitionEnrichmentInterface) {
            $context['class'] = $this;
            $fieldDefinition = $fieldDefinition->enrichFieldDefinition($context);
        }

        return $fieldDefinition;
    }

    /**
     * @param mixed $def
     * @param array $fields
     *
     * @return array
     */
    private function doGetFieldDefinitions($def = null, $fields = [])
    {
        if ($def === null) {
            $def = $this->getChildren();
        }

        if (is_array($def)) {
            foreach ($def as $child) {
                $fields = array_merge($fields, $this->doGetFieldDefinitions($child, $fields));
            }
        }

        if ($def instanceof DataObject\ClassDefinition\Layout) {
            if ($def->hasChildren()) {
                foreach ($def->getChildren() as $child) {
                    $fields = array_merge($fields, $this->doGetFieldDefinitions($child, $fields));
                }
            }
        }

        if ($def instanceof DataObject\ClassDefinition\Data) {
            $fields[$def->getName()] = $def;
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags($data, array $tags = [])
    {
        if (!$data instanceof DataObject\Localizedfield) {
            return $tags;
        }

        foreach ($data->getInternalData(true) as $language => $values) {
            foreach ($this->getFieldDefinitions() as $fd) {
                if (isset($values[$fd->getName()])) {
                    $tags = $fd->getCacheTags($values[$fd->getName()], $tags);
                }
            }
        }

        return $tags;
    }

    /**
     * @param DataObject\Localizedfield|null $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        $dependencies = [];

        if (!$data instanceof DataObject\Localizedfield) {
            return [];
        }

        foreach ($data->getInternalData(true) as $language => $values) {
            foreach ($this->getFieldDefinitions() as $fd) {
                if (isset($values[$fd->getName()])) {
                    $dependencies = array_merge($dependencies, $fd->resolveDependencies($values[$fd->getName()]));
                }
            }
        }

        return $dependencies;
    }

    /**
     * @param string|int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        if (is_numeric($height)) {
            $height = (int)$height;
        }
        $this->height = $height;

        return $this;
    }

    /**
     * @return string|int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param mixed $layout
     *
     * @return $this
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @return bool
     */
    public function getBorder(): bool
    {
        return $this->border;
    }

    /**
     * @param bool $border
     */
    public function setBorder(bool $border): void
    {
        $this->border = $border;
    }

    /**
     * @param string $name
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setName($name)
    {
        if ($name !== 'localizedfields') {
            throw new \Exception('Localizedfields can only be named `localizedfields`, no other names are allowed');
        }

        $this->name = $name;

        return $this;
    }

    /**
     * @param string|null $region
     *
     * @return $this
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param int|string $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @return int|string
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        $config = \Pimcore\Config::getSystemConfiguration('general');
        $languages = [];
        if (isset($config['valid_languages'])) {
            $languages = explode(',', $config['valid_languages']);
        }

        $dataForValidityCheck = $this->getDataForValidity($data, $languages);
        $validationExceptions = [];
        if (!$omitMandatoryCheck) {
            foreach ($languages as $language) {
                foreach ($this->getFieldDefinitions() as $fd) {
                    try {
                        try {
                            if (!isset($dataForValidityCheck[$language][$fd->getName()])) {
                                $dataForValidityCheck[$language][$fd->getName()] = null;
                            }
                            $fd->checkValidity($dataForValidityCheck[$language][$fd->getName()], false, $params);
                        } catch (\Exception $e) {
                            if ($data->getObject()->getClass()->getAllowInherit() && $fd->supportsInheritance() && $fd->isEmpty($dataForValidityCheck[$language][$fd->getName()])) {
                                //try again with parent data when inheritance is activated
                                try {
                                    $getInheritedValues = DataObject::doGetInheritedValues();
                                    DataObject::setGetInheritedValues(true);

                                    $value = null;
                                    $context = $data->getContext();
                                    $containerType = $context['containerType'] ?? null;
                                    if ($containerType === 'objectbrick') {
                                        $brickContainer = $data->getObject()->{'get' . ucfirst($context['fieldname'])}();
                                        $brick = $brickContainer->{'get' . ucfirst($context['containerKey'])}();
                                        if ($brick) {
                                            $value = $brick->{'get' . ucfirst($fd->getName())}($language);
                                        }
                                    } elseif ($containerType === null || $containerType === 'object') {
                                        $getter = 'get' . ucfirst($fd->getName());
                                        $value = $data->getObject()->$getter($language);
                                    }

                                    $fd->checkValidity($value, $omitMandatoryCheck, $params);
                                    DataObject::setGetInheritedValues($getInheritedValues);
                                } catch (\Exception $e) {
                                    if (!$e instanceof Model\Element\ValidationException) {
                                        throw $e;
                                    }
                                    $exceptionClass = get_class($e);

                                    throw new $exceptionClass($e->getMessage() . ' fieldname=' . $fd->getName(), $e->getCode(), $e->getPrevious());
                                }
                            } else {
                                if ($e instanceof Model\Element\ValidationException) {
                                    throw $e;
                                }
                                $exceptionClass = get_class($e);

                                throw new $exceptionClass($e->getMessage() . ' fieldname=' . $fd->getName(), $e->getCode(), $e);
                            }
                        }
                    } catch (Model\Element\ValidationException $ve) {
                        $ve->addContext($this->getName() . '-' . $language);
                        $validationExceptions[] = $ve;
                    }
                }
            }
        }

        if (count($validationExceptions) > 0) {
            $errors = [];
            /** @var Element\ValidationException $e */
            foreach ($validationExceptions as $e) {
                $errors[] = $e->getAggregatedMessage();
            }
            $message = implode(' / ', $errors);

            throw new Model\Element\ValidationException($message);
        }
    }

    /**
     * @param DataObject\Localizedfield|mixed $localizedObject
     * @param array $languages
     *
     * @return array
     */
    private function getDataForValidity($localizedObject, array $languages)
    {
        if (!$localizedObject->getObject()
            || $localizedObject->getObject()->getType() != DataObject::OBJECT_TYPE_VARIANT
            || !$localizedObject instanceof DataObject\Localizedfield) {
            return $localizedObject->getInternalData(true);
        }

        //prepare data for variants
        $data = [];
        foreach ($languages as $language) {
            $data[$language] = [];
            foreach ($this->getFieldDefinitions() as $fd) {
                $data[$language][$fd->getName()] = $localizedObject->getLocalizedValue($fd->getName(), $language);
            }
        }

        return $data;
    }

    /**
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDiffDataForEditmode($data, $object = null, $params = [])
    {
        $return = [];

        $myname = $this->getName();

        if (!$data instanceof DataObject\Localizedfield) {
            return [];
        }

        foreach ($data->getInternalData(true) as $language => $values) {
            foreach ($this->getFieldDefinitions() as $fd) {
                $fieldname = $fd->getName();

                $subdata = $fd->getDiffDataForEditmode($values[$fieldname], $object, $params);

                foreach ($subdata as $item) {
                    $diffdata['field'] = $this->getName();
                    $diffdata['key'] = $this->getName().'~'.$fieldname.'~'.$item['key'].'~'.$language;

                    $diffdata['type'] = $item['type'];
                    $diffdata['value'] = $item['value'];

                    // this is not needed anymoe
                    unset($item['type']);
                    unset($item['value']);

                    $diffdata['title'] = $this->getName().' / '.$item['title'];
                    $diffdata['lang'] = $language;
                    $diffdata['data'] = $item;
                    $diffdata['extData'] = [
                        'fieldname' => $fieldname,
                    ];

                    $diffdata['disabled'] = $item['disabled'];
                    $return[] = $diffdata;
                }
            }
        }

        return $return;
    }

    /**
     * @param array $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return DataObject\Localizedfield
     */
    public function getDiffDataFromEditmode($data, $object = null, $params = [])
    {
        $localFields = $this->getDataFromObjectParam($object, $params);
        $localData = [];

        // get existing data
        if ($localFields instanceof DataObject\Localizedfield) {
            $localData = $localFields->getInternalData(true);
        }

        $mapping = [];
        foreach ($data as $item) {
            $extData = $item['extData'];
            $fieldname = $extData['fieldname'];
            $language = $item['lang'];
            $values = $mapping[$fieldname] ?? [];

            $itemdata = $item['data'];

            if ($itemdata) {
                $values[] = $itemdata;
            }

            $mapping[$language][$fieldname] = $values;
        }

        foreach ($mapping as $language => $fields) {
            foreach ($fields as $key => $value) {
                $fd = $this->getFieldDefinition($key);
                if ($fd && $fd->isDiffChangeAllowed($object)) {
                    if ($value == null) {
                        unset($localData[$language][$key]);
                    } else {
                        $localData[$language][$key] = $fd->getDiffDataFromEditmode($value);
                    }
                }
            }
        }

        $localizedFields = new DataObject\Localizedfield($localData);
        $localizedFields->setObject($object);

        return $localizedFields;
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /**
     * @return array
     */
    public function getBlockedVarsForExport(): array
    {
        return [
            'fieldDefinitionsCache',
            'referencedFields',
            'blockedVarsForExport',
            'permissionView',
            'permissionEdit',
            'childs',
        ];
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $vars = get_object_vars($this);
        $blockedVars = $this->getBlockedVarsForExport();

        foreach ($blockedVars as $blockedVar) {
            unset($vars[$blockedVar]);
        }

        return array_keys($vars);
    }

    /**
     * { @inheritdoc }
     */
    public function rewriteIds(/** mixed */ $container, /** array */ $idMapping, /** array */ $params = []) /** :mixed */
    {
        $data = $this->getDataFromObjectParam($container, $params);

        $validLanguages = Tool::getValidLanguages();

        foreach ($validLanguages as $language) {
            foreach ($this->getFieldDefinitions() as $fd) {
                if ($fd instanceof IdRewriterInterface) {
                    $d = $fd->rewriteIds($data, $idMapping, ['language' => $language]);
                    $data->setLocalizedValue($fd->getName(), $d, $language);
                }
            }
        }

        return $data;
    }

    /**
     * @return int
     */
    public function getHideLabelsWhenTabsReached()
    {
        return $this->hideLabelsWhenTabsReached;
    }

    /**
     * @param int $hideLabelsWhenTabsReached
     *
     * @return $this
     */
    public function setHideLabelsWhenTabsReached($hideLabelsWhenTabsReached)
    {
        $this->hideLabelsWhenTabsReached = $hideLabelsWhenTabsReached;

        return $this;
    }

    /**
     * @param int $maxTabs
     */
    public function setMaxTabs($maxTabs)
    {
        $this->maxTabs = $maxTabs;
    }

    /**
     * @return int
     */
    public function getMaxTabs()
    {
        return $this->maxTabs;
    }

    /**
     * @param int $labelWidth
     */
    public function setLabelWidth($labelWidth)
    {
        $this->labelWidth = (int)$labelWidth;
    }

    /**
     * @return int
     */
    public function getLabelWidth()
    {
        return $this->labelWidth;
    }

    /**
     * @return array|null
     */
    public function getPermissionView(): ?array
    {
        return $this->permissionView;
    }

    /**
     * @param array|null $permissionView
     */
    public function setPermissionView($permissionView): void
    {
        $this->permissionView = $permissionView;
    }

    /**
     * @return array|null
     */
    public function getPermissionEdit(): ?array
    {
        return $this->permissionEdit;
    }

    /**
     * @param array|null $permissionEdit
     */
    public function setPermissionEdit($permissionEdit): void
    {
        $this->permissionEdit = $permissionEdit;
    }

    /**
     * @return bool
     */
    public function getProvideSplitView()
    {
        return $this->provideSplitView;
    }

    /**
     * @param bool $provideSplitView
     */
    public function setProvideSplitView($provideSplitView): void
    {
        $this->provideSplitView = $provideSplitView;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDirtyDetection()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getTabPosition(): string
    {
        return $this->tabPosition ?? 'top';
    }

    /**
     * @param string|null $tabPosition
     */
    public function setTabPosition($tabPosition): void
    {
        $this->tabPosition = $tabPosition;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Localizedfield::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . DataObject\Localizedfield::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\'. DataObject\Localizedfield::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . DataObject\Localizedfield::class . '|null';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value instanceof DataObject\Localizedfield) {
            $items = $value->getInternalData();
            if (is_array($items)) {
                $result = [];
                foreach ($items as $language => $languageData) {
                    $languageResult = [];
                    foreach ($languageData as $elementName => $elementData) {
                        $fd = $this->getFieldDefinition($elementName);
                        if (!$fd) {
                            // class definition seems to have changed
                            Logger::warn('class definition seems to have changed, element name: '.$elementName);

                            continue;
                        }

                        if ($fd instanceof NormalizerInterface) {
                            $dataForResource = $fd->normalize($elementData, $params);
                            $languageResult[$elementName] = $dataForResource;
                        }
                    }

                    $result[$language] = $languageResult;
                }

                return $result;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if (is_array($value)) {
            $lf = new DataObject\Localizedfield();
            $lf->setObject($params['object']);

            $items = [];

            foreach ($value as $language => $languageData) {
                $languageResult = [];
                foreach ($languageData as $elementName => $elementData) {
                    $fd = $this->getFieldDefinition($elementName);
                    if (!$fd) {
                        // class definition seems to have changed
                        Logger::warn('class definition seems to have changed, element name: '.$elementName);

                        continue;
                    }

                    if ($fd instanceof NormalizerInterface) {
                        $dataFromResource = $fd->denormalize($elementData, $params);
                        $languageResult[$elementName] = $dataFromResource;
                    }
                }

                $items[$language] = $languageResult;
            }

            $lf->setItems($items);

            return $lf;
        }

        return null;
    }

    public static function __set_state($data)
    {
        $obj = new static();
        $obj->setValues($data);

        $obj->childs = $obj->children;  // @phpstan-ignore-line

        return $obj;
    }
}
