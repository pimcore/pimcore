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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Tool;
use Pimcore\Model\Element;

class Classificationstore extends Model\Object\ClassDefinition\Data
{
    use Element\ChildsCompatibilityTrait;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "classificationstore";


    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "\\Pimcore\\Model\\Object\\Classificationstore";

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
     * @var integer
     */
    public $width;

    /**
     * @var integer
     */
    public $height;

    /**
     * @var integer
     */
    public $maxTabs;

    /**
     * @var integer
     */
    public $labelWidth;

    /** @var  bool */
    public $localized;

    /**
     * @var integer
     */
    public $storeId;

    /**
     * contains further localized field definitions if there are more than one localized fields in on class
     * @var array
     */
    protected $referencedFields = [];

    /**
     * @var array
     */
    public $fieldDefinitionsCache;

    /**
     * @var array
     */
    public $allowedGroupIds;


    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        $fieldData = [];
        $metaData = [];

        if (!$data instanceof Object\Classificationstore) {
            return [];
        }

        $result = $this->doGetDataForEditMode($data, $object, $fieldData, $metaData, 1);

        // replace the real data with the data for the editmode
        foreach ($result["data"] as $language => &$groups) {
            foreach ($groups as $groupId => &$keys) {
                foreach ($keys as $keyId => &$keyValue) {
                    $keyConfig = Object\Classificationstore\DefinitionCache::get($keyId);
                    if ($keyConfig->getEnabled()) {
                        $fd = Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
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

                $relation = new Object\Classificationstore\KeyGroupRelation\Listing();
                $relation->setCondition("type = 'calculatedValue' and groupId = " . $relation->quote($groupId));
                $relation = $relation->load();
                foreach ($relation as $key) {
                    $keyId = $key->getKeyId();
                    $childDef = Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($key);

                    $childData = new Object\Data\CalculatedValue($this->getName());
                    $childData->setContextualData("classificationstore", $this->getName(), null, $language, $groupId, $keyId, $childDef);
                    $childData = $childDef->getDataForEditmode($childData, $object, $params);
                    $result["data"][$language][$groupId][$keyId]= $childData;
                }
            }
        }

        $result["activeGroups"] = $data->getActiveGroups();
        $result["groupCollectionMapping"] = $data->getGroupCollectionMappings();

        return $result;
    }

    /**
     * @param $data
     * @param $object
     * @param $fieldData
     * @param $metaData
     * @param int $level
     * @return array
     */
    private function doGetDataForEditMode($data, $object, &$fieldData, &$metaData, $level = 1)
    {
        $class = $object->getClass();
        $inheritanceAllowed = $class->getAllowInherit();
        $inherited = false;

        $items = $data->getItems();

        foreach ($items  as $groupId => $keys) {
            foreach ($keys as $keyId => $languages) {
                $keyConfig = Object\Classificationstore\DefinitionCache::get($keyId);
                if ($keyConfig->getEnabled()) {
                    $fd = Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);


                    foreach ($languages as $language => $value) {
                        $fdata = $value;
                        if (!isset($fieldData[$language][$groupId][$keyId]) || $fd->isEmpty($fieldData[$language][$groupId][$keyId])) {
                            // never override existing data
                            $fieldData[$language][$groupId][$keyId] = $fdata;
                            if (!$fd->isEmpty($fdata)) {
                                $metaData[$language][$groupId][$keyId] = ["inherited" => $level > 1, "objectid" => $object->getId()];
                            }
                        }
                    }
                }
            }
        }


        // TODO
        if ($inheritanceAllowed) {
            // check if there is a parent with the same type
            $parent = Object\Service::hasInheritableParentObject($object);
            if ($parent) {
                // same type, iterate over all language and all fields and check if there is something missing
                if ($this->localized) {
                    $validLanguages = Tool::getValidLanguages();
                } else {
                    $validLanguages = [];
                }
                array_unshift($validLanguages, "default");

                $foundEmptyValue = false;

                $activeGroupIds = $this->recursiveGetActiveGroupsIds($object);

                foreach ($validLanguages as $language) {
                    foreach ($activeGroupIds as $groupId => $enabled) {
                        if (!$enabled) {
                            continue;
                        }

                        $relation = new Object\Classificationstore\KeyGroupRelation\Listing();
                        $relation->setCondition("groupId = " . $relation->quote($groupId));
                        $relation = $relation->load();
                        foreach ($relation as $key) {
                            $keyId = $key->getKeyId();
                            $fd = Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($key);

                            if ($fd->isEmpty($fieldData[$language][$groupId][$keyId])) {
                                $foundEmptyValue = true;
                                $inherited = true;
                                $metaData[$language][$groupId][$keyId] = ["inherited" => true, "objectid" => $parent->getId()];
                            }
                        }
                    }
                }

                if ($foundEmptyValue) {
                    // still some values are passing, ask the parent
                    $getter = "get" . ucfirst($this->getName());
                    $parentData = $parent->$getter();
                    $parentResult = $this->doGetDataForEditMode($parentData, $parent, $fieldData, $metaData, $level + 1);
                }
            }
        }

        $result = [
            "data" => $fieldData,
            "metaData" => $metaData,
            "inherited" => $inherited
        ];

        return $result;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param string $containerData
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataFromEditmode($containerData, $object = null, $params = [])
    {
        $classificationStore = $this->getDataFromObjectParam($object);

        if (!$classificationStore instanceof Object\Classificationstore) {
            $classificationStore = new Object\Classificationstore();
        }

        $data = $containerData["data"];
        $activeGroups = $containerData["activeGroups"];
        $groupCollectionMapping = $containerData["groupCollectionMapping"];

        $correctedMapping = [];

        foreach ($groupCollectionMapping as $groupId => $collectionId) {
            if ($activeGroups[$groupId]) {
                $correctedMapping[$groupId] = $collectionId;
            }
        }

        $classificationStore->setGroupCollectionMappings($correctedMapping);

        if (is_array($data)) {
            foreach ($data as $language => $groups) {
                foreach ($groups as $groupId => $keys) {
                    foreach ($keys as $keyId => $value) {
                        $keyConfig = $this->getKeyConfiguration($keyId);

                        $dataDefinition = Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

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
     * @param $data
     * @param null $object
     * @param mixed $params
     * @return \stdClass
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return "not supported";
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param null|Object\AbstractObject $object
     * @param mixed $params
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
     * @abstract
     * @param Object\AbstractObject $object
     * @param array $params
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        return "not supported";
    }

    /**
     * @param string $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return null
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return;
    }

    /**
     * @param $object
     * @param mixed $params
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        $dataString = "";
        $getter = "get" . ucfirst($this->getName());
        $classificationStore = $object->$getter();
        $items = $classificationStore->getItems();
        if ($items) {
            foreach ($items as $groupId => $keys) {
                foreach ($keys as $keyId => $values) {
                    $keyConfig = $this->getKeyConfiguration($keyId);
                    $fieldDefinition = Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

                    foreach ($values as $language => $value) {
                        $value = $fieldDefinition->getDataForResource($value, $object, $params);
                        $dataString .= $value . " ";
                    }
                }
            }
        }


        return $dataString;
    }

    /**
     * @param Object\AbstractObject $object
     * @param mixed $params
     * @throws \Exception
     */
    public function getForWebserviceExport($object, $params = [])
    {
        /** @var  $data Object\Classificationstore */
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data) {
            if ($this->isLocalized()) {
                $validLanguages = Tool::getValidLanguages();
            } else {
                $validLanguages = [];
            }
            array_unshift($validLanguages, "default");

            $result = [];
            $activeGroups = [];
            $items = $data->getActiveGroups();
            if (is_array($items)) {
                foreach ($items as $groupId => $groupData) {
                    $groupDef = Object\Classificationstore\GroupConfig::getById($groupId);
                    $activeGroups[] = [
                        "id" => $groupId,
                        "name" => $groupDef->getName(). " - " . $groupDef->getDescription(),
                        "enabled" => $groupData
                    ];
                }
            }

            $result["activeGroups" ] = $activeGroups;
            $items = $data->getItems();

            foreach ($items as $groupId => $groupData) {
                $groupResult = [];

                foreach ($groupData as $keyId => $keyData) {
                    $keyConfig = Object\Classificationstore\DefinitionCache::get($keyId);
                    $fd = Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
                    $context = [
                        "containerType" => "classificationstore",
                        "fieldname" => $this->getName(),
                        "groupId" => $groupId,
                        "keyId" => $keyId
                    ];

                    foreach ($validLanguages as $language) {
                        $value = $fd->getForWebserviceExport($object, ["context" => $context, "language" => $language]);
                        $groupResult[$language][] = [
                            "id" => $keyId,
                            "name" => $keyConfig->getName(),
                            "description" => $keyConfig->getDescription(),
                            "value" => $value
                        ];
                    }
                }

                if ($groupResult) {
                    $groupDef = Object\Classificationstore\GroupConfig::getById($groupId);
                    $groupResult = [
                        "id" => $groupId,
                        "name" => $groupDef->getName(). " - " . $groupDef->getDescription(),
                        "keys" => $groupResult
                    ];
                }

                $result["groups"][] = $groupResult;
            }

            return $result;
        }
    }

    /**
     * @param mixed $value
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @param IdMapper $idMapper
     * @return mixed|null|Object\Classificationstore
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        if ($value) {
            $storeData = new Object\Classificationstore();
            $storeData->setFieldname($this->getName());
            $storeData->setObject($object);
            $activeGroupsLocal = [];
            $activeGroupsRemote = $value->activeGroups;
            if (is_array($activeGroupsRemote)) {
                foreach ($activeGroupsRemote as $data) {
                    $remoteId = $data->id;
                    $localId = $idMapper->getMappedId("csGroup", $remoteId);
                    $activeGroupsLocal[$localId] = $localId;
                }
            }

            $storeData->setActiveGroups($activeGroupsLocal);


            $groupsRemote = $value->groups;
            if (is_array($groupsRemote)) {
                foreach ($groupsRemote as $remoteGroupData) {
                    $remoteGroupId = $remoteGroupData->id;
                    $localGroupId = $idMapper->getMappedId("csGroup", $remoteGroupId);
                    $remoteKeys = $remoteGroupData->keys;
                    $remoteKeys = (array) $remoteKeys;

                    foreach ($remoteKeys as $language => $keyList) {
                        foreach ($keyList as $keyData) {
                            $remoteKeyId = $keyData->id;
                            $localKeyId = $idMapper->getMappedId("csKey", $remoteKeyId);
                            $keyConfig = Object\Classificationstore\KeyConfig::getById($localKeyId);
                            $keyDef = Object\Classificationstore\Service::getFieldDefinitionFromJson(json_decode($keyConfig->getDefinition()), $keyConfig->getType());
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
     * @return array
     */
    public function getChildren()
    {
        return $this->childs;
    }

    /**
     * @param array $children
     * @return $this
     */
    public function setChildren($children)
    {
        $this->childs = $children;
        $this->fieldDefinitionsCache = null;

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasChildren()
    {
        if (is_array($this->childs) && count($this->childs) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param mixed $child
     * @return void
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
     * @param $field
     */
    public function addReferencedField($field)
    {
        $this->referencedFields[] = $field;
    }

    /**
     * @param mixed $data
     * @param array $blockedKeys
     * @return void
     */
    public function setValues($data = [], $blockedKeys = [])
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $blockedKeys)) {
                $method = "set" . $key;
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }

        return $this;
    }

    /**
     * @param $object
     * @param array $params
     */
    public function save($object, $params = [])
    {
        $classificationStore = $this->getDataFromObjectParam($object);
        if ($classificationStore instanceof Object\Classificationstore) {
            $classificationStore->setObject($object);
            $classificationStore->save();
        }
    }

    /**
     * @param $object
     * @param array $params
     * @return Object\Classificationstore
     */
    public function load($object, $params = [])
    {
        $classificationStore = new Object\Classificationstore();
        $classificationStore->setObject($object);
        $classificationStore->setFieldname($this->getName());
        $classificationStore->load();

        return $classificationStore;
    }

    /**
     * @param $object
     */
    public function delete($object)
    {
        $classificationStore = $this->getDataFromObjectParam($object);

        if ($classificationStore instanceof Object\Classificationstore) {
            $classificationStore->setObject($object);
            $classificationStore->setFieldname($this->getName());
            $classificationStore->delete();
        }
    }

    /**
     * This method is called in Object|Class::save() and is used to create the database table for the classification data
     * @return void
     */
    public function classSaved($class, $params = [])
    {
        $clasificationStore = new Object\Classificationstore();
        $clasificationStore->setClass($class);
        $clasificationStore->createUpdateTable();
    }

    /**
     * @param $object
     * @param array $params
     * @return Object\Localizedfield
     * @throws \Exception
     */
    public function preGetData($object, $params = [])
    {
        if (!$object instanceof Object\Concrete) {
            throw new \Exception("Localized Fields are only valid in Objects");
        }

        if (!$object->{$this->getName()} instanceof Object\Classificationstore) {
            $store = new Object\Classificationstore();
            $store->setObject($object);
            $store->setFieldname($this->getName());

            $object->{$this->getName()} = $store;
        }

        return $object->{$this->getName()};
    }

    /**
     * @param $class
     * @return string
     */
    public function getGetterCode($class)
    {
        $code = "";
        $code .= parent::getGetterCode($class);

        return $code;
    }

    /**
     * @param $class
     * @return string
     */
    public function getSetterCode($class)
    {
        $code = "";
        $code .= parent::getSetterCode($class);

        return $code;
    }

    /**
     * @param $keyId
     * @return mixed
     */
    public function getKeyConfiguration($keyId)
    {
        /** @var $keyConfig Object\Classificationstore\KeyConfig */
        $keyConfig = Object\Classificationstore\DefinitionCache::get($keyId);

        return $keyConfig;
    }


    /**
     * @param $height
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
     * @param $layout
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
     * @return $this|void
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
     * @param $region
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
     * @param $width
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
     * @param boolean $omitMandatoryCheck
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
        $errors = [];

        if (!$omitMandatoryCheck) {
            foreach ($activeGroups as $activeGroupId => $enabled) {
                if ($enabled) {
                    $groupDefinition = Object\Classificationstore\GroupConfig::getById($activeGroupId);
                    if (!$groupDefinition) {
                        continue;
                    }

                    /** @var $keyGroupRelation Object\Classificationstore\KeyGroupRelation */
                    $keyGroupRelations = $groupDefinition->getRelations();

                    foreach ($keyGroupRelations as $keyGroupRelation) {
                        foreach ($validLanguages as $validLanguage) {
                            $keyId = $keyGroupRelation->getKeyId();
                            $value = $items[$activeGroupId][$keyId][$validLanguage];

                            $keyDef = Object\Classificationstore\Service::getFieldDefinitionFromJson(json_decode($keyGroupRelation->getDefinition()), $keyGroupRelation->getType());

                            if ($keyGroupRelation->isMandatory()) {
                                $keyDef->setMandatory(1);
                            }
                            try {
                                $keyDef->checkValidity($value);
                            } catch (\Exception $e) {
                                $errors[] = $e;
                            }
                        }
                    }
                }
            }
        }
        if ($errors) {
            $messages = [];
            foreach ($errors as $e) {
                $messages[]= $e->getMessage() . " (" . $validLanguage . ")";
            }
            $validationException = new Model\Element\ValidationException(implode(", ", $messages));
            $validationException->setSubItems($errors);
            throw $validationException;
        }
    }


