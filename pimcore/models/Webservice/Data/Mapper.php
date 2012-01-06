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
 * @package    Webservice
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

abstract class Webservice_Data_Mapper {

    /**
     * @static
     * @param  $object
     * @param  string $type "in" or "out"
     * @return string
     */
    public static function findWebserviceClass($object, $type) {

        $mappingClasses = array(
            "Asset_File",
            "Asset_Folder",
            "Document_Folder",
            "Document_Link",
            "Document_Page",
            "Document_Snippet",
            "Object_Folder",
            "Object_Concrete",
        );

        $retVal = null;
        if($object instanceof Property){
            $retVal = "Webservice_Data_Property";
        } else if ($object instanceof Document_Tag) {
            $retVal = "Webservice_Data_Document_Element";
        } else if ($object instanceof Object_Class_Data) {
            $retVal = "Webservice_Data_Object_Element";
        }  else if (is_object($object)) {
            $orgclass = get_class($object);

            if (in_array($orgclass,$mappingClasses)) {
                $apiclass = "Webservice_Data_" . $orgclass . "_" . ucfirst($type);
                if (!Pimcore_Tool::classExists($apiclass)) {
                    $apiclass = "Webservice_Data_" . $orgclass;
                    if (!Pimcore_Tool::classExists($apiclass)) {
                        throw new Exception("Webservice_Data_Mapper: no SOAP API class found for [ " . $orgclass . " ]");
                    }
                }
            } else {
                $apiclass = $orgclass;
            }
            $retVal = $apiclass;
        } else $retVal = "Array";
        return $retVal;


    }

    /**
     * @static
     * @param Element_Interface $object
     * @param string $type  "in" or "out"
     * @param  string $class
     * @return array
     */
    public static function map($object, $apiclass, $type) {
        if($object instanceof Zend_Date){
            $object=$object->toString();
        } else if (is_object($object)) {
            if (Pimcore_Tool::classExists($apiclass)) {
                $new = new $apiclass();
                if (method_exists($new, "map")) {
                    $new->map($object);
                    $object = $new;
                }
            } else {
                throw new Exception("Webservice_Data_Mapper: Cannot map [ $apiclass ] - class does not exist");
            }
        }
        else if (is_array($object)) {
            $tmpArray = array();
            foreach ($object as $v) {
                $className = self::findWebserviceClass($v, $type);
                $tmpArray[] = self::map($v, $className, $type);
            }
            $object = $tmpArray;
        }

        return $object;
    }

    public static function toObject($el) {
        if (is_object($el)) {
            $el = object2array($el);
        }

        $obj = new stdClass();
        foreach ($el as $key => $value) {
            $obj->$key = $value;
        }

        return $obj;
    }

}
