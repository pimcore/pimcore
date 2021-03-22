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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\Element;
use Pimcore\Tool;

class Localizedfields extends Data implements CustomResourcePersistingInterface, TypeDeclarationSupportInterface
{
    use Element\ChildsCompatibilityTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'localizedfields';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Localizedfield';

    /**
     * @var array
     */
    public $childs = [];

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $region;

    /**
     * @var string
     */
    public $layout;

    /**
     * @var string
     */
    public $title;

    /**
     * @var int
     */
    public $width;

    /**
     * @var int
     */
    public $height;

    /**
     * @var int
     */
    public $maxTabs;

    /**
     * @var int
     */
    public $labelWidth;

    /**
     * @var bool
     */
    public $border = false;

    /**
     * @var bool
     */
    public $provideSplitView;

    /**
     * @var string
     */
    public $tabPosition = 'top';

    /**
     * @var int
     */
    public $hideLabelsWhenTabsReached;

    /**
     * contains further localized field definitions if there are more than one localized fields in on class
     *
     * @var array
     */
    protected $referencedFields = [];

    /**
     * @var array|null
     */
    public $fieldDefinitionsCache;

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

        $dataItems = $data->getInternalData(true);
        foreach ($dataItems as $language => $values) {
            foreach ($this->getFieldDefinitions() as $fd) {
                if ($fd instanceof LazyLoadingSupportInterface && $fd->getLazyLoading()) {
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
        // /pimcore/modules/admin/views/scripts/object/preview-version.php
        return 'LOCALIZED FIELDS';
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        return 'NOT SUPPORTED';
    }

    /**
     * @param string $importValue
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return null
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return null;
    }

    /**
     * @param DataObject\Concrete|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param mixed $params
     *
     * @return string
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
     * @deprecated
     *
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $object->getObjectVar($this->getName());
        $wsData = [];

        $items = null;

        if (!$data instanceof DataObject\Localizedfield) {
            $items = [];
        } else {
            $items = $data->getInternalData(true);
        }

        $user = Tool\Admin::getCurrentUser();

        $languagesAllowed = null;
        if ($user && !$user->isAdmin()) {
            $languagesAllowed = DataObject\Service::getLanguagePermissions($object, $user, 'lView');

            if ($languagesAllowed) {
                $languagesAllowed = array_keys($languagesAllowed);
            }
        }

        $validLanguages = Tool::getValidLanguages();
        $localeService = \Pimcore::getContainer()->get('pimcore.locale');
        $localeBackup = $localeService->getLocale();

        if ($validLanguages) {
            foreach ($validLanguages as $language) {
                foreach ($this->getFieldDefinitions() as $fd) {
                    if ($languagesAllowed && !in_array($language, $languagesAllowed)) {
                        continue;
                    }

                    $localeService->setLocale($language);

                    $params['locale'] = $language;

                    $el = new Model\Webservice\Data\DataObject\Element();
                    $el->name = $fd->getName();
                    $el->type = $fd->getFieldType();
                    $el->value = $fd->getForWebserviceExport($object, $params);
                    if ($el->value == null && self::$dropNullValues) {
                        continue;
                    }
                    $el->language = $language;
                    $wsData[] = $el;
                }
            }

            $localeService->setLocale($localeBackup);
        }

        return $wsData;
    }

    /**
     * @deprecated
     *
     * @param array $value
     * @param DataObject\Concrete|null $object
     * @param array $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @return null|DataObject\Localizedfield
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        if (is_array($value)) {
            $validLanguages = Tool::getValidLanguages();

            if (!$idMapper || !$idMapper->ignoreMappingFailures()) {
                foreach ($value as $v) {
                    if (!in_array($v->language, $validLanguages)) {
                        throw new \Exception('Invalid language in localized fields');
                    }
                }
            }

            if (isset($params['context']) && $params['context']['containerType'] === 'fieldcollection') {
                $localizedFields = new DataObject\Localizedfield();
                $localizedFields->setContext($params['context']);
            } else {
                $localizedFields = $object->get('localizedfields');

                if (!$localizedFields instanceof DataObject\Localizedfield) {
                    $localizedFields = new DataObject\Localizedfield();
                }
            }

            if ($object instanceof DataObject\Concrete) {
                $localizedFields->setObject($object, false);
            }

            $user = Tool\Admin::getCurrentUser();

            $languagesAllowed = null;
            if ($user && !$user->isAdmin()) {
                $languagesAllowed = DataObject\Service::getLanguagePermissions($object, $user, 'lEdit');

                if ($languagesAllowed) {
                    $languagesAllowed = array_keys($languagesAllowed);
                }
            }

            foreach ($value as $field) {
                if ($field instanceof \stdClass) {
                    $field = Tool\Cast::castToClass('\\Pimcore\\Model\\Webservice\\Data\\DataObject\\Element', $field);
                }

                if ($idMapper && $idMapper->ignoreMappingFailures()) {
                    if (!in_array($field->language, $validLanguages)) {
                        continue;
                    }
                }

                if ($languagesAllowed && !in_array($field->language, $languagesAllowed)) {
                    //TODO needs to be discussed. Maybe it is better to throw an exception instead of ignoring
                    //the language
                    continue;
                }

                if (!$field instanceof Model\Webservice\Data\DataObject\Element) {
                    throw new \Exception(
                        "Invalid import data in field [ $field->name ] for language [ $field->language ] in localized fields [ ".$this->getName(
                        ).' ]'
                    );
                }
                $fd = $this->getFieldDefinition($field->name);
                if (!$fd instanceof DataObject\ClassDefinition\Data) {
                    if ($idMapper && $idMapper->ignoreMappingFailures()) {
                        continue;
                    }
                    throw new \Exception(
                        "Unknown field [ $field->name ] for language [ $field->language ] in localized fields [ ".$this->getName(
                        ).' ] '
                    );
                } elseif ($fd->getFieldtype() != $field->type) {
                    throw new \Exception(
                        "Type mismatch for field [ $field->name ] for language [ $field->language ] in localized fields [ ".$this->getName(
                        ).' ]. Should be [ '.$fd->getFieldtype().' ], but is [ '.$field->type.' ] '
                    );
                }

                $localizedFields->setLocalizedValue(
                    $field->name,
                    $this->getFieldDefinition($field->name)->getFromWebserviceImport(
                        $field->value,
                        $object,
                        $params,
                        $idMapper
                    ),
                    $field->language
                );
            }

            return $localizedFields;
        } elseif (!empty($value)) {
            throw new \Exception('Invalid data in localized fields');
        } else {
            return null;
        }
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        return $this->childs;
    }

    /**
     * @param array $children
     *
     * @return $this
     */
    public function setChildren($children)
    {
        $this->childs = $children;
        $this->fieldDefinitionsCache = null;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return is_array($this->childs) && count($this->childs) > 0;
    }

    /**
     * @param Data|Layout $child
     */
    public function addChild($child)
    {
        $this->childs[] = $child;
        $this->fieldDefinitionsCache = null;
    }

    /**
     * @param Data[] $referencedFields
     */
    public function setReferencedFields($referencedFields)
    {
        $this->referencedFields = $referencedFields;
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
    }

    /**
     * @param DataObject\Concrete|DataObject\Objectbrick\Data\AbstractData|\Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
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
     * @param DataObject\Concrete|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param array $params
     *
     * @return DataObject\Localizedfield
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
     * @param DataObject\Concrete $object
     * @param array $params
     */
    public function delete($object, $params = [])
    {
        $localizedFields = $this->getDataFromObjectParam($object, $params);

        if ($localizedFields instanceof DataObject\Localizedfield) {
            $localizedFields->setObject($object);
            $context = isset($params['context']) ? $params['context'] : null;
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
        $context = isset($params['context']) ? $params['context'] : null;
        $localizedFields->setContext($context);
        $localizedFields->createUpdateTable($params);

        foreach ($this->getFieldDefinitions() as $fd) {
            if (method_exists($fd, 'classSaved')) {
                $fd->classSaved($class, $params);
            }
        }
    }

    /**
     * @param DataObject\Concrete|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $container
     * @param array $params
     *
     * @return DataObject\Localizedfield
     *
     * @throws \Exception
     */
    public function preGetData($container, $params = [])
    {
        if (!$container instanceof DataObject\Concrete && !$container instanceof DataObject\Fieldcollection\Data\AbstractData
                    && !$container instanceof DataObject\Objectbrick\Data\AbstractData) {
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
     * @param DataObject\ClassDefinition|DataObject\Objectbrick\Definition|DataObject\Fieldcollection\Definition $class
     *
     * @return string
     */
    public function getGetterCode($class)
    {
        $code = '';
        if (!$class instanceof DataObject\Fieldcollection\Definition && !$class instanceof DataObject\Objectbrick\Definition) {
            $code .= parent::getGetterCode($class);
        }

        $fieldDefinitions = $this->getFieldDefinitions();
        foreach ($fieldDefinitions as $fd) {

            /**
             * @var $fd DataObject\ClassDefinition\Data
             */
            $code .= $fd->getGetterCodeLocalizedfields($class);
        }

        return $code;
    }

    /**
     * @param DataObject\ClassDefinition|DataObject\Objectbrick\Definition|DataObject\Fieldcollection\Definition $class
     *
     * @return string
     */
    public function getSetterCode($class)
    {
        $code = '';
        if (!$class instanceof DataObject\Fieldcollection\Definition && !$class instanceof DataObject\Objectbrick\Definition) {
            $code .= parent::getSetterCode($class);
        }

        foreach ($this->getFieldDefinitions() as $fd) {

            /**
             * @var $fd DataObject\ClassDefinition\Data
             */
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

    protected function doEnrichFieldDefinition($fieldDefinition, $context = [])
    {
        if (method_exists($fieldDefinition, 'enrichFieldDefinition')) {
            $context['class'] = $this;
            $fieldDefinition = $fieldDefinition->enrichFieldDefinition($context);
        }

        return $fieldDefinition;
    }

    /**
     * @param null $def
     * @param array $fields
     *
     * @return array
     */
    public function doGetFieldDefinitions($def = null, $fields = [])
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
     * This is a dummy and is mostly implemented by relation types
     *
     * @param mixed $data
     * @param array $tags
     *
     * @return array
     */
    public function getCacheTags($data, $tags = [])
    {
        $tags = is_array($tags) ? $tags : [];

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
     * @param int|string|null $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $this->getAsIntegerCast($height);

        return $this;
    }

    /**
     * @return int
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
     * @return $this|Data
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
     * @param int|string|null $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
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
                            if (isset($dataForValidityCheck[$language]) && isset($dataForValidityCheck[$language][$fd->getName()])) {
                                $fd->checkValidity($dataForValidityCheck[$language][$fd->getName()]);
                            } else {
                                $fd->checkValidity(null);
                            }
                        } catch (\Exception $e) {
                            if ($data->getObject()->getClass()->getAllowInherit()) {
                                //try again with parent data when inheritance is activated
                                try {
                                    $getInheritedValues = AbstractObject::doGetInheritedValues();
                                    AbstractObject::setGetInheritedValues(true);

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

                                    $fd->checkValidity($value, $omitMandatoryCheck);
                                    AbstractObject::setGetInheritedValues($getInheritedValues);
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
            $aggregatedExceptions = new Model\Element\ValidationException();
            $aggregatedExceptions->setSubItems($validationExceptions);
            throw $aggregatedExceptions;
        }
    }

    /**
     * @param DataObject\Localizedfield|mixed $localizedObject
     * @param array $languages
     *
     * @return array
     */
    protected function getDataForValidity($localizedObject, array $languages)
    {
        if (!$localizedObject->getObject()
            || $localizedObject->getObject()->getType() != 'variant'
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

    /** See parent class.
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

    /** See parent class.
     * @param array $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return mixed
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
            $values = $mapping[$fieldname];

            $itemdata = $item['data'];

            if ($itemdata) {
                if (!$values) {
                    $values = [];
                }

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

    /** True if change is allowed in edit mode.
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $vars = get_object_vars($this);
        unset($vars['fieldDefinitionsCache']);
        unset($vars['referencedFields']);

        return array_keys($vars);
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     * @param mixed $object
     * @param array $idMapping
     * @param array $params
     *
     * @return Model\Element\ElementInterface
     */
    public function rewriteIds($object, $idMapping, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        $validLanguages = Tool::getValidLanguages();

        foreach ($validLanguages as $language) {
            foreach ($this->getFieldDefinitions() as $fd) {
                if (method_exists($fd, 'rewriteIds')) {
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
        $this->labelWidth = $labelWidth;
    }

    /**
     * @return int
     */
    public function getLabelWidth()
    {
        return $this->labelWidth;
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

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
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

                        $childParams = ['raw' => true];
                        if ($params['blockmode'] ?? false) {
                            $childParams['blockmode'] = true;
                        }

                        $dataForResource = $fd->marshal($elementData, $object, $childParams);

                        $languageResult[$elementName] = $dataForResource;
                    }

                    $result[$language] = $languageResult;
                }

                return $result;
            }
        }

        return null;
    }

    /** See marshal
     * @param mixed $value
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        $lf = new DataObject\Localizedfield();
        $lf->setObject($object);
        if (is_array($value)) {
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

                    $childParams = ['raw' => true];
                    if ($params['blockmode'] ?? false) {
                        $childParams['blockmode'] = true;
                    }
                    $dataFromResource = $fd->unmarshal($elementData, $object, $childParams);

                    $languageResult[$elementName] = $dataFromResource;
                }

                $items[$language] = $languageResult;
            }

            $lf->setItems($items);
        }

        return $lf;
    }

    /**
     * @return bool
     */
    public function supportsDirtyDetection()
    {
        return true;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getTabPosition(): string
    {
        return $this->tabPosition;
    }

    /**
     * @param string $tabPosition
     */
    public function setTabPosition($tabPosition): void
    {
        $this->tabPosition = $tabPosition;
    }
}