    /**
     * @param mixed $data
     * @param null $object
     * @param mixed $params
     * @throws \Exception
     */
    public function getDiffDataForEditmode($data, $object = null, $params = [])
    {
        throw new \Exception("not supported");
    }

    /**
     * @param $data
     * @param null $object
     * @param mixed $params
     * @throws \Exception
     */
    public function getDiffDataFromEditmode($data, $object = null, $params = [])
    {
        throw new \Exception("not supported");
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
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
     * @return boolean
     */
    public function isLocalized()
    {
        return $this->localized;
    }

    /**
     * @param boolean $localized
     */
    public function setLocalized($localized)
    {
        $this->localized = $localized;
    }

    /**
     * @param $object
     * @param array $mergedMapping
     * @return array|boolean
     */
    public function recursiveGetActiveGroupCollectionMapping($object, $mergedMapping = [])
    {
        if (!$object) {
            return false;
        }

        $getter = "get" . ucfirst($this->getName());
        /** @var  $classificationStore Object\Classificationstore */
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
            $parent = Object\Service::hasInheritableParentObject($object);
            if ($parent) {
                $mergedMapping = $this->recursiveGetActiveGroupCollectionMapping($parent, $mergedMapping);
            }
        }

        return $mergedMapping;
    }


    /**
     * @param $object Object\Concrete
     * @param array $activeGroups
     * @return array|boolean
     */
    public function recursiveGetActiveGroupsIds($object, $activeGroups = [])
    {
        if (!$object) {
            return false;
        }

        $getter = "get" . ucfirst($this->getName());
        /** @var  $classificationStore Object\Classificationstore */
        $classificationStore = $object->$getter();
        $activeGroupIds = $classificationStore->getActiveGroups();

        if ($activeGroupIds) {
            foreach ($activeGroupIds as $groupId => $enabled) {
                if ($enabled) {
                    $activeGroups[$groupId] = $enabled;
                }
            }
        }

        $class = $object->getClass();
        $inheritanceAllowed = $class->getAllowInherit();

        if ($inheritanceAllowed) {
            $parent = Object\Service::hasInheritableParentObject($object);
            if ($parent) {
                $activeGroups = $this->recursiveGetActiveGroupsIds($parent, $activeGroups);
            }
        }

        return $activeGroups;
    }

