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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Exception;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool;
use stdClass;

class Localizedfields extends Data implements CustomResourcePersistingInterface, TypeDeclarationSupportInterface, NormalizerInterface, DataContainerAwareInterface, IdRewriterInterface, PreGetDataInterface, VarExporterInterface, FieldDefinitionEnrichmentModelInterface
{
    use Layout\Traits\LabelTrait;
    use DataObject\Traits\ClassSavedTrait;
    use DataObject\Traits\DataWidthTrait;
    use DataObject\Traits\DataHeightTrait;
    use DataObject\Traits\FieldDefinitionEnrichmentDataTrait;

    /**
     * @internal
     */
    public array $children = [];

    /**
     * @internal
     */
    public ?string $name = null;

    /**
     * @internal
     */
    public string|null $region = null;

    /**
     * @internal
     */
    public string|null $layout = null;

    /**
     * @internal
     */
    public ?string $title = null;

    /**
     * @internal
     */
    public int|null $maxTabs = null;

    /**
     * @internal
     */
    public bool $border = false;

    /**
     * @internal
     */
    public bool $provideSplitView = false;

    /**
     * @internal
     */
    public ?string $tabPosition = 'top';

    /**
     * @internal
     */
    public ?int $hideLabelsWhenTabsReached = null;

    /**
     * contains further localized field definitions if there are more than one localized fields in on class
     *
     * @internal
     */
    protected array $referencedFields = [];

    /**
     * @internal
     */
    public ?array $permissionView = null;

    /**
     * @internal
     */
    public ?array $permissionEdit = null;

