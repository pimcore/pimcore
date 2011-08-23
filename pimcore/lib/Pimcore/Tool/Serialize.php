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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_Tool_Serialize {


    /**
     * @static
     * @param mixed $data
     * @return string
     */
    public static function serialize ($data) {

        // load all data if it is an object, because of lazyloaded fields and _fulldump
        if($data instanceof Object_Concrete) {
            Object_Service::loadAllObjectFields($data);
        }

        $filteredData = self::mapElementReferences($data, true);
        $serializedData = serialize($filteredData);

        // now we have to remap the elements, because of pass by reference (because of combination of version/caching, ...)
        self::reverseMapElementReferences($filteredData);

        return $serializedData;
    }

    /**
     * @static
     * @param $data
     * @return mixed
     */
    public static function unserialize ($data) {

        // only strings are allowed ;-)
        if(!is_string($data)) {
            return $data;
        }

        $unserialized = unserialize($data);

        // put the origin object directly to the registry, this is because of cyclic references which should be resolved by self::reverseMapElementReferences()
        if($unserialized instanceof Element_Interface) {
            Zend_Registry::set(Element_Service::getType($unserialized) . "_" . $unserialized->getId(), $unserialized);
        }

        $remappedData = self::reverseMapElementReferences($unserialized);
        return $remappedData;
    }

    /**
     * @static
     * @param $data
     * @param bool $isOrigin
     * @return mixed
     */
    public static function mapElementReferences ($data, $isOrigin = true) {

        // possibility to ignore classes (recursion)
        $ignoreTypes = array();

        if(is_object($data)) {
            if($data instanceof Element_Interface && !$isOrigin) {
                return new Element_Reference_Placeholder($data->getId(), Element_Service::getType($data));
            } else if(!isset($data->__pimcore_tool_serialize_active)) {

                // recursion detection
                $data->__pimcore_tool_serialize_active = true;

                $vars = get_object_vars($data);

                // check for blocked vars by $data::__sleep();
                // same behavior like serialize();
                // per default all keys are valid
                if(method_exists($data, "__sleep")) {
                    $allowedVars = $data->__sleep();
                } else {
                    $allowedVars = array_keys($vars);
                }

                foreach ($allowedVars as $key) {
                    if(array_key_exists($key, $vars)) {
                        if(is_array($data->$key) || (is_object($data->$key) && !in_array(get_class($data->$key), $ignoreTypes)) ) {
                            $data->$key = self::mapElementReferences($data->$key, false);
                        }
                    }
                }

                // remove recursion detection property
                unset($data->__pimcore_tool_serialize_active);
            }
        } else if (is_array($data)) {
            foreach ($data as &$value) {
                if(is_array($value) || (is_object($value) && !in_array(get_class($value), $ignoreTypes)) ) {
                    $value = self::mapElementReferences($value, false);
                }
            }
        }

        return $data;
    }

    /**
     * @static
     * @param $data
     * @return mixed
     */
    public static function reverseMapElementReferences ($data) {

        // possibility to ignore classes (recursion)
        $ignoreTypes = array("Object_Class");
        //$ignoreTypes = array();

        if(is_object($data)) {

            if($data instanceof Element_Reference_Placeholder) {
                return Element_Service::getElementById($data->getType(), $data->getId());
            } else if(!isset($data->__pimcore_tool_serialize_active)) {

                // recursion detection
                $data->__pimcore_tool_serialize_active = true;

                $vars = get_object_vars($data);

                foreach ($vars as $key => $value) {
                    if(is_array($data->$key) || (is_object($data->$key) && !in_array(get_class($data->$key), $ignoreTypes)) ) {
                        $data->$key = self::reverseMapElementReferences($data->$key);
                    }
                }

                // remove recursion detection property
                unset($data->__pimcore_tool_serialize_active);
            }
        } else if (is_array($data)) {
            foreach ($data as &$value) {
                if(is_array($value) || (is_object($value) && !in_array(get_class($value), $ignoreTypes)) ) {
                    $value = self::reverseMapElementReferences($value);
                }
            }
        }

        return $data;
    }
}