    /** Override point for Enriching the layout definition before the layout is returned to the admin interface.
     * @param $object Object\Concrete
     * @param array $context additional contextual data
     */
    public function enrichLayoutDefinition($object, $context = [])
    {
        $groupCollectionMapping = $this->recursiveGetActiveGroupCollectionMapping($object);

        $this->activeGroupDefinitions = [];
        $activeGroupIds = $this->recursiveGetActiveGroupsIds($object);

        if (!$activeGroupIds) {
            return;
        }

        $filteredGroupIds = [];

        foreach ($activeGroupIds as $groupId => $enabled) {
            if ($enabled) {
                $filteredGroupIds[] = $groupId;
            }
        }

        $condition = "ID in (" . implode(',', $filteredGroupIds) . ")";
        $groupList = new Object\Classificationstore\GroupConfig\Listing();
        $groupList->setCondition($condition);
        $groupList->setOrder(["ASC", "ASC"]);
        $groupList = $groupList->load();

        /** @var  $group Object\Classificationstore\GroupConfig */
        foreach ($groupList as $group) {
            $keyList = [];

            $relation = new Object\Classificationstore\KeyGroupRelation\Listing();
            $relation->setCondition("groupId = " . $relation->quote($group->getId()));
            $relation->setOrderKey(["sorter", "id"]);
            $relation->setOrder(["ASC", "ASC"]);
            $relation = $relation->load();
            /** @var  $key Object\Classificationstore\KeyGroupRelation */
            foreach ($relation as $key) {
                if (!$key->isEnabled()) {
                    continue;
                }
                $definition = \Pimcore\Model\Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($key);
                $definition->setTooltip($definition->getName() . " - " . $key->getDescription());

                if (method_exists($definition, "__wakeup")) {
                    $definition->__wakeup();
                }

                if ($definition) {
                    $definition->setMandatory($definition->getMandatory() || $key->isMandatory());
                }

                $keyList[] = [
                    "name" => $key->getName(),
                    "id" => $key->getKeyId(),
                    "description" => $key->getDescription(),
                    "definition" => $definition
                ];
            }

            $this->activeGroupDefinitions[$group->getId()] = [
                "name" => $group->getName(),
                "id" => $group->getId(),
                "description" => $group->getDescription(),
                "keys" => $keyList
            ];
        }

        if ($groupCollectionMapping) {
            $collectionIds = array_values($groupCollectionMapping);

            $relation = new Object\Classificationstore\CollectionGroupRelation\Listing();
            $condition = "colId IN (" . implode(",", $collectionIds) . ")";
            $relation->setCondition($condition);
            $relation = $relation->load();

            $sorting = [];
            /** @var $item Object\Classificationstore\CollectionGroupRelation */
            foreach ($relation as $item) {
                $sorting[$item->getGroupId()] = $item->getSorter();
            }

            usort($this->activeGroupDefinitions, function ($a, $b) use ($sorting) {
                $s1 = $sorting[$a["id"]] ? $sorting[$a["id"]] : 0;
                $s2 = $sorting[$b["id"]] ? $sorting[$b["id"]] : 0;

                if ($s1 < $s2) {
                    return 1;
                } elseif ($s2 > $s1) {
                    return -1;
                } else {
                    return 0;
                }
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
     */
    public function setAllowedGroupIds($allowedGroupIds)
    {
        $parts = [];

        if (is_string($allowedGroupIds) && !empty($allowedGroupIds)) {
            $allowedGroupIds = str_replace([" ", "\n"], "", $allowedGroupIds);
            $parts = explode(",", $allowedGroupIds);
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
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId ? $storeId : 1;
    }

    /**
     * @return array|\string[]
     */
    public function getValidLanguages()
    {
        if ($this->localized) {
            $validLanguages = Tool::getValidLanguages();
        } else {
            $validLanguages = [];
        }
        array_unshift($validLanguages, "default");

        return $validLanguages;
    }
}
