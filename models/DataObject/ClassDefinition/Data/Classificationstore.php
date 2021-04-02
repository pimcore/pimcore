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

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\Element;
use Pimcore\Tool;

class Classificationstore extends Data implements CustomResourcePersistingInterface, TypeDeclarationSupportInterface
{
    use Element\ChildsCompatibilityTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'classificationstore';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Classificationstore';

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

    /** @var bool */
    public $localized;

    /**
     * @var int
     */
    public $storeId;

    /**
     * @var bool
     */
    public $hideEmptyData;

    /**
     * @var bool
     */
    public $disallowAddRemove;

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
     * @var array
     */
    public $allowedGroupIds;

    /**
     * @var array
     */
    public $activeGroupDefinitions = [];

    /**
     * @var int
     */
    public $maxItems;

    /**
     * @see Data::getDataForEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if (!$data instanceof DataObject\Classificationstore) {
            return [];
        }

        $fieldData = [];
        $metaData = [];
        $result = $this->doGetDataForEditMode($data, $object, $fieldData, $metaData, 1);

        // replace the real data with the data for the editmode
        foreach ($result['data'] as $language => &$groups) {
            foreach ($groups as $groupId => &$keys) {
                foreach ($keys as $keyId => &$keyValue) {
                    $keyConfig = DataObject\Classificationstore\DefinitionCache::get($keyId);
                    if ($keyConfig->getEnabled()) {
                        $fd = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
                        $keyValue = $fd->getDataForEditmode($keyValue, $object, $params);
                    }
                }
            }
        }

        $activeGroupIds = $this->recursiveGetActiveGroupsIds($object);

        $validLanguages = $this->getValidLanguages();

        foreach ($validLanguages as $language) {
            foreach ($activeGroupIds as $groupId => $enabled) {
                if (!$enabled) {
                    continue;
                }

                $relation = new DataObject\Classificationstore\KeyGroupRelation\Listing();
                $relation->setCondition("type = 'calculatedValue' and groupId = " . $relation->quote($groupId));
                $relation = $relation->load();
                foreach ($relation as $key) {
                    $keyId = $key->getKeyId();
                    $childDef = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($key);

                    $childData = new DataObject\Data\CalculatedValue($this->getName());
                    $childData->setContextualData('classificationstore', $this->getName(), null, $language, $groupId, $keyId, $childDef);
                    $childData = $childDef->getDataForEditmode($childData, $object, $params);
                    $result['data'][$language][$groupId][$keyId] = $childData;
                }
            }
        }

        $result['activeGroups'] = $data->getActiveGroups();
        $result['groupCollectionMapping'] = $data->getGroupCollectionMappings();

        return $result;
    }

    /**
     * @param DataObject\Classificationstore $data
     * @param DataObject\Concrete $object
     * @param array $fieldData structure: [language][groupId][keyId] = field data
     * @param array $metaData structure: [language][groupId][keyId] = array with meta info
     * @param int $level
     *
     * @return array
     */
    private function doGetDataForEditMode($data, $object, &$fieldData, &$metaData, $level = 1)
    {
        $class = $object->getClass();
        $inheritanceAllowed = $class->getAllowInherit();
        $inherited = false;

        $items = $data->getItems();

        foreach ($items as $groupId => $keys) {
            if (!isset($data->getActiveGroups()[$groupId])) {
                continue;
            }
            foreach ($keys as $keyId => $languages) {
                $keyConfig = DataObject\Classificationstore\DefinitionCache::get($keyId);
                if ($keyConfig->getEnabled()) {
                    $fd = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

                    foreach ($languages as $language => $value) {
                        $fdata = $value;
                        if (!isset($fieldData[$language][$groupId][$keyId]) || $fd->isEmpty($fieldData[$language][$groupId][$keyId])) {
                            // never override existing data
                            $fieldData[$language][$groupId][$keyId] = $fdata;
                            if (!$fd->isEmpty($fdata)) {
                                $metaData[$language][$groupId][$keyId] = ['inherited' => $level > 1, 'objectid' => $object->getId()];
                            }
                        }
                    }
                }
            }
        }

        // TODO
        if ($inheritanceAllowed) {
            // check if there is a parent with the same type
            $parent = DataObject\Service::hasInheritableParentObject($object);
            if ($parent) {
                // same type, iterate over all language and all fields and check if there is something missing
                if ($this->localized) {
                    $validLanguages = Tool::getValidLanguages();
                } else {
                    $validLanguages = [];
                }
                array_unshift($validLanguages, 'default');

                $foundEmptyValue = false;

                $activeGroupIds = $this->recursiveGetActiveGroupsIds($object);

                foreach ($validLanguages as $language) {
                    foreach ($activeGroupIds as $groupId => $enabled) {
                        if (!$enabled) {
                            continue;
                        }

                        $relation = new DataObject\Classificationstore\KeyGroupRelation\Listing();
                        $relation->setCondition('groupId = ' . $relation->quote($groupId));
                        $relation = $relation->load();
                        foreach ($relation as $key) {
                            $keyId = $key->getKeyId();
                            $fd = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($key);

                            if ($fd->isEmpty($fieldData[$language][$groupId][$keyId] ?? null)) {
                                $foundEmptyValue = true;
                                $inherited = true;
                                $metaData[$language][$groupId][$keyId] = ['inherited' => true, 'objectid' => $parent->getId()];
                            }
                        }
                    }
                }

                if ($foundEmptyValue) {
                    // still some values are missing, ask the parent
                    $getter = 'get' . ucfirst($this->getName());
                    $parentData = $parent->$getter();
                    $parentResult = $this->doGetDataForEditMode($parentData, $parent, $fieldData, $metaData, $level + 1);
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
     * @param array $containerData
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return DataObject\Classificationstore
     */
    public function getDataFromEditmode($containerData, $object = null, $params = [])
    {
        $classificationStore = $this->getDataFromObjectParam($object);

        if (!$classificationStore instanceof DataObject\Classificationstore) {
            $classificationStore = new DataObject\Classificationstore();
        }

        $data = $containerData['data'];
        $activeGroups = $containerData['activeGroups'];
        $groupCollectionMapping = $containerData['groupCollectionMapping'];

        $correctedMapping = [];

        foreach ($groupCollectionMapping as $groupId => $collectionId) {
            if (isset($activeGroups[$groupId]) && $activeGroups[$groupId]) {
                $correctedMapping[$groupId] = $collectionId;
            }
        }

        $classificationStore->setGroupCollectionMappings($correctedMapping);

        if (is_array($data)) {
            foreach ($data as $language => $groups) {
                foreach ($groups as $groupId => $keys) {
                    foreach ($keys as $keyId => $value) {
                        $keyConfig = $this->getKeyConfiguration($keyId);

                        $dataDefinition = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

                        $dataFromEditMode = $dataDefinition->getDataFromEditmode($value);
                        $activeGroups[$groupId] = true;

                        $classificationStore->setLocalizedKeyValue($groupId, $keyId, $dataFromEditMode, $language);
                    }
                }
            }
        }

        $activeGroupIds = array_keys($activeGroups);

        $classificationStore->setActiveGroups($activeGroups);

        // cleanup
        $existingGroupIds = $classificationStore->getGroupIdsWithData();
        if (is_array($existingGroupIds)) {
            foreach ($existingGroupIds as $existingGroupId) {
                if (!in_array($existingGroupId, $activeGroupIds)) {
                    $classificationStore->removeGroupData($existingGroupId);
                }
            }
        }

        return $classificationStore;
    }

    /**
     * @param mixed $data
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return 'not supported';
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param DataObject\Classificationstore|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        // this is handled directly in the template
        // /pimcore/modules/admin/views/scripts/object/preview-version.php
        return $data;
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
        return 'not supported';
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
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        $dataString = '';
        $getter = 'get' . ucfirst($this->getName());
        $classificationStore = $object->$getter();
        $items = $classificationStore->getItems();
        $activeGroups = $classificationStore->getActiveGroups();
        if ($items) {
            foreach ($items as $groupId => $keys) {
                if (!isset($activeGroups[$groupId])) {
                    continue;
                }
                foreach ($keys as $keyId => $values) {
                    $keyConfig = $this->getKeyConfiguration($keyId);
                    /** @var ResourcePersistenceAwareInterface $fieldDefinition */
                    $fieldDefinition = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

                    foreach ($values as $language => $value) {
                        $value = $fieldDefinition->getDataForResource($value, $object, $params);
                        if (is_array($value)) {
                            $value = implode(',', $value);
                        }
                        $dataString .= $value . ' ';
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
     * @return array
     *
     * @throws \Exception
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $this->doGetForWebserviceExport($object, $params, $result);

        return $result;
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     * @param array $result
     * @param int $level
     *
     * @throws \Exception
     */
    private function doGetForWebserviceExport($object, $params = [], &$result = [], $level = 0)
    {

        /** @var DataObject\Classificationstore $data */
        $data = $this->getDataFromObjectParam($object, $params);

        if ($this->isLocalized()) {
            $validLanguages = Tool::getValidLanguages();
        } else {
            $validLanguages = [];
        }
        array_unshift($validLanguages, 'default');

        if ($data) {
            $activeGroups = [];
            $items = $data->getActiveGroups();
            if (is_array($items)) {
                foreach ($items as $groupId => $groupData) {
                    $groupDef = DataObject\Classificationstore\GroupConfig::getById($groupId);
                    if (!is_null($groupDef)) {
                        $activeGroups[$groupId] = [
                            'id' => $groupId,
                            'name' => $groupDef->getName(). ' - ' . $groupDef->getDescription(),
                            'enabled' => $groupData,
                        ];
                    }
                }
            }

            $result['activeGroups'] = $activeGroups;
            $items = $data->getItems();

            foreach ($items as $groupId => $groupData) {
                if (!isset($activeGroups[$groupId])) {
                    continue;
                }
                $groupResult = [];

                foreach ($groupData as $keyId => $keyData) {
                    $keyConfig = DataObject\Classificationstore\DefinitionCache::get($keyId);
                    $fd = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
                    $context = [
                        'containerType' => 'classificationstore',
                        'fieldname' => $this->getName(),
                        'groupId' => $groupId,
                        'keyId' => $keyId,
                    ];

                    foreach ($validLanguages as $language) {
                        $context['language'] = $language;
                        $value = $fd->getForWebserviceExport($object, ['context' => $context, 'language' => $language]);
                        $resultItem = [
                            'id' => $keyId,
                            'name' => $keyConfig->getName(),
                            'description' => $keyConfig->getDescription(),
                            'value' => $value,
                        ];
                        if ($level > 0) {
                            $resultItem['inheritedFrom'] = $object->getId();
                        }
                        $groupResult[$language][] = $resultItem;
                    }
                }

                if ($groupResult) {
                    $groupDef = DataObject\Classificationstore\GroupConfig::getById($groupId);
                    if (!is_null($groupDef)) {
                        $groupResult = [
                            'id' => $groupId,
                            'name' => $groupDef->getName(). ' - ' . $groupDef->getDescription(),
                            'keys' => $groupResult,
                        ];
                    }
                }

                $result['groups'][] = $groupResult;
            }
        }

        $inheritanceAllowed = $object->getClass()->getAllowInherit();
        if (DataObject\AbstractObject::doGetInheritedValues($object)) {
            $parent = DataObject\Service::hasInheritableParentObject($object);
            if ($parent) {
                $foundEmptyValue = false;

                $activeGroupIds = $this->recursiveGetActiveGroupsIds($object);

                foreach ($validLanguages as $language) {
                    foreach ($activeGroupIds as $groupId => $enabled) {
                        if (!$enabled) {
                            continue;
                        }

                        $relation = new DataObject\Classificationstore\KeyGroupRelation\Listing();
                        $relation->setCondition('groupId = ' . $relation->quote($groupId));
                        $relation = $relation->load();
                        foreach ($relation as $key) {
                            $keyId = $key->getKeyId();
                            $fd = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($key);

                            if ($fd->isEmpty($result[$language][$groupId][$keyId] ?? null)) {
                                $foundEmptyValue = true;
                            }
                        }
                    }
                }

                if ($foundEmptyValue) {
                    // still some values are missing, ask the parent
                    $getter = 'get' . ucfirst($this->getName());
                    $parentData = $parent->$getter();
                    $parentResult = $this->doGetForWebserviceExport($parent, $params, $result, $level + 1);
                }
            }
        }
    }

    /**
     * @deprecated
     *
     * @param mixed $value
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @return mixed|null|DataObject\Classificationstore
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        if ($value) {
            $storeData = new DataObject\Classificationstore();
            $storeData->setFieldname($this->getName());
            $storeData->setObject($object);
            $activeGroupsLocal = [];
            $activeGroupsRemote = $value['activeGroups'];

            if ($activeGroupsRemote instanceof \stdClass) {
                $activeGroupsRemote = get_object_vars($activeGroupsRemote);
                foreach ($activeGroupsRemote as $data) {
                    $localId = $idMapper ? $idMapper->getMappedId('csGroup', $data['id']) : $data['id'];
                    $activeGroupsLocal[$localId] = $localId;
                }
            }

            $storeData->setActiveGroups($activeGroupsLocal);

            $groupsRemote = $value['groups'];
            if ($groupsRemote instanceof \stdClass) {
                $groupsRemote = get_object_vars($groupsRemote);
                foreach ($groupsRemote as $remoteGroupData) {
                    $localGroupId = $idMapper ? $idMapper->getMappedId('csGroup', $remoteGroupData['id']) : $remoteGroupData['id'];
                    $remoteKeys = $remoteGroupData['keys'];
                    $remoteKeys = (array) $remoteKeys;

                    foreach ($remoteKeys as $language => $keyList) {
                        foreach ($keyList as $keyData) {
                            $localKeyId = $idMapper ? $idMapper->getMappedId('csKey', $keyData->id) : $keyData->id;
                            $keyConfig = DataObject\Classificationstore\KeyConfig::getById($localKeyId);
                            $keyDef = DataObject\Classificationstore\Service::getFieldDefinitionFromJson(json_decode($keyConfig->getDefinition()), $keyConfig->getType());
                            $value = $keyData->value;
                            $value = $keyDef->getFromWebserviceImport($value, $object, []);
                            $storeData->setLocalizedKeyValue($localGroupId, $localKeyId, $value, $language);
                        }
                    }
                }
            }

            return $storeData;
        }
    }

    /**
     * @param DataObject\Classificationstore|null $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        if ($data instanceof DataObject\Classificationstore) {
            return empty($data->getItems());
        }

        return is_null($data);
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
        if (is_array($this->childs) && count($this->childs) > 0) {
            return true;
        }

        return false;
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
     * @param array $referencedFields
     */
    public function setReferencedFields($referencedFields)
    {
        $this->referencedFields = $referencedFields;
    }

    /**
     * @return array
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
     * @param DataObject\Concrete $object
     * @param array $params
     */
    public function save($object, $params = [])
    {
        $classificationStore = $this->getDataFromObjectParam($object);
        if ($classificationStore instanceof DataObject\Classificationstore) {
            $classificationStore->setObject($object);
            $classificationStore->save();
        }
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return DataObject\Classificationstore
     */
    public function load($object, $params = [])
    {
        $classificationStore = new DataObject\Classificationstore();
        $classificationStore->setObject($object);
        $classificationStore->setFieldname($this->getName());
        $classificationStore->load();

        return $classificationStore;
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     */
    public function delete($object, $params = [])
    {
        $classificationStore = $this->getDataFromObjectParam($object);

        if ($classificationStore instanceof DataObject\Classificationstore) {
            $classificationStore->setObject($object);
            $classificationStore->setFieldname($this->getName());
            $classificationStore->delete();
        }
    }

    /**
     * This method is called in DataObject\ClassDefinition::save() and is used to create the database table for the classification data
     *
     * @param DataObject\ClassDefinition $class
     * @param array $params
     */
    public function classSaved($class, $params = [])
    {
        $clasificationStore = new DataObject\Classificationstore();
        $clasificationStore->setClass($class);
        $clasificationStore->createUpdateTable();
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return DataObject\Localizedfield
     *
     * @throws \Exception
     */
    public function preGetData($object, $params = [])
    {
        if (!$object instanceof DataObject\Concrete) {
            throw new \Exception('Classification store fields are only valid in Objects');
        }

        if (!$object->getObjectVar($this->getName()) instanceof DataObject\Classificationstore) {
            $store = new DataObject\Classificationstore();
            $store->setObject($object);
            $store->setFieldname($this->getName());

            $object->{'set' . $this->getName()}($store);
        }

        return $object->getObjectVar($this->getName());
    }

    /**
     * @param int $keyId
     *
     * @return mixed
     */
    public function getKeyConfiguration($keyId)
    {
        /** @var DataObject\Classificationstore\KeyConfig $keyConfig */
        $keyConfig = DataObject\Classificationstore\DefinitionCache::get($keyId);

        return $keyConfig;
    }

    /**
     * @param string|int|null $height
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
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * @param string $title
     *
     * @return $this|void
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param int $width
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
     * @param DataObject\Classificationstore $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        $activeGroups = $data->getActiveGroups();
        if (!$activeGroups) {
            return;
        }
        $items = $data->getItems();
        $validLanguages = $this->getValidLanguages();
        $subItems = [];
        $getInheritedValues = DataObject\AbstractObject::doGetInheritedValues();

        if (!$omitMandatoryCheck) {
            if ($this->maxItems > 0 && count($activeGroups) > $this->maxItems) {
                throw new Model\Element\ValidationException(
                    'Groups in field [' . $this->getName() . '] is bigger than ' . $this->getMaxItems()
                );
            }

            foreach ($activeGroups as $activeGroupId => $enabled) {
                if ($enabled) {
                    $groupDefinition = DataObject\Classificationstore\GroupConfig::getById($activeGroupId);
                    if (!$groupDefinition) {
                        continue;
                    }

                    $keyGroupRelations = $groupDefinition->getRelations();

                    /** @var DataObject\Classificationstore\KeyGroupRelation $keyGroupRelation */
                    foreach ($keyGroupRelations as $keyGroupRelation) {
                        foreach ($validLanguages as $validLanguage) {
                            $keyId = $keyGroupRelation->getKeyId();

                            $object = $data->getObject();
                            if ($object->getClass()->getAllowInherit()) {
                                DataObject\AbstractObject::setGetInheritedValues(true);
                                $value = $data->getLocalizedKeyValue($activeGroupId, $keyId, $validLanguage, true);
                                DataObject\AbstractObject::setGetInheritedValues($getInheritedValues);
                            } else {
                                $value = $items[$activeGroupId][$keyId][$validLanguage] ?? null;
                            }

                            $keyDef = DataObject\Classificationstore\Service::getFieldDefinitionFromJson(json_decode($keyGroupRelation->getDefinition()), $keyGroupRelation->getType());

                            if ($keyGroupRelation->isMandatory()) {
                                $keyDef->setMandatory(1);
                            }
                            try {
                                $keyDef->checkValidity($value);
                            } catch (\Exception $exception) {
                                $subItems[] = new Model\Element\ValidationException(
                                    $exception->getMessage() . ' (' . $validLanguage . ')',
                                    $exception->getCode(),
                                    $exception->getPrevious()
                                );
                            }
                        }
                    }
                }
            }
        }

        if ($subItems) {
            $messages = array_map(function (Model\Element\ValidationException $validationException) {
                return $validationException->getMessage();
            }, $subItems);

            $validationException = new Model\Element\ValidationException(implode(', ', $messages));
            $validationException->setSubItems($subItems);
            throw $validationException;
        }
    }

    /**
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @throws \Exception
     */
    public function getDiffDataForEditmode($data, $object = null, $params = [])
    {
        throw new \Exception('not supported');
    }

    /**
     * @param array $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @throws \Exception
     */
    public function getDiffDataFromEditmode($data, $object = null, $params = [])
    {
        throw new \Exception('not supported');
    }

    /** True if change is allowed in edit mode.
     * @param DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return false;
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
        $this->labelWidth = (int) $labelWidth;
    }

    /**
     * @return int
     */
    public function getLabelWidth()
    {
        return $this->labelWidth;
    }

    /**
     * @param int $maxItems
     */
    public function setMaxItems($maxItems)
    {
        $this->maxItems = (int) $maxItems;
    }

    /**
     * @return int
     */
    public function getMaxItems()
    {
        return $this->maxItems;
    }

    /**
     * @return bool
     */
    public function isLocalized()
    {
        return $this->localized;
    }

    /**
     * @param bool $localized
     */
    public function setLocalized($localized)
    {
        $this->localized = $localized;
    }

    /**
     * @param DataObject\Concrete|null $object
     * @param array $mergedMapping
     *
     * @return array|null
     *
     * @todo: Method returns void/null, should be boolean or null
     */
    public function recursiveGetActiveGroupCollectionMapping($object, $mergedMapping = [])
    {
        if (!$object) {
            return null;
        }

        $getter = 'get' . ucfirst($this->getName());
        /** @var DataObject\Classificationstore $classificationStore */
        $classificationStore = $object->$getter();
        $mapping = $classificationStore->getGroupCollectionMappings();

        if (is_array($mapping)) {
            foreach ($mapping as $groupId => $collectionId) {
                if (!isset($mergedMapping[$groupId]) && $collectionId) {
                    $mergedMapping[$groupId] = $collectionId;
                }
            }
        }

        $class = $object->getClass();
        $inheritanceAllowed = $class->getAllowInherit();

        if ($inheritanceAllowed) {
            $parent = DataObject\Service::hasInheritableParentObject($object);
            if ($parent) {
                $mergedMapping = $this->recursiveGetActiveGroupCollectionMapping($parent, $mergedMapping);
            }
        }

        return $mergedMapping;
    }

    /**
     * @param DataObject\Concrete $object
     * @param array $activeGroups
     *
     * @return array|null
     *
     * @todo: Method returns void/null, should be boolean or null
     */
    public function recursiveGetActiveGroupsIds($object, $activeGroups = [])
    {
        if (!$object) {
            return null;
        }

        $getter = 'get' . ucfirst($this->getName());
        /** @var DataObject\Classificationstore $classificationStore */
        $classificationStore = $object->$getter();
        $activeGroupIds = $classificationStore->getActiveGroups();

        if ($activeGroupIds) {
            $activeGroups = array_keys($activeGroupIds, true, true);
        }

        $class = $object->getClass();
        $inheritanceAllowed = $class->getAllowInherit();

        if ($inheritanceAllowed) {
            $parent = DataObject\Service::hasInheritableParentObject($object);
            if ($parent) {
                $activeGroups = $this->recursiveGetActiveGroupsIds($parent, $activeGroups);
            }
        }

        return $activeGroups;
    }

    /**
     * Override point for Enriching the layout definition before the layout is returned to the admin interface.
     *
     * @param DataObject\Concrete $object
     * @param array $context additional contextual data
     *
     * @throws \Exception
     */
    public function enrichLayoutDefinition($object, $context = [])
    {
        $this->activeGroupDefinitions = [];
        $activeGroupIds = $this->recursiveGetActiveGroupsIds($object);

        if (!$activeGroupIds) {
            return;
        }

        $filteredGroupIds = array_keys($activeGroupIds, true, true);

        $groupList = new DataObject\Classificationstore\GroupConfig\Listing();
        $groupList->setCondition('`id` in (?)', implode(',', $filteredGroupIds));
        $groupList->setOrderKey(['id']);
        $groupList->setOrder(['ASC']);

        /** @var DataObject\Classificationstore\GroupConfig $group */
        foreach ($groupList->load() as $group) {
            $keyList = [];

            $relation = new DataObject\Classificationstore\KeyGroupRelation\Listing();
            $relation->setCondition('`groupId` = ?', $group->getId());
            $relation->setOrderKey(['sorter', 'id']);
            $relation->setOrder(['ASC', 'ASC']);
            /** @var DataObject\Classificationstore\KeyGroupRelation $keyGroupRelation */
            foreach ($relation->load() as $keyGroupRelation) {
                if (!$keyGroupRelation->isEnabled()) {
                    continue;
                }
                $definition = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyGroupRelation);

                // changes here also have an effect here: "bundles/AdminBundle/Resources/public/js/pimcore/object/tags/classificationstore.js"
                $fallbackTooltip = $definition->getName();
                if (!empty($keyGroupRelation->getDescription())) {
                    $fallbackTooltip .= ' - ' . $keyGroupRelation->getDescription();
                }

                $definition->setTooltip($definition->getTooltip() ?: $fallbackTooltip);

                if (method_exists($definition, '__wakeup')) {
                    $definition->__wakeup();
                }

                if ($definition) {
                    $definition->setMandatory($definition->getMandatory() || $keyGroupRelation->isMandatory());
                    if (method_exists($definition, 'enrichLayoutDefinition')) {
                        $context['object'] = $object;
                        $context['class'] = $object->getClass();
                        $context['ownerType'] = 'classificationstore';
                        $context['ownerName'] = $this->getName();
                        $context['keyId'] = $keyGroupRelation->getKeyId();
                        $context['groupId'] = $keyGroupRelation->getGroupId();
                        $context['keyDefinition'] = $definition;
                        $definition = $definition->enrichLayoutDefinition($object, $context);
                    }
                }

                $keyList[] = [
                    'name' => $keyGroupRelation->getName(),
                    'id' => $keyGroupRelation->getKeyId(),
                    'description' => $keyGroupRelation->getDescription(),
                    'definition' => $definition,
                ];
            }

            $this->activeGroupDefinitions[$group->getId()] = [
                'name' => $group->getName(),
                'id' => $group->getId(),
                'description' => $group->getDescription(),
                'keys' => $keyList,
            ];
        }

        $groupCollectionMapping = $this->recursiveGetActiveGroupCollectionMapping($object);
        if (!empty($groupCollectionMapping)) {
            $collectionIds = array_values($groupCollectionMapping);

            $relation = new DataObject\Classificationstore\CollectionGroupRelation\Listing();
            $relation->setCondition('`colId` IN (?)', implode(',', $collectionIds));

            $sorting = [];
            /** @var DataObject\Classificationstore\CollectionGroupRelation $item */
            foreach ($relation->load() as $item) {
                $sorting[$item->getGroupId()] = $item->getSorter();
            }

            usort($this->activeGroupDefinitions, static function ($a, $b) use ($sorting) {
                $s1 = $sorting[$a['id']] ?: 0;
                $s2 = $sorting[$b['id']] ?: 0;

                return $s1 <=> $s2;
            });
        }
    }

    /**
     * @return array
     */
    public function getAllowedGroupIds()
    {
        return $this->allowedGroupIds;
    }

    /**
     * @param array $allowedGroupIds
     *
     * @todo: $parts is not definied here, should it be definied as empty array or null
     */
    public function setAllowedGroupIds($allowedGroupIds)
    {
        $parts = [];
        if (is_string($allowedGroupIds) && !empty($allowedGroupIds)) {
            $allowedGroupIds = str_replace([' ', "\n"], '', $allowedGroupIds);
            $parts = explode(',', $allowedGroupIds);
        } elseif (is_array($allowedGroupIds)) {
            $parts = $allowedGroupIds;
        }

        $this->allowedGroupIds = $parts;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId ? $this->storeId : 1;
    }

    /**
     * @param int $storeId
     *
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId ? $storeId : 1;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getValidLanguages()
    {
        if ($this->localized) {
            $validLanguages = Tool::getValidLanguages();
        } else {
            $validLanguages = [];
        }
        array_unshift($validLanguages, 'default');

        return $validLanguages;
    }

    /**
     * @return bool
     */
    public function getHideEmptyData()
    {
        return $this->hideEmptyData;
    }

    /**
     * @param bool $hideEmptyData
     *
     * @return $this
     */
    public function setHideEmptyData($hideEmptyData)
    {
        $this->hideEmptyData = (bool) $hideEmptyData;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDisallowAddRemove()
    {
        return $this->disallowAddRemove;
    }

    /**
     * @param bool $disallowAddRemove
     *
     * @return $this
     */
    public function setDisallowAddRemove($disallowAddRemove)
    {
        $this->disallowAddRemove = $disallowAddRemove;

        return $this;
    }
}
