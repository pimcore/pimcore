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
 * @package    Object
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Data_KeyValue extends Pimcore_Model_Abstract {


    /**
     * @var Object_Class
     */
    public $class;

    /**
     * @var int
     */
    public $objectId;

    /**
     * @var array
     */
    public $arr = array();

    /** Whether multivalent values are allowed.
     * @var
     */
    protected $multivalent;


    public function __construct() {
    }

    /**
     * @param Object_Class $class
     * @return void
     */
    public function setClass(Object_Class $class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return Object_Class
     */
    public function getClass()
    {
        return $this->class;
    }

    public function __toString() {
        $str = "Object_Data_KeyValue oid=" . $this->objectId . "\n";
        $props = $this->getInternalProperties();

        if (is_array($props)) {
            foreach($props as $prop) {
                $str .= "    " . $prop["key"] . "=>" . $prop["value"] . "\n";
            }
        }
        return $str;
    }

    public function getObjectId() {
        return $this->objectId;
    }

    public function setObjectId($objectId) {
        $this->objectId = $objectId;
        return $this;
    }

    public function setProperties($arr) {
        $newProperties = array();
        foreach ($arr as $key => $pair) {

            if (!$pair["inherited"]) {
                $newProperties[] = $pair;
            }
        }

        $this->arr = $newProperties;
        return $this;
    }

    public function getInternalProperties() {
        return $this->arr;
    }

    public function getKeyvaluepairsByGroup($groupName){
        $data = array();
        $group = Object_KeyValue_GroupConfig::getByName($groupName);
        if (!empty($group)) {
            $properties = $this->getProperties();
            foreach ((array)$properties as $property) {
                if ($property['groupId'] == $group->getId()) {
                    $data[] = $property;
                }
            }
        }
        return $data;
    }

    public function getProperties($forEditMode = false) {
        $result = array();
        $object = Object_Abstract::getById($this->objectId);
        $objectName = $object->getKey();

        $internalKeys = array();
        foreach ($this->arr as $pair) {
            $pair["inherited"] = false;
            $pair["source"] = $object->getId();
            $pair["groupId"] = Object_KeyValue_KeyConfig::getById($pair['key'])->getGroup();
            $result[] = $pair;
            $internalKeys[] = $pair["key"];
        }

        $blacklist = array();

        $parent = Object_Service::hasInheritableParentObject($object);
        while ($parent) {
            $kv = $parent->getKeyvaluepairs();
            $parentProperties = $kv->getInternalProperties();

            $addedKeys = array();

            foreach ($parentProperties as $parentPair) {
                $parentKeyId = $parentPair["key"];
                $parentValue = $parentPair["value"];

                if (in_array($parentKeyId, $blacklist)) {
                    continue;
                }

                if ($this->multivalent && !$forEditMode && in_array($parentKeyId, $internalKeys)) {
                    continue;
                }

                $add = true;

                for ($i = 0; $i < count($result); ++$i) {
                    $resultPair = $result[$i];

                    $resultKey = $resultPair["key"];

                    $existingPair = null;
                    if ($resultKey == $parentKeyId) {
                        if ($this->multivalent && !in_array($resultKey, $blacklist)) {

                        } else {
                        $add = false;

                        }
                        // if the parent's key is already in the (internal) result list then
                        // we don't add it => not inherited.
                        if (!$this->multivalent) {
                            $add = false;
                        if (empty($resultPair["altSource"])) {
                            $resultPair["altSource"] = $parent->getId();
                            $resultPair["altValue"] = $parentPair["value"];
                        }
                        }

                        $result[$i] = $resultPair;
                    }

                    if (!$this->multivalent) {
                        break;
                    }

                }

                $addedKeys[] = $parentPair["key"];
                if ($add) {
                    $parentPair["inherited"] = true;
                    $parentPair["source"] = $parent->getId();
                    $parentPair["altSource"] = $parent->getId();
                    $parentPair["altValue"] = $parentPair["value"];
                    $result[] = $parentPair;
                }
            }

            foreach ($parentProperties as $parentPair) {
                $parentKeyId = $parentPair["key"];
                $blacklist[] = $parentKeyId;
            }

            $parent = Object_Service::hasInheritableParentObject($parent);
        }

        return $result;
    }


    function getKeyId($propName, $groupId = null) {
        $keyConfig = Object_KeyValue_KeyConfig::getByName($propName, $groupId);

        if (!$keyConfig) {
            throw new Exception("key does not exist");
        }
        $keyId =  $keyConfig->getId();
        return $keyId;
    }


    /** Throws an exception if property is not globally defined. Null, if it is not set
     * for this object.
     * @param $propName
     */
    public function getProperty($propName, $groupId = null) {
        $keyId =  $this->getKeyId($propName, $groupId);

        $result = array();
        // the key name is valid, now iterate over the object's pairs
        $propsWithInheritance = $this->getProperties();
        foreach ($propsWithInheritance as $pair) {
            if ($pair["key"] == $keyId) {
                $result[] = new Object_Data_KeyValue_Entry($pair["value"], $pair["translated"], $pair["metadata"]);
            }
        }
        $count = count($result);
        if ($count == 0) {
            return null;
        } else if ($count == 1) {
            return $result[0];
        } else {
            return $result;
        }
    }




    /** Sets the value of the property with the given id
     * @param $keyId the id of the key
     * @param $value the value
     * @param bool $fromGrid if true then the data is coming from the grid, we have to check if the value needs
     *                  to be translated
     * @return Object_Data_KeyValue the resulting object
     */
    public function setPropertyWithId($keyId, $value, $fromGrid = false) {
        // the key name is valid, now iterate over the object's pairs
        for ($i = 0; $i < count($this->arr); $i++) {
            $pair = $this->arr[$i];
            if ($pair["key"] == $keyId) {

                if ($fromGrid) {
                    $translatedValue = $this->getTranslatedValue($keyId, $value);
                }

                $pair["value"] = $value;
                $pair["translated"] = $translatedValue;
                $this->arr[$i] = $pair;
                return;
            }
        }

        $pair = array();
        $pair["key"] = $keyId;
        $pair["value"] = $value;
        $this->arr[] = $pair;
        return $this;
    }

    private function getTranslatedValue($keyId, $value) {
        $translatedValue = "";
        $keyConfig = Object_KeyValue_KeyConfig::getById($keyId);
        $translatorID = $keyConfig->getTranslator();
        $translatorConfig = Object_KeyValue_TranslatorConfig::getById($translatorID);
        $className = $translatorConfig->getTranslator();
        if (Pimcore_Tool::classExists($className)) {
            $translator = new $className();
            $translatedValue = $translator->translate($value);
            if (!$translatedValue) {
                $translatedValue = $value;
            }
        }
        return $translatedValue;
    }


    public function setProperty($propName, $value) {
        $keyId =  $this->getKeyId($propName);
        $this->setPropertyWithId($keyId, $value);
    }

    public function __call($name, $arguments) {
        $sub = substr($name, 0, 14);
        if(substr($name, 0, 16) == "getWithGroupName") {
            $key = substr($name, 16, strlen($name)-16);
            $groupConfig = Object_KeyValue_GroupConfig::getByName($arguments[0]);
            return $this->getProperty($key, $groupConfig->getId());
        } else if(substr($name, 0, 14) == "getWithGroupId") {
            $key = substr($name, 14, strlen($name)-14);
            $groupConfig = Object_KeyValue_GroupConfig::getById($arguments[0]);
            return $this->getProperty($key, $groupConfig->getId());
        } else  if(substr($name, 0, 3) == "get") {
            $key = substr($name, 3, strlen($name)-3);
            return $this->getProperty($key);
        } else if(substr($name, 0, 3) == "set") {
            $key = substr($name, 3, strlen($name)-3);
            return $this->setProperty($key, $arguments[0]);
        }
        return parent::__call($name, $arguments);
    }


    public function getEntryByKeyId($keyId) {
        $result = array();
        foreach($this->getProperties() as $property) {
            if($property['key'] == $keyId) {
                $result[] = new Object_Data_KeyValue_Entry($property["value"], $property["translated"],$property["metadata"]);
            }
        }

        $count = count($result);
        if ($count == 0) {
            return null;
        } else if ($count == 1) {
            return $result[0];
        } else {
            return $result;
        }

    }

    public function setValueWithKeyId($keyId, $value) {
        $cleanedUpValues = array();
        foreach($this->arr as $entry) {
            if($entry['key'] != $keyId) {
                $cleanedUpValues[] = $entry;
            }
        }
        $this->arr = $cleanedUpValues;

        if(!is_array($value)) {
            $value = array($value);
        }

        foreach($value as $v) {
            $pair = array();
            $pair["key"] = $keyId;
            $pair["value"] = $v;
            $pair["translated"] = $this->getTranslatedValue($keyId, $v);
            $this->arr[] = $pair;
        }

    }


    /**
     * @param  $multivalent
     */
    public function setMultivalent($multivalent)
    {
        $this->multivalent = $multivalent;
    }

    /**
     * @return
     */
    public function getMultivalent()
    {
        return $this->multivalent;
    }


}
