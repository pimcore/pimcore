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
 * @package    Document
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
    public function __construct($user) {
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
        $new->setPermissions($source->getPermissions());
        $new->setProperties($source->getProperties());

        $new->save();

        $target = Object_Abstract::getById($new->getId());
        return $target;
    }


    /**
     * @param  Object_Abstract $object
     * @return array
     */
    public static function gridObjectData($object) {

        $data = Element_Service::gridElementData($object);

        if ($object instanceof Object_Concrete) {
            $data["classname"] = $object->geto_ClassName();
            $data['inheritedFields'] = array();
            foreach ($object->getclass()->getFieldDefinitions() as $key => $def) {
                // some of the not editable field require a special response

                $getter = "get".ucfirst($key);

                //relation type fields with remote owner do not have a getter
                if(method_exists($object,$getter)) {
                    $valueObject = self::getValueForObject($object, $getter);
                    $data['inheritedFields'][$key] = array("inherited" => $valueObject->objectid != $object->getId(), "objectid" => $valueObject->objectid, "value" => $valueObject->value);
                    if ($def->getFieldType() == "href") {
                        if ($valueObject->value instanceof Element_Interface) {
                            $data[$key] = $valueObject->value->getFullPath();
                        }
                        continue;
                    }
                    else if ($def->getFieldType() == "objects" || $def->getFieldType() == "multihref") {
                        if (is_array($valueObject->value)) {
                            $pathes = array();
                            foreach ($valueObject->value as $eo) {
                                if ($eo instanceof Element_Interface) {
                                    $pathes[] = $eo->getFullPath();
                                }
                            }
                            $data[$key] = $pathes;
                        }
                        continue;
                    }
                    else if ($def->getFieldType() == "date" || $def->getFieldType() == "datetime") {
                        if ($valueObject->value instanceof Zend_Date) {
                            $data[$key] = $valueObject->value->getTimestamp();
                        }
                        else {
                            $data[$key] = null;
                        }
                        continue;
                    } else if ($def->getFieldType() == "localizedfields") {
                        foreach ($def->getFieldDefinitions() as $fd) {
                            $data[$fd->getName()] = $object->{"get".ucfirst($fd->getName())}();
                        }
                       continue;
                    }
                    $data[$key] = $valueObject->value;


                }
            }
        }

        return $data;
    }

    /**
     * gets value for given object and getter, including inherited values
     *
     * @static
     * @return stdclass, value and objectid where the value comes from
     */
    private static function getValueForObject($object, $getter) {
        $value = $object->$getter();

        if(empty($value)) {
            $parent = self::hasInheritableParentObject($object);
            if(!empty($parent)) {
                return self::getValueForObject($parent, $getter);
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
                if ($object->getO_parent()->getO_type() == "object") {
                    if ($object->getO_parent()->getO_classId() == $object->getO_classId()) {
                        return $object->getO_parent();
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
                if(!$field) { // if the definition doesn't exist check for a localized field
                    $localized = $class->getFieldDefinition("localizedfields");
                    if($localized instanceof Object_Class_Data_Localizedfields) {
                        $field = $localized->getFieldDefinition($filter["field"]);
                    }
                }

                if($field instanceof Object_Class_Data) {
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
            $conditionFilters = " AND (" . implode("AND", $conditionPartsFilters) . ")";
        }

        Logger::log("ObjectController filter condition:" . $conditionFilters);
        return $conditionFilters;
    }
}