    public function getDataForEditmode(mixed $localizedField, DataObject\Concrete $object = null, array $params = []): array
    {
        $fieldData = [];
        $metaData = [];

        if (!$localizedField instanceof Localizedfield) {
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

    private function doGetDataForEditMode(Localizedfield $data, DataObject\Concrete $object, array &$fieldData, array &$metaData, int $level = 1, array $params = []): array
    {
        $class = $object->getClass();
        $inheritanceAllowed = $class->getAllowInherit();
        $inherited = false;

        $loadLazy = !($params['objectFromVersion'] ?? false);
        $dataItems = $data->getInternalData($loadLazy);
        foreach ($dataItems as $language => $values) {
            foreach ($this->getFieldDefinitions() as $fd) {
                if ($fd instanceof LazyLoadingSupportInterface
                    && $fd instanceof DataObject\ClassDefinition\Data
                    && $fd->getLazyLoading()
                    && $loadLazy) {
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

    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): Localizedfield
    {
        $localizedFields = $this->getDataFromObjectParam($object, $params);

        if (!$localizedFields instanceof Localizedfield) {
            $localizedFields = new Localizedfield();
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

    public function getDataForGrid(?Localizedfield $data, Concrete $object = null, array $params = []): stdClass
    {
        $result = new stdClass();
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

    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        // this is handled directly in the template
        // https://github.com/pimcore/admin-ui-classic-bundle/blob/1.x/templates/admin/data_object/data_object/preview_version.html.twig
        return 'LOCALIZED FIELDS';
    }

    public function getForCsvExport(Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return 'NOT SUPPORTED';
    }

    public function getDataForSearchIndex(Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $dataString = '';
        $lfData = $this->getDataFromObjectParam($object);

        if ($lfData instanceof Localizedfield) {
            foreach ($lfData->getInternalData(true) as $values) {
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

    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return $this
     */
    public function setChildren(array $children): static
    {
        $this->children = $children;
        $this->setFieldDefinitions(null);

        return $this;
    }

    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    /**
     * typehint "mixed" is required for asset-metadata-definitions bundle
     * since it doesn't extend Core Data Types
     *
     * @param Data|Layout $child
     */
    public function addChild(mixed $child): void
    {
        $this->children[] = $child;
        $this->setFieldDefinitions(null);
    }

    /**
     * @param Data[] $referencedFields
     */
    public function setReferencedFields(array $referencedFields): void
    {
        $this->referencedFields = $referencedFields;
        $this->setFieldDefinitions(null);
    }

    /**
     * @return Data[]
     */
    public function getReferencedFields(): array
    {
        return $this->referencedFields;
    }

    public function addReferencedField(Data $field): void
    {
        $this->referencedFields[] = $field;
        $this->setFieldDefinitions(null);
    }

    public function save(Localizedfield|AbstractData|DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
        $localizedFields = $this->getDataFromObjectParam($object, $params);
        if ($localizedFields instanceof Localizedfield) {
            if ((!isset($params['newParent']) || !$params['newParent']) && isset($params['isUpdate']) && $params['isUpdate'] && !$localizedFields->hasDirtyLanguages()) {
                return;
            }

            if ($object instanceof DataObject\Fieldcollection\Data\AbstractData || $object instanceof DataObject\Objectbrick\Data\AbstractData) {
                $object = $object->getObject();
            }

            $localizedFields->setObjectOmitDirty($object);

            $context = isset($params['context']) ? $params['context'] : null;
            $localizedFields->setContext($context);
            $localizedFields->loadLazyData();
            $localizedFields->save($params);
        }
    }

    public function load(Localizedfield|AbstractData|DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): Localizedfield
    {
        if ($object instanceof DataObject\Fieldcollection\Data\AbstractData || $object instanceof DataObject\Objectbrick\Data\AbstractData) {
            $object = $object->getObject();
        }

        $localizedFields = new Localizedfield();
        $localizedFields->setObject($object);
        $context = isset($params['context']) ? $params['context'] : null;
        $localizedFields->setContext($context);
        $localizedFields->load($object, $params);

        $localizedFields->resetDirtyMap();
        $localizedFields->resetLanguageDirtyMap();

        return $localizedFields;
    }

    public function delete(Localizedfield|AbstractData|DataObject\Objectbrick\Data\AbstractData|Concrete $object, array $params = []): void
    {
        $localizedFields = $this->getDataFromObjectParam($object, $params);

        if ($localizedFields instanceof Localizedfield) {
            $localizedFields->setObject($object);
            $context = $params['context'] ?? [];
            $localizedFields->setContext($context);
            $localizedFields->delete(true, false);
        }
    }

    /**
     * This method is called in DataObject\ClassDefinition::save() and is used to create the database table for the localized data
     *
     */
    public function classSaved(DataObject\ClassDefinition $class, array $params = []): void
    {
        // create a dummy instance just for updating the tables
        $localizedFields = new Localizedfield();
        $localizedFields->setClass($class);
        $context = $params['context'] ?? [];
        $localizedFields->setContext($context);
        $localizedFields->createUpdateTable($params);

        foreach ($this->getFieldDefinitions() as $fd) {
            if ($fd instanceof ClassSavedInterface) {
                $fd->classSaved($class, $params);
            }
        }
    }

    public function preGetData(mixed $container, array $params = []): mixed
    {
        if (
            !$container instanceof DataObject\Concrete &&
            !$container instanceof DataObject\Fieldcollection\Data\AbstractData &&
            !$container instanceof DataObject\Objectbrick\Data\AbstractData
        ) {
            throw new Exception('Localized Fields are only valid in Objects, Fieldcollections and Objectbricks');
        }

        $lf = $container->getObjectVar('localizedfields');
        if (!$lf instanceof Localizedfield) {
            $lf = new Localizedfield();

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

    public function getGetterCode(DataObject\Objectbrick\Definition|DataObject\ClassDefinition|DataObject\Fieldcollection\Definition $class): string
    {
        $code = '';
        if (!$class instanceof DataObject\Fieldcollection\Definition) {
            $code .= parent::getGetterCode($class);
        }

        foreach ($this->getFieldDefinitions() as $fd) {
            $code .= $fd->getGetterCodeLocalizedfields($class);
        }

        return $code;
    }

    public function getSetterCode(DataObject\Objectbrick\Definition|DataObject\ClassDefinition|DataObject\Fieldcollection\Definition $class): string
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
     * @internal
     */
    public function doEnrichFieldDefinition(Data $fieldDefinition, array $context = []): Data
    {
        if ($fieldDefinition instanceof FieldDefinitionEnrichmentInterface) {
            $context['class'] = $this;
            $fieldDefinition = $fieldDefinition->enrichFieldDefinition($context);
        }

        return $fieldDefinition;
    }

    public function getCacheTags(mixed $data, array $tags = []): array
    {
        if (!$data instanceof Localizedfield) {
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

    public function resolveDependencies(mixed $data): array
    {
        $dependencies = [];

        if (!$data instanceof Localizedfield) {
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
     * @return $this
     */
    public function setLayout(mixed $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function getBorder(): bool
    {
        return $this->border;
    }

    public function setBorder(bool $border): void
    {
        $this->border = $border;
    }

    /**
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setName(string $name): static
    {
        if ($name !== 'localizedfields') {
            throw new Exception('Localizedfields can only be named `localizedfields`, no other names are allowed');
        }

        $this->name = $name;

        return $this;
    }

    /**
     * @return $this
     */
    public function setRegion(?string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        $languages = Tool::getRequiredLanguages();

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
                        } catch (Exception $e) {
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
                                } catch (Exception $e) {
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

    private function getDataForValidity(Localizedfield $localizedObject, array $languages): array
    {
        if (!$localizedObject->getObject()
            || $localizedObject->getObject()->getType() != DataObject::OBJECT_TYPE_VARIANT) {
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

    public function getDiffDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        $return = [];

        $myname = $this->getName();

        if (!$data instanceof Localizedfield) {
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

    public function getDiffDataFromEditmode(array $data, DataObject\Concrete $object = null, array $params = []): Localizedfield
    {
        $localFields = $this->getDataFromObjectParam($object, $params);
        $localData = [];

        // get existing data
        if ($localFields instanceof Localizedfield) {
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

        $localizedFields = new Localizedfield($localData);
        $localizedFields->setObject($object);

        return $localizedFields;
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    public function getBlockedVarsForExport(): array
    {
        return [
            'fieldDefinitionsCache',
            'referencedFields',
            'blockedVarsForExport',
            'permissionView',
            'permissionEdit',
        ];
    }

    public function __sleep(): array
    {
        $vars = get_object_vars($this);
        $blockedVars = $this->getBlockedVarsForExport();

        foreach ($blockedVars as $blockedVar) {
            unset($vars[$blockedVar]);
        }

        return array_keys($vars);
    }

    public function rewriteIds(mixed $container, array $idMapping, array $params = []): mixed
    {
        $data = $this->getDataFromObjectParam($container, $params);

        $validLanguages = Tool::getValidLanguages();

        foreach ($validLanguages as $language) {
            foreach ($this->getFieldDefinitions() as $fd) {
                if ($fd instanceof IdRewriterInterface
                && $fd instanceof DataObject\ClassDefinition\Data) {
                    $d = $fd->rewriteIds($data, $idMapping, ['language' => $language]);
                    $data->setLocalizedValue($fd->getName(), $d, $language);
                }
            }
        }

        return $data;
    }

    public function getHideLabelsWhenTabsReached(): int
    {
        return $this->hideLabelsWhenTabsReached;
    }

    /**
     * @return $this
     */
    public function setHideLabelsWhenTabsReached(int $hideLabelsWhenTabsReached): static
    {
        $this->hideLabelsWhenTabsReached = $hideLabelsWhenTabsReached;

        return $this;
    }

    public function setMaxTabs(int $maxTabs): void
    {
        $this->maxTabs = $maxTabs;
    }

    public function getMaxTabs(): int
    {
        return $this->maxTabs;
    }

    public function getPermissionView(): ?array
    {
        return $this->permissionView;
    }

    public function setPermissionView(?array $permissionView): void
    {
        $this->permissionView = $permissionView;
    }

    public function getPermissionEdit(): ?array
    {
        return $this->permissionEdit;
    }

    public function setPermissionEdit(?array $permissionEdit): void
    {
        $this->permissionEdit = $permissionEdit;
    }

    public function getProvideSplitView(): bool
    {
        return $this->provideSplitView;
    }

    public function setProvideSplitView(bool $provideSplitView): void
    {
        $this->provideSplitView = $provideSplitView;
    }

    public function supportsDirtyDetection(): bool
    {
        return true;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    public function getTabPosition(): string
    {
        return $this->tabPosition ?? 'top';
    }

    public function setTabPosition(?string $tabPosition): void
    {
        $this->tabPosition = $tabPosition;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . Localizedfield::class;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . Localizedfield::class;
    }

    public function getPhpdocInputType(): ?string
    {
        return '\\'. Localizedfield::class . '|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return '\\' . Localizedfield::class . '|null';
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        if ($value instanceof Localizedfield) {
            $items = $value->getInternalData();
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

        return null;
    }

    public function denormalize(mixed $value, array $params = []): ?Localizedfield
    {
        if (is_array($value)) {
            $lf = new Localizedfield();
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

    public static function __set_state(array $data): static
    {
        $obj = new static();
        $obj->setValues($data);

        return $obj;
    }

    public function getFieldType(): string
    {
        return 'localizedfields';
    }
}
