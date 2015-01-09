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

namespace Pimcore\Model\Object\ClassDefinition;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Model\Webservice;

class Service  {

    /**
     * @static
     * @param  Object\ClassDefinition $class
     * @return string
     */
    public static function generateClassDefinitionJson($class){

        $data = Webservice\Data\Mapper::map($class, "\\Pimcore\\Model\\Webservice\\Data\\ClassDefinition\\Out", "out");
        unset($data->id);
        unset($data->name);
        unset($data->creationDate);
        unset($data->modificationDate);
        unset($data->userOwner);
        unset($data->userModification);
        unset($data->fieldDefinitions);
        
        //add propertyVisibility to export data
        $data->propertyVisibility = $class->propertyVisibility;

        $json = \Zend_Json::encode($data);
        $json = \Zend_Json::prettyPrint($json);
        return $json;
    }

    /**
     * @param $class
     * @param $json
     * @return bool
     */
    public static function importClassDefinitionFromJson($class, $json, $throwException = false) {

        $userId = 0;
        $user = \Pimcore\Tool\Admin::getCurrentUser();
        if($user) {
            $userId = $user->getId();
        }

        $importData = \Zend_Json::decode($json);

        // set layout-definition
        $layout = self::generateLayoutTreeFromArray($importData["layoutDefinitions"], $throwException);
        if ($layout === false) {
            return false;
        }
        $class->setLayoutDefinitions($layout);

        // set properties of class
        $class->setModificationDate(time());
        $class->setUserModification($userId);
        $class->setIcon($importData["icon"]);
        $class->setAllowInherit($importData["allowInherit"]);
        $class->setAllowVariants($importData["allowVariants"]);
        $class->setShowVariants($importData["showVariants"]);
        $class->setParentClass($importData["parentClass"]);
        $class->setPreviewUrl($importData["previewUrl"]);
        $class->setPropertyVisibility($importData["propertyVisibility"]);

        $class->save();

        return true;
    }

    /**
     * @param $fieldCollection
     * @return string
     */
    public static function generateFieldCollectionJson($fieldCollection){

        unset($fieldCollection->key);
        unset($fieldCollection->fieldDefinitions);

        $json = \Zend_Json::encode($fieldCollection);
        $json = \Zend_Json::prettyPrint($json);
        return $json;
    }

    /**
     * @param $fieldCollection
     * @param $json
     * @return bool
     */
    public static function importFieldCollectionFromJson($fieldCollection, $json, $throwException = false) {

        $importData = \Zend_Json::decode($json);

        $layout = self::generateLayoutTreeFromArray($importData["layoutDefinitions"], $throwException);
        $fieldCollection->setLayoutDefinitions($layout);
        $fieldCollection->setParentClass($importData["parentClass"]);
        $fieldCollection->save();

        return true;
    }

    /**
     * @param $objectBrick
     * @return string
     */
    public static function generateObjectBrickJson($objectBrick){

        unset($objectBrick->key);
        unset($objectBrick->fieldDefinitions);

        // set classname attribute to the real class name not to the class ID
        // this will allow to import the brick on a different instance with identical class names but different class IDs
        if(is_array($objectBrick->classDefinitions)) {
            foreach($objectBrick->classDefinitions as &$cd) {
                $class = Object\ClassDefinition::getById($cd["classname"]);
                if($class) {
                    $cd["classname"] = $class->getName();
                }
            }
        }

        $json = \Zend_Json::encode($objectBrick);
        $json = \Zend_Json::prettyPrint($json);
        return $json;
    }

