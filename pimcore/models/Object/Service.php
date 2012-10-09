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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Service extends Element_Service {

    /**
     * @var array
     */
    protected $_copyRecursiveIds;

    /**
     * @var User
     */
    protected $_user;

    /**
     * @param  User $user
     * @return void
     */
    public function __construct($user = null) {
        $this->_user = $user;
    }


    /**
     * finds all objects which hold a reference to a specific user
     *
     * @static
     * @param  integer $userId
     * @return Object_Concrete[]
     */
    public static function getObjectsReferencingUser($userId) {
        $userObjects = array();
        $classesList = new Object_Class_List();
        $classesList->setOrderKey("name");
        $classesList->setOrder("asc");
        $classes = $classesList->load();

        $classesToCheck = array();
        if (is_array($classes)) {
            foreach ($classes as $class) {
                $fieldDefinitions = $class->getFieldDefinitions();
                $dataKeys = array();
                if (is_array($fieldDefinitions)) {
                    foreach ($fieldDefinitions as $tag) {
                        if ($tag instanceof Object_Class_Data_User) {
                            $dataKeys[] = $tag->getName();
                        }
                    }
                }
                if (is_array($dataKeys) and count($dataKeys) > 0) {
                    $classesToCheck[$class->getName()] = $dataKeys;
                }
            }
        }

        foreach ($classesToCheck as $classname => $fields) {
            $listName = "Object_" . ucfirst($classname) . "_List";
            $list = new $listName();
            $conditionParts = array();
            foreach ($fields as $field) {
                $conditionParts[] = $field . "='" . $userId . "'";
            }
            $list->setCondition(implode(" AND ", $conditionParts));
            $objects = $list->load();
            $userObjects = array_merge($userObjects, $objects);
        }
        return $userObjects;
    }

    /**
     * @param  Object_Abstract $target
     * @param  Object_Abstract $source
     * @return
     */
    public function copyRecursive($target, $source) {

        // avoid recursion
        if (!$this->_copyRecursiveIds) {
            $this->_copyRecursiveIds = array();
        }
        if (in_array($source->getId(), $this->_copyRecursiveIds)) {
            return;
        }

        $source->getProperties();
        //load all in case of lazy loading fields
        self::loadAllObjectFields($source);

        $new = clone $source;
        $new->o_id = null;
        $new->setChilds(null);
        $new->setKey(Element_Service::getSaveCopyName("object", $new->getKey(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user->getId());
        $new->setUserModification($this->_user->getId());
        $new->setResource(null);
        $new->setLocked(false);
        $new->setCreationDate(time());
        $new->save();

        // add to store
        $this->_copyRecursiveIds[] = $new->getId();

        foreach ($source->getChilds() as $child) {
            $this->copyRecursive($new, $child);
        }

        $this->updateChilds($target, $new);

        return $new;
    }


    /**
     * @param  Object_Abstract $target
     * @param  Object_Abstract $source
     * @return Object_Abstract copied object
     */
    public function copyAsChild($target, $source) {

        //load properties
        $source->getProperties();

        //load all in case of lazy loading fields
        self::loadAllObjectFields($source);

        $new = clone $source;
        $new->o_id = null;

        $new->setChilds(null);
        $new->setKey(Element_Service::getSaveCopyName("object", $new->getKey(), $target));
        $new->setParentId($target->getId());
        $new->setUserOwner($this->_user->getId());
        $new->setUserModification($this->_user->getId());
        $new->setResource(null);
        $new->setLocked(false);
        $new->setCreationDate(time());
        $new->save();

        $this->updateChilds($target, $new);

        return $new;
    }

    /**
     * @param  Object_Abstract $target
     * @param  Object_Abstract $source
     * @return return $target
     */
    public function copyContents($target, $source) {

        // check if the type is the same
        if (get_class($source) != get_class($target)) {
            throw new Exception("Source and target have to be the same type");
        }

        //load all in case of lazy loading fields
        self::loadAllObjectFields($source);

        $new = clone $source;
        $new->setChilds($target->getChilds());
        $new->setId($target->getId());
        $new->setPath($target->getO_path());
        $new->setKey($target->getKey());
        $new->setParentId($target->getParentId());
        $new->setScheduledTasks($source->getScheduledTasks());
        $new->setProperties($source->getProperties());
        $new->setUserModification($this->_user->getId());

        $new->save();

        $target = Object_Abstract::getById($new->getId());
        return $target;
    }


    /**
     * @param  Object_Abstract $object
     * @return array
     */
    public static function gridObjectData($object, $fields = null) {

        $data = Element_Service::gridElementData($object);

        if ($object instanceof Object_Concrete) {
            $data["classname"] = $object->geto_ClassName();
            $data["idPath"] = Pimcore_Tool::getIdPathForElement($object);
            $data['inheritedFields'] = array();

            if(empty($fields)) {
                $fields = array_keys($object->getclass()->getFieldDefinitions());
            }
            foreach($fields as $key) {
                $brickType = null;
                $brickGetter = null;
                $dataKey = $key; 
                $keyParts = explode("~", $key);

                $def = $object->getClass()->getFieldDefinition($key);

                if(count($keyParts) > 1) {
                    $brickType = $keyParts[0];
                    $brickKey = $keyParts[1];
                    $key = self::getFieldForBrickType($object->getclass(), $brickType);

                    $brickClass = Object_Objectbrick_Definition::getByKey($brickType);
                    $def = $brickClass->getFieldDefinition($brickKey);
                }

                if(!empty($key)) {

                    // some of the not editable field require a special response

                    $getter = "get".ucfirst($key);
                    $brickGetter = null;
                    if(!empty($brickKey)) {
                        $brickGetter = "get".ucfirst($brickKey);
                    }

                    // if the definition is not set try to get the definition from localized fields
                    if(!$def) {
                        if($locFields = $object->getClass()->getFieldDefinition("localizedfields")) {
                            $def = $locFields->getFieldDefinition($key);
                        }
                    }

                    //relation type fields with remote owner do not have a getter
                    if(method_exists($object,$getter)) {

                        //system columns must not be inherited
                        if(in_array($key, Object_Concrete::$systemColumnNames)) {
                            $data[$dataKey] = $object->$getter();
                        } else {

                            $valueObject = self::getValueForObject($object, $getter, $brickType, $brickGetter);
                            $data['inheritedFields'][$dataKey] = array("inherited" => $valueObject->objectid != $object->getId(), "objectid" => $valueObject->objectid);

                            if(method_exists($def, "getDataForGrid")) {
                                $tempData = $def->getDataForGrid($valueObject->value, $object);
                                if($def instanceof Object_Class_Data_Localizedfields) {
                                    foreach($tempData as $tempKey => $tempValue) {
                                        $data[$tempKey] = $tempValue;
                                    }
                                } else {
                                    $data[$dataKey] = $tempData;
                                }
                            } else {
                                $data[$dataKey] = $valueObject->value;
                            }

                        }


                    }
                }

            }
        }
        return $data;
    }

    public static function getFieldForBrickType(Object_Class $class, $bricktype) {
        $fieldDefinitions = $class->getFieldDefinitions();
        foreach($fieldDefinitions as $key => $fd) {
            if($fd instanceof Object_Class_Data_Objectbricks) {
                if(in_array($bricktype, $fd->getAllowedTypes())) {
                    return $key;
                }
            }
        }
       return null;
    }

    /**
     * gets value for given object and getter, including inherited values
     *
     * @static
     * @return stdclass, value and objectid where the value comes from
     */
    private static function getValueForObject($object, $getter, $brickType = null, $brickGetter = null) {
        $value = $object->$getter();
        if(!empty($value) && !empty($brickType)) {
            $getBrickType = "get" . ucfirst($brickType);
            $value = $value->$getBrickType();
            if(!empty($value) && !empty($brickGetter)) {
                $value = $value->$brickGetter();
            }
        }


        if(empty($value) || (is_object($value) && method_exists($value, "isEmpty") && $value->isEmpty())) {
            $parent = self::hasInheritableParentObject($object);
            if(!empty($parent)) {
                return self::getValueForObject($parent, $getter, $brickType, $brickGetter);
            }
        }

        $result = new stdClass();
        $result->value = $value;
        $result->objectid = $object->getId();
        return $result;
    }

    public static function hasInheritableParentObject(Object_Concrete $object) {
        if($object->getO_class()->getAllowInherit()) {
            if ($object->getO_parent() instanceof Object_Abstract) {
                $parent = $object->getO_parent();
                while($parent && $parent->getO_type() == "folder") {
                    $parent = $parent->getO_parent();
                }

                if ($parent && ($parent->getO_type() == "object" || $parent->getO_type() == "variant")) {
                    if ($parent->getO_classId() == $object->getO_classId()) {
                        return $parent;
                    }
                }
            }
        }
    }

    /**
     * call the getters of each object field, in case some of the are lazy loading and we need the data to be loaded
     *
     * @static
     * @param  Object_Concrete $object
     * @return void
     */
    public static function loadAllObjectFields($object) {
        if ($object instanceof Object_Concrete) {
            //load all in case of lazy loading fields
            $fd = $object->getO_class()->getFieldDefinitions();
            foreach ($fd as $def) {
                $getter = "get" . ucfirst($def->getName());
                if (method_exists($object, $getter)) {
                    $object->$getter();
                }
            }
        }
    }
    
    /**
     *
     * @param string $filterJson
     * @param Object_Class $class
     * @return string
     */
    public static function getFilterCondition($filterJson, $class) {

        $systemFields = array("o_path", "o_key", "o_id", "o_published","o_creationDate","o_modificationDate");
        
        // create filter condition
        $conditionPartsFilters = array();
        
        if ($filterJson) {
            $filters = Zend_Json::decode($filterJson);
            foreach ($filters as $filter) {

                $operator = "=";
                
                if($filter["type"] == "string") {
                    $operator = "LIKE";
                } else if ($filter["type"] == "numeric") {
                    if($filter["comparison"] == "lt") {
                        $operator = "<";
                    } else if($filter["comparison"] == "gt") {
                        $operator = ">";
                    } else if($filter["comparison"] == "eq") {
                        $operator = "=";
                    }
                } else if ($filter["type"] == "date") {
                    if($filter["comparison"] == "lt") {
                        $operator = "<";
                    } else if($filter["comparison"] == "gt") {
                        $operator = ">";
                    } else if($filter["comparison"] == "eq") {
                        $operator = "=";
                    }
                    $filter["value"] = strtotime($filter["value"]);
                } else if ($filter["type"] == "list") {
                    $operator = "=";
                } else if ($filter["type"] == "boolean") {
                    $operator = "=";
                    $filter["value"] = (int) $filter["value"];
                }
                
                $field = $class->getFieldDefinition($filter["field"]);
                $brickField = null;
                $brickType = null;
                if(!$field) {

                    // if the definition doesn't exist check for a localized field
                    $localized = $class->getFieldDefinition("localizedfields");
                    if($localized instanceof Object_Class_Data_Localizedfields) {
                        $field = $localized->getFieldDefinition($filter["field"]);
                    }


                    //if the definition doesn't exist check for object brick
                    $keyParts = explode("~", $filter["field"]);
                    if(count($keyParts) > 1) {
                        $brickType = $keyParts[0];
                        $brickKey = $keyParts[1];

                        $key = self::getFieldForBrickType($class, $brickType);
                        $field = $class->getFieldDefinition($key);

                        $brickClass = Object_Objectbrick_Definition::getByKey($brickType);
                        $brickField = $brickClass->getFieldDefinition($brickKey);

                    }
                }
                if($field instanceof Object_Class_Data_Objectbricks) {
                    // custom field
                    $db = Pimcore_Resource::get();
                    if(is_array($filter["value"])) {
                        $fieldConditions = array();
                        foreach ($filter["value"] as $filterValue) {
                            $fieldConditions[] = $db->getQuoteIdentifierSymbol() . $brickType . $db->getQuoteIdentifierSymbol() . "." . $brickField->getFilterCondition($filterValue, $operator);
                        }
                        $conditionPartsFilters[] = "(" . implode(" OR ", $fieldConditions) . ")";
                    } else {
                        $conditionPartsFilters[] = $db->getQuoteIdentifierSymbol() . $brickType . $db->getQuoteIdentifierSymbol() . "." . $brickField->getFilterCondition($filter["value"], $operator);
                    }
                } else if($field instanceof Object_Class_Data) {
                    // custom field
                    if(is_array($filter["value"])) {
                        $fieldConditions = array();
                        foreach ($filter["value"] as $filterValue) {
                            $fieldConditions[] = $field->getFilterCondition($filterValue, $operator);
                        }
                        $conditionPartsFilters[] = "(" . implode(" OR ", $fieldConditions) . ")";
                    } else {
                        $conditionPartsFilters[] = $field->getFilterCondition($filter["value"], $operator);
                    }
                    
                } else if (in_array("o_".$filter["field"], $systemFields)) {
                    // system field
                    $conditionPartsFilters[] = "`o_" . $filter["field"] . "` " . $operator . " '" . $filter["value"] . "' ";
                }                
            }
        }

        $conditionFilters = "";
        if (count($conditionPartsFilters) > 0) {
            $conditionFilters = "(" . implode(" AND ", $conditionPartsFilters) . ")";
        }
        Logger::log("ObjectController filter condition:" . $conditionFilters);
        return $conditionFilters;
    }

    /**
     * @static
     * @param $object
     * @param $fieldname
     * @return array
     */
    public static function getOptionsForSelectField($object, $fieldname) {
        $class = null;
        $options = array();

        if(is_object($object) && method_exists($object, "getClass")) {
            $class = $object->getClass();
        } else if(is_string($object)) {
            $object = new $object();
            $class = $object->getClass();
        }

        if($class) {
            /**
             * @var Object_Class_Data_Select $definition
             */
            $definition = $class->getFielddefinition($fieldname);
            if($definition instanceof Object_Class_Data_Select) {
                $_options = $definition->getOptions();

                foreach($_options as $option) {
                    $options[$option["value"]] = $option["key"];
                }
            }
        }

        return $options;
    }

    /**
     * @static
     * @param $path
     * @return bool
     */
    public static function pathExists ($path, $type = null) {

        $path = Element_Service::correctPath($path);

        try {
            $object = new Object_Abstract();

            if (Pimcore_Tool::isValidPath($path)) {
                $object->getResource()->getByPath($path);
                return true;
            }
        }
        catch (Exception $e) {

        }

        return false;
    }
}
