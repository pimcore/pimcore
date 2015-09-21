<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Tool;

class Classificationstore extends Model\Object\ClassDefinition\Data
{

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
    public $phpdocType = "array";

    /**
     * @var array
     */
    public $childs = array();


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
     * contains further localized field definitions if there are more than one localized fields in on class
     * @var array
     */
    protected $referencedFields = array();

    /**
     * @var array
     */
    private $fieldDefinitionsCache;

    /** @var  array */
    public $allowedGroupIds;


    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function getDataForEditmode($data, $object = null)
    {
        $fieldData = array();
        $metaData = array();

        if (!$data instanceof Object\Classificationstore) {
            return array();
        }

        $result = $this->doGetDataForEditMode($data, $object, $fieldData, $metaData, 1);

        // replace the real data with the data for the editmode
        foreach($result["data"] as $language => &$groups) {
            foreach ($groups as $groupId => &$keys) {
                foreach($keys as $keyId => &$keyValue) {
                    $keyConfig = Object\Classificationstore\DefinitionCache::get($keyId);
                    $fd = Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

                    $keyValue = $fd->getDataForEditmode($keyValue, $object);
                }
            }
        }

        $result["activeGroups"] = $data->getActiveGroups();

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
    private function doGetDataForEditMode($data, $object, &$fieldData, &$metaData, $level = 1) {
        $class = $object->getClass();
        $inheritanceAllowed = $class->getAllowInherit();
        $inherited = false;

        $items = $data->getItems();

        foreach ($items  as $groupId => $keys) {
            foreach ($keys as $keyId => $languages) {
                $keyConfig = Object\Classificationstore\DefinitionCache::get($keyId);
                $fd = Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);


                foreach ($languages as $language => $value) {
                    $fdata = $value;
                    if (!isset($fieldData[$language][$groupId][$keyId]) || $fd->isEmpty($fieldData[$language][$groupId][$keyId])) {
                        // never override existing data
                        $fieldData[$language][$groupId][$keyId] = $fdata;
                        if (!$fd->isEmpty($fdata)) {
                            $metaData[$language][$groupId][$keyId] = array("inherited" => $level > 1, "objectid" => $object->getId());
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
                    $validLanguages = array();
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
                                $metaData[$language][$groupId][$keyId] = array("inherited" => true, "objectid" => $parent->getId());
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

        $result = array(
            "data" => $fieldData,
            "metaData" => $metaData,
            "inherited" => $inherited
        );

        return $result;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param string $containerData
     * @param null|Model\Object\AbstractObject $object
     * @return string
     */
    public function  getDataFromEditmode($containerData, $object = null)
    {
        $classificationStore = $this->getDataFromObjectParam($object);

        if(!$classificationStore instanceof Object\Classificationstore) {
            $classificationStore = new Object\Classificationstore();
        }

        $data = $containerData["data"];
        $activeGroups = $containerData["activeGroups"];


        if (is_array($data)) {
            foreach ($data as $language => $fields) {
                foreach ($fields as $name => $fdata) {
                    $keyId = $fdata["keyId"];
                    $groupId = $fdata["groupId"];

                    $keyConfig = $this->getKeyConfiguration($keyId);

                    $dataDefinition = Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

                    $value = $fdata["value"];
                    $dataFromEditMode =  $dataDefinition->getDataFromEditmode($value);
                    $activeGroups[$groupId] = true;

                    $classificationStore->setLocalizedKeyValue($groupId, $keyId, $dataFromEditMode, $language);
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
     * @return \stdClass
     */
    public function getDataForGrid($data, $object = null) {
        return "not supported";
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param string $data
     * @return string
     */
    public function getVersionPreview($data)
    {
        // this is handled directly in the template
        // /pimcore/modules/admin/views/scripts/object/preview-version.php
        return "LOCALIZED FIELDS";
    }

    /**
     * @param Model\Object\AbstractObject $object
     * @return string
     */
    public function getForCsvExport($object)
    {
        return "not supported";
    }

    /**
     * @param string $importValue
     * @return null
     */
    public function getFromCsvImport($importValue)
    {
        return;
    }

    /**
     * @param $object
     * @return string
     */
    public function getDataForSearchIndex($object) {

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
                        $value = $fieldDefinition->getDataForResource($value, $object);
                        $dataString .= $value . " ";
                    }
                }
            }
        }


        return $dataString;
    }

    /**
     * @param Model\Object\AbstractObject $object
     * @return mixed
     */
    public function getForWebserviceExport($object)
    {
        throw new Exception("not supported");
    }

    /**
     * @param mixed $value
     * @param null $object
     * @param null $idMapper
     * @return mixed|null|Object\Localizedfield
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $idMapper = null)
    {
        throw new Exception("not supported");
    }


    /**
     * @return array
     */
    public function getChilds()
    {
        return $this->childs;
    }

    /**
     * @param array $childs
     * @return void
     */
    public function setChilds($childs)
    {
        $this->childs = $childs;
        $this->fieldDefinitionsCache = null;
        return $this;
    }

    /**
     * @return boolean
     */
    public function hasChilds()
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
    public function addReferencedField($field) {
        $this->referencedFields[] = $field;
    }

    /**
     * @param mixed $data
     * @param array $blockedKeys
     * @return void
     */
    public function setValues($data = array(), $blockedKeys = array())
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
    public function save($object, $params = array())
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
    public function load($object, $params = array())
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
    public function classSaved($class)
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
    public function preGetData($object, $params = array())
    {
        if(!$object instanceof Object\Concrete) {
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
    public function checkValidity($data, $omitMandatoryCheck = false){
        $groups = $data->getItems();
//        $conf = \Pimcore\Config::getSystemConfig();
//        if($conf->general->validLanguages) {
//            $languages = explode(",",$conf->general->validLanguages);
//        }

        if(!$omitMandatoryCheck){
            foreach ($groups as $groupId => $group) {
                foreach ($group as $keyId => $keyData)
                    foreach ($keyData as $language => $value) {
                        $keyConfig = $this->getKeyConfiguration($keyId);
                        $fieldDefinition = Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
                        $fieldDefinition->checkValidity($value);
                    }
            }
        }
    }


    /** See parent class.
     * @param mixed $data
     * @param null $object
     * @return array|null
     */
    public function getDiffDataForEditmode($data, $object = null)
    {
        throw new Exception("not supported");
    }

    /** See parent class.
     * @param $data
     * @param null $object
     * @return null|Pimcore_Date
     */

    public function getDiffDataFromEditmode($data, $object = null)
    {
        throw new Exception("not supported");
    }

    /** True if change is allowed in edit mode.
     * @return bool
     */
    public function isDiffChangeAllowed() {
        return false;
    }

    /**
     * @return array
     */
    public function __sleep() {
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
     * @param $object \Object_Abstract
     * @param array $activeGroups
     * @return array
     */
    public function recursiveGetActiveGroupsIds($object, $activeGroups = array()) {

        $getter = "get" . ucfirst($this->getName());
        /** @var  $classificationStore Classificationstore */
        $classificationStore = $object->$getter();
        $activeGroupIds = $classificationStore->getActiveGroups();

        foreach ($activeGroupIds as $groupId => $enabled) {
            if ($enabled) {
                $activeGroups[$groupId] = $enabled;
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

    public function enrichLayoutDefinition($object) {
        $this->activeGroupDefinitions = array();
        $activeGroupIds = $this->recursiveGetActiveGroupsIds($object);

        if (!$activeGroupIds) {
            return;
        }

        $filteredGroupIds = array();

        foreach ($activeGroupIds as $groupId => $enabled) {
            if ($enabled) {
                $filteredGroupIds[] = $groupId;
            }
        }


        $condition = "ID in (" . implode(',', $filteredGroupIds) . ")";
        $groupList = new Object\Classificationstore\GroupConfig\Listing();
        $groupList->setCondition($condition);
        $groupList->setOrderKey(array("sorter", "id"));
        $groupList->setOrder(array("ASC", "ASC"));
        $groupList = $groupList->load();

        /** @var  $group Object\Classificationstore\GroupConfig */
        foreach ($groupList as $group) {
            $keyList = array();

            $relation = new Object\Classificationstore\KeyGroupRelation\Listing();
            $relation->setCondition("groupId = " . $relation->quote($group->getId()));
            $relation->setOrderKey(array("sorter", "id"));
            $relation->setOrder(array("ASC", "ASC"));
            $relation = $relation->load();
            foreach ($relation as $key) {
                $definition = \Pimcore\Model\Object\Classificationstore\Service::getFieldDefinitionFromKeyConfig($key);

                if (method_exists( $definition, "__wakeup")) {
                    $definition->__wakeup();
                }

                $keyList[] = array(
                    "name" => $key->getName(),
                    "id" => $key->getKeyId(),
                    "description" => $key->getDescription(),
                    "definition" => $definition
                );
            }

            $this->activeGroupDefinitions[$group->getId()] = array(
                "name" => $group->getName(),
                "id" => $group->getId(),
                "description" => $group->getDescription(),
                "keys" => $keyList
            );

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
        if (is_string($allowedGroupIds) && !empty($allowedGroupIds)) {
            $allowedGroupIds = str_replace(array(" ", "\n") , "", $allowedGroupIds);
            $parts = explode(",", $allowedGroupIds);
        }

        $this->allowedGroupIds = $parts;

    }
}