    /**
     * @param $objectBrick
     * @param $json
     * @return bool
     */
    public static function importObjectBrickFromJson($objectBrick, $json, $throwException = false) {

        $importData = \Zend_Json::decode($json);

        // reverse map the class name to the class ID, see: self::generateObjectBrickJson()
        if(is_array($importData["classDefinitions"])) {
            foreach($importData["classDefinitions"] as &$cd) {
                if(!is_numeric($cd["classname"])) {
                    $class = Object\ClassDefinition::getByName($cd["classname"]);
                    if($class) {
                        $cd["classname"] = $class->getId();
                    }
                }
            }
        }

        $layout = self::generateLayoutTreeFromArray($importData["layoutDefinitions"], $throwException);
        $objectBrick->setLayoutDefinitions($layout);
        $objectBrick->setClassDefinitions($importData["classDefinitions"]);
        $objectBrick->setParentClass($importData["parentClass"]);
        $objectBrick->save();

        return true;
    }

    /**
     * @param $array
     * @param bool $throwException
     * @return bool
     * @throws \Exception
     */
    public static function generateLayoutTreeFromArray($array, $throwException = false) {

        if (is_array($array) && count($array) > 0) {

            $class = "\\Pimcore\\Model\\Object\\ClassDefinition\\".ucfirst($array["datatype"])."\\" . ucfirst($array["fieldtype"]);
            if (!\Pimcore\Tool::classExists($class)) {
                $class = "\\Object_Class_" .ucfirst($array["datatype"])."_" . ucfirst($array["fieldtype"]);
                if (!\Pimcore\Tool::classExists($class)) {
                    $class = null;
                }
            }

            if ($class) {
                $item = new $class();

                if(method_exists($item,"addChild")) { // allows childs

                    $item->setValues($array, array("childs"));

                    if(is_array($array) && is_array($array["childs"]) && $array["childs"]["datatype"]){
                        $childO = self::generateLayoutTreeFromArray($array["childs"], $throwException);
                        $item->addChild($childO);
                    } else if (is_array($array["childs"]) && count($array["childs"]) > 0) {
                        foreach ($array["childs"] as $child) {
                            $childO = self::generateLayoutTreeFromArray($child, $throwException);
                            if ($childO !== false) {
                                $item->addChild($childO);
                            } else {
                                if ($throwException) {
                                    throw new \Exception("Could not add child " . var_export($child, true));
                                }
                                return false;
                            }
                        }
                    }
                } else {
                    $item->setValues($array);
                }

                return $item;
            }
        }
        if ($throwException) {
            throw new \Exception("Could not add child " . var_export($array, true));
        }
        return false;
    }

    /**
     * @param $tableDefinitions
     * @param $tableNames
     */
    public static function updateTableDefinitions(&$tableDefinitions, $tableNames) {
        if (!is_array($tableDefinitions)) {
            $tableDefinitions = array();
        }

        $db = \Pimcore\Resource::get();
        $tmp = array();
        foreach ($tableNames as $tableName) {
            $tmp[$tableName] = $db->fetchAll("show columns from " . $tableName);
        }

        foreach ($tmp as $tableName => $columns) {
            foreach($columns as $column) {
                $column["Type"] = strtolower($column["Type"]);
                if (strtolower($column["Null"]) == "yes") {
                    $column["Null"] = "null";
                }
//                $fieldName = strtolower($column["Field"]);
                $fieldName = $column["Field"];
                $tableDefinitions[$tableName][$fieldName] = $column;
            }
        }
    }

    /**
     * @param $tableDefinitions
     * @param $table
     * @param $colName
     * @param $type
     * @param $default
     * @param $null
     * @return bool
     */
    public static function skipColumn($tableDefinitions, $table, $colName, $type, $default, $null) {
        $tableDefinition = $tableDefinitions[$table];
        if ($tableDefinition) {
            $colDefinition = $tableDefinition[$colName];
            if ($colDefinition) {
                if (!strlen($default) && strtolower($null) === "null") {
                    $default = null;
                }

                if (  $colDefinition["Type"] == $type && strtolower($colDefinition["Null"]) == strtolower($null)
                    && $colDefinition["Default"] == $default) {
                    return true;
                }
            }
        }
        return false;
    }


}
