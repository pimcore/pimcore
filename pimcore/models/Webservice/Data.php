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

abstract class Webservice_Data {

    public function map($object) {
        $keys = get_object_vars($this);
        foreach ($keys as $key => $value) {

                $method = "get" . $key;
                if (method_exists($object, $method)) {
                    if ($object->$method()) {
                        $this->$key = $object->$method();

                        // check for a pimcore data type
                        if ($this->$key instanceof Element_Interface) {
                            $this->$key = $this->$key->getId();
                        }

                        // if the value is an object or array call the mapper again for the value
                        if (is_object($this->$key) || is_array($this->$key)) {
                            $type = "out";
                            if (strpos(get_class($this), "_In")!==FALSE) {
                                $type = "in";
                            }
                            $className = Webservice_Data_Mapper::findWebserviceClass($this->$key, "out");
                            $this->$key = Webservice_Data_Mapper::map($this->$key, $className, $type);
                        }


                    }
                }


        }


    }

    public function reverseMap($object) {

        $keys = get_object_vars($this);
        foreach ($keys as $key => $value) {
            $method = "set" . $key;
            if (method_exists($object, $method)) {
                $object->$method($value);
            }
        }

        $object->setProperties(null);
        if (is_array($this->properties)) {
            foreach ($this->properties as $propertyWs) {

                $dat = $propertyWs->data;
                $type = $propertyWs->type;
                if (in_array($type, array("object", "document", "asset"))) {
                    $dat = Element_Service::getElementById($propertyWs->type, $propertyWs->data);
                    if (is_numeric($propertyWs->data) and !$dat) {
                        throw new Exception("cannot import property [ " . $propertyWs->name . " ] because it references unknown " . $propertyWs->type);
                    }
                } else if ($type == "date"){
                    $dat = new Pimcore_Date(strtotime($propertyWs->data));
                } else {
                    $dat = $propertyWs->data;
                }


                $object->setProperty($propertyWs->name, $propertyWs->type, $dat, $propertyWs->inherited);
            }
        }


    }
}
