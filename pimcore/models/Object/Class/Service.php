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
 * @package    Object_Class
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_Service  {

    /**
     * @static
     * @param  Object_Class $class
     * @return string
     */
    public static function generateClassDefinitionJson($class){

        $data = Webservice_Data_Mapper::map($class, "Webservice_Data_Class_Out", "out");
        unset($data->id);
        unset($data->name);
        unset($data->creationDate);
        unset($data->modificationDate);
        unset($data->userOwner);
        unset($data->userModification);
        unset($data->fieldDefinitions);

        $json = Zend_Json::encode($data);
        $json = Zend_Json::prettyPrint($json);
        return $json;
    }

    /**
     * @param $class
     * @param $json
     * @return bool
     */
    public static function importClassDefinitionFromJson($class, $json) {

        $userId = 0;
        $user = Pimcore_Tool_Admin::getCurrentUser();
        if($user) {
            $userId = $user->getId();
        }

        $importData = Zend_Json::decode($json);

        // set layout-definition
        $layout = self::generateLayoutTreeFromArray($importData["layoutDefinitions"]);
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

        $json = Zend_Json::encode($fieldCollection);
        $json = Zend_Json::prettyPrint($json);
        return $json;
    }

    /**
     * @param $fieldCollection
     * @param $json
     * @return bool
     */
    public static function importFieldCollectionFromJson($fieldCollection, $json) {

        $importData = Zend_Json::decode($json);

        $layout = self::generateLayoutTreeFromArray($importData["layoutDefinitions"]);
        $fieldCollection->setLayoutDefinitions($layout);
        $fieldCollection->setParentClass($importData["parentClass"]);
        $fieldCollection->save();

        return true;
    }

    /**
     * @param $fieldCollection
     * @return string
     */
    public static function generateObjectBrickJson($objectBrick){

        unset($objectBrick->key);
        unset($objectBrick->fieldDefinitions);

        // set classname attribute to the real class name not to the class ID
        // this will allow to import the brick on a different instance with identical class names but different class IDs
        if(is_array($objectBrick->classDefinitions)) {
            foreach($objectBrick->classDefinitions as &$cd) {
                $class = Object_Class::getById($cd["classname"]);
                if($class) {
                    $cd["classname"] = $class->getName();
                }
            }
        }

        $json = Zend_Json::encode($objectBrick);
        $json = Zend_Json::prettyPrint($json);
        return $json;
    }

    /**
     * @param $objectBrick
     * @param $json
     * @return bool
     */
    public static function importObjectBrickFromJson($objectBrick, $json) {

        $importData = Zend_Json::decode($json);

        // reverse map the class name to the class ID, see: self::generateObjectBrickJson()
        if(is_array($importData["classDefinitions"])) {
            foreach($importData["classDefinitions"] as &$cd) {
                if(!is_numeric($cd["classname"])) {
                    $class = Object_Class::getByName($cd["classname"]);
                    if($class) {
                        $cd["classname"] = $class->getId();
                    }
                }
            }
        }

        $layout = self::generateLayoutTreeFromArray($importData["layoutDefinitions"]);
        $objectBrick->setLayoutDefinitions($layout);
        $objectBrick->setClassDefinitions($importData["classDefinitions"]);
        $objectBrick->setParentClass($importData["parentClass"]);
        $objectBrick->save();

        return true;
    }

    public static function generateLayoutTreeFromArray($array) {

        if (is_array($array) && count($array) > 0) {

            $class = "Object_Class_".ucfirst($array["datatype"])."_" . ucfirst($array["fieldtype"]);
            if (Pimcore_Tool::classExists($class)) {
                $item = new $class();

                if(method_exists($item,"addChild")) { // allows childs

                    $item->setValues($array, array("childs"));

                    if(is_array($array) && is_array($array["childs"]) && $array["childs"]["datatype"]){
                         $childO = self::generateLayoutTreeFromArray($array["childs"]);
                            $item->addChild($childO);
                    } else if (is_array($array["childs"]) && count($array["childs"]) > 0) {
                        foreach ($array["childs"] as $child) {
                            $childO = self::generateLayoutTreeFromArray($child);
                            $item->addChild($childO);
                        }
                    }
                } else {
                    $item->setValues($array);
                }

                return $item;
            }
        }
        return false;
    }

}