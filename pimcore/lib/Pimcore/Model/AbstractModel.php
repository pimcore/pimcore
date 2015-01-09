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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model;

use Pimcore\File;
use Pimcore\Resource;
use Pimcore\Tool; 

abstract class AbstractModel {

    /**
     * @var \Pimcore\Model\Resource\AbstractResource
     */
    protected $resource;

    /**
     * @var array
     */
    private static $resourceClassCache = array();

    /**
     * @return \Pimcore\Model\Resource\AbstractResource
     */
    public function getResource() {

        if (!$this->resource) {
            $this->initResource();
        }
        return $this->resource;
    }

    /**
     * @param  $resource
     * @return void
     */
    public function setResource($resource) {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @param null $key
     * @throws \Exception
     */
    public function initResource($key = null) {

        $myClass = get_class($this);
        $resource = null;

        if (!$key) {
            // check for a resource in the cache
            if(array_key_exists($myClass, self::$resourceClassCache)) {
                $resource = self::$resourceClassCache[$myClass];
            } else {
                $classes = $this->getParentClasses($myClass);

                foreach ($classes as $class) {

                    $delimiter = "_"; // old prefixed class style
                    if(strpos($class, "\\")) {
                        $delimiter = "\\"; // that's the new with namespaces
                    }

                    $classParts = explode($delimiter, $class);
                    $length = count($classParts);
                    $className = null;

                    for ($i = 0; $i < $length; $i++) {

                        // check for a general DBMS resource adapter
                        $tmpClassName = implode($delimiter, $classParts) . $delimiter . "Resource";
                        if($className = $this->determineResourceClass($tmpClassName)) {
                            break;
                        }

                        // this is just for compatibility anymore, this was before the standard way
                        // but as there will not be a specialized implementation anymore eg. Oracle, PostgreSQL, ...
                        // we can move that below the general resource adapter as a fallback
                        $tmpClassName = implode($delimiter, $classParts) . $delimiter . "Resource" . $delimiter . ucfirst(Resource::getType());
                        if($className = $this->determineResourceClass($tmpClassName)) {
                            break;
                        }

                        array_pop($classParts);
                    }

                    if($className && $className != "Pimcore\\Resource") {
                        \Logger::debug("Found resource implementation " . $className . " for " . $myClass);
                        $resource = $className;
                        self::$resourceClassCache[$myClass] = $resource;

                        break;
                    }
                }
            }
        } else {
            // check in cache
            $cacheKey = $myClass . "-" . $key;
            if(array_key_exists($cacheKey, self::$resourceClassCache)) {
                $resource = self::$resourceClassCache[$cacheKey];
            } else {
                $delimiter = "_"; // old prefixed class style
                if(strpos($key, "\\") !== false) {
                    $delimiter = "\\"; // that's the new with namespaces
                }

                // check for a specialized resource adapter for the current DBMS
                $resourceClass = $key . $delimiter . "Resource" . $delimiter . ucfirst(Resource::getType());
                if(!$resource = $this->determineResourceClass($resourceClass)) {
                    $resource = $key . $delimiter . "Resource";
                }

                self::$resourceClassCache[$cacheKey] = $resource;
            }
        }

        if(!$resource) {
            \Logger::critical("No resource implementation found for: " . $myClass);
            throw new \Exception("No resource implementation found for: " . $myClass);
        }

        $resource = "\\" . ltrim($resource, "\\");

        $this->resource = new $resource();
        $this->resource->setModel($this);

        $db = Resource::get();
        $this->resource->configure($db);

        if (method_exists($this->resource, "init")) {
            $this->resource->init();
        }
    }

    /**
     * @param $className
     */
    protected function determineResourceClass ($className) {
        $fileToInclude = str_replace(["_","\\"], "/", $className) . ".php";
        $fileToInclude = preg_replace("@^Pimcore/Model/@", "", $fileToInclude);

        if($fileToInclude == "Resource.php" || $fileToInclude == "Resource/Mysql.php") {
            return;
        }

        if (File::isIncludeable($fileToInclude)) {
            include_once($fileToInclude);
            if(Tool::classExists($className)) {
                return $className;
            }
        } else {
            \Logger::debug("Couldn't find resource implementation " . $className . " for " . get_class($this));
        }
        return;
    }

    /**
     * @param  $class
     * @return array
     */
    protected function getParentClasses ($class) {

        $classes = array();
        $classes[] = $class;

        $parentClass = get_parent_class($class);
        if($parentClass && $parentClass != get_class()) {
            $classes = array_merge($classes, $this->getParentClasses($parentClass));
        }

        return $classes;
    }

    /**
     * @param array $data
     * @return void
     */
    public function setValues($data = array()) {
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $key => $value) {
                $this->setValue($key,$value);
            }
        }
        return $this;
    }

    /**
     * @param  $key
     * @param  $value
     * @return void
     */
    public function setValue($key, $value) {
        $method = "set" . $key;
        if (method_exists($this, $method)) {
            $this->$method($value);
        } else if(method_exists($this, "set" . preg_replace("/^o_/","",$key))) {
            // compatibility mode for objects (they do not have any set_oXyz() methods anymore)
            $this->$method($value);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function __sleep() {

        $finalVars = array();
        $blockedVars = array("resource","_fulldump"); // _fulldump is a temp var which is used to trigger a full serialized dump in __sleep eg. in Document, \Object_Abstract
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }
        return $finalVars;
    }

    /**
     * @param $method
     * @param $args
     * @throws \Exception
     */
    public function __call($method, $args) {

        // check if the method is defined in resource
        if (method_exists($this->getResource(), $method)) {
            try {
                $r = call_user_func_array(array($this->getResource(), $method), $args);
                return $r;
            }
            catch (\Exception $e) {
                \Logger::emergency($e);
                throw $e;
            }
        }
        else {
            \Logger::error("Class: " . get_class($this) . " => call to undefined method " . $method);
            throw new \Exception("Call to undefined method " . $method . " in class " . get_class($this));
        }
    }

    /**
     * @return void
     */
    public function __clone() {
        $this->resource = null;
    }

    /**
     * returns object values without the resource
     *
     * @return array
     */
    public function getObjectVars(){
        $data = get_object_vars($this);
        unset($data['resource']);
        return $data;
    }
}
