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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Object_Class_Service  {




    /**
     * @static
     * @param  Object_Class $class
     * @return string
     */
    public static function generateClassDefinitionXml($class){

        $data = object2array($class);

        unset($data["id"]);
        unset($data["name"]);
        unset($data["creationDate"]);
        unset($data["modificationDate"]);
        unset($data["userOwner"]);
        unset($data["userModification"]);
        unset($data["fieldDefinitions"]);

        $referenceFunction =  function(&$value,$key){
            $value = htmlspecialchars($value);
        };
        array_walk_recursive($data,$referenceFunction);


        $config = new Zend_Config($data, true);
        $writer = new Zend_Config_Writer_Xml(array(
            "config" => $config
        ));
        return $writer->render();
    }

    /**
     * @static
     * @param  Object_Class $class
     * @return string
     */
    public static function generateFieldCollectionXml($fieldCollection){
        $FieldCollectionJson = Zend_Json::encode($fieldCollection);
        $data = Zend_Json::decode($FieldCollectionJson);
        unset($data["key"]);

        $referenceFunction =  function(&$value,$key){
            $value = htmlspecialchars($value);
        };
        array_walk_recursive($data,$referenceFunction);

        $config = new Zend_Config($data, true);
        $writer = new Zend_Config_Writer_Xml(array(
            "config" => $config
        ));
        return $writer->render();
    }

    public static function generateLayoutTreeFromArray($array) {

        if (is_array($array) && count($array) > 0) {

            $class = "Object_Class_".ucfirst($array["datatype"])."_" . ucfirst($array["fieldtype"]);
            if (Pimcore_Tool::classExists($class)) {
                $item = new $class();

                if(method_exists($item,"addChild")) { // allows childs

                    $item->setValues($array, array("childs"));

                    if($array["childs"]["datatype"]){
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