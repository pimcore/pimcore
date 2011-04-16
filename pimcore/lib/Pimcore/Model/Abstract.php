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

abstract class Pimcore_Model_Abstract {

    protected $resource;

    public function getResource() {

        if (!$this->resource) {
            $this->initResource();
        }
        return $this->resource;
    }

    public function setResource($resource) {
        $this->resource = $resource;
    }

    protected function initResource($key = null) {

        if (!$key) {

            $classes = $this->getParentClasses(get_class($this));

            foreach ($classes as $class) {
                $classParts = explode("_", $class);
                $length = count($classParts);
                $className = null;

                for ($i = 0; $i < $length; $i++) {
                    $tmpClassName = implode("_", $classParts) . "_Resource_Mysql";
                    
                    $fileToInclude = str_replace("_", "/", $tmpClassName) . ".php";
                    if (is_includeable($fileToInclude)) {
                        include_once($fileToInclude);
                        if(class_exists($tmpClassName)) {
                            $className = $tmpClassName;
                            break;
                        }
                    }
                    else {
                        Logger::debug("Couldn't find resource implementation " . $tmpClassName . " for " . get_class($this));
                    }
                    array_pop($classParts);
                }

                if($className) {
                    Logger::debug("Find resource implementation " . $className . " for " . get_class($this));
                    $resource = $className;
                    break;
                }
            }
        }
        else {
            $resource = $key . "_Resource_Mysql";
        }

        if(!$resource) {
            Logger::critical("No resource implementation found for: " . get_class($this));
            throw new Exception("No resource implementation found for: " . get_class($this));
        }

        $this->resource = new $resource();
        $this->resource->setModel($this);

        $db = Pimcore_Resource_Mysql::get();

        $this->resource->configure($db);

        if (method_exists($this->resource, "init")) {
            $this->resource->init();
        }
    }

    protected function getParentClasses ($class) {

        $classes = array();
        $classes[] = $class;

        $parentClass = get_parent_class($class);
        if($parentClass && $parentClass != get_class()) {
            $classes = array_merge($classes, $this->getParentClasses($parentClass));
        }

        return $classes;
    }

    public function setValues($data = array()) {
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $key => $value) {
                $this->setValue($key,$value);
            }
        }
    }

    public function setValue($key, $value) {
        $method = "set" . $key;
        if (method_exists($this, $method)) {
            $this->$method($value);
        } else if($this instanceof User) {
            $method = "setPermission";
            if (method_exists($this, $method)) {
                $this->$method($key);
            }
        }
    }

    public function __sleep() {
        $blockedVars = array("resource","_fulldump"); // _fulldump is a temp var wich is used to trigger a full serialized dump in __sleep eg. in Document, Object_Abstract
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }
        return $finalVars;
    }

    public function __call($method, $args) {

        // check if the method is defined in resource
        if (method_exists($this->getResource(), $method)) {
            try {
                $r = call_user_func_array(array($this->getResource(), $method), $args);
                return $r;
            }
            catch (Exception $e) {
                logger::emergency($e);
                throw $e;
            }
        }
        else {
            Logger::error("Class: " . get_class($this) . " => call to undefined method " . $method);
            throw new Exception("Call to undefined method " . $method . " in class " . get_class($this));
        }
    }

    public function __clone() {
        $this->resource = null;
    }
}
