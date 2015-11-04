<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model;

use Pimcore\File;
use Pimcore\Db;
use Pimcore\Tool; 

abstract class AbstractModel {

    /**
     * @var \Pimcore\Model\Dao\AbstractDao
     */
    protected $dao;

    /**
     * @var array
     */
    private static $daoClassCache = array();

    /**
     * @return \Pimcore\Model\Dao\AbstractDao
     */
    public function getDao() {

        if (!$this->dao) {
            $this->initDao();
        }
        return $this->dao;
    }

    /**
     * @param $dao
     * @return self
     */
    public function setDao($dao) {
        $this->dao = $dao;
        return $this;
    }

    /**
     * @param null $key
     * @throws \Exception
     */
    public function initDao($key = null) {

        $myClass = get_class($this);
        $dao = null;

        if (!$key) {
            // check for a resource in the cache
            if(array_key_exists($myClass, self::$daoClassCache)) {
                $dao = self::$daoClassCache[$myClass];
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

                        // check for a general dao adapter
                        $tmpClassName = implode($delimiter, $classParts) . $delimiter . "Dao";
                        if($className = $this->determineResourceClass($tmpClassName)) {
                            break;
                        }

                        // check for the old style resource adapter
                        $tmpClassName = implode($delimiter, $classParts) . $delimiter . "Resource";
                        if($className = $this->determineResourceClass($tmpClassName)) {
                            break;
                        }

                        array_pop($classParts);
                    }

                    if($className && $className != "Pimcore\\Resource") {
                        $dao = $className;
                        self::$daoClassCache[$myClass] = $dao;

                        break;
                    }
                }
            }
        } else {
            // check in cache
            $cacheKey = $myClass . "-" . $key;
            if(array_key_exists($cacheKey, self::$daoClassCache)) {
                $dao = self::$daoClassCache[$cacheKey];
            } else {
                $delimiter = "_"; // old prefixed class style
                if(strpos($key, "\\") !== false) {
                    $delimiter = "\\"; // that's the new with namespaces
                }

                $dao = $key . $delimiter . "Dao";

                self::$daoClassCache[$cacheKey] = $dao;
            }
        }

        if(!$dao) {
            \Logger::critical("No dao implementation found for: " . $myClass);
            throw new \Exception("No dao implementation found for: " . $myClass);
        }

        $dao = "\\" . ltrim($dao, "\\");

        $this->dao = new $dao();
        $this->dao->setModel($this);

        $db = Db::get();
        $this->dao->configure($db);

        if (method_exists($this->dao, "init")) {
            $this->dao->init();
        }
    }

    /**
     * @param $className
     */
    protected function determineResourceClass ($className) {

        $filesToInclude = [];

        $filePath = str_replace(["_","\\"], "/", $className) . ".php";
        $filesToInclude[] = preg_replace("@^Pimcore/Model/@", "", $filePath);
        $filesToInclude[] = $filePath;

        foreach($filesToInclude as $fileToInclude) {
            if($fileToInclude == "Dao.php" || $fileToInclude == "Resource.php") {
                return;
            }

            if (File::isIncludeable($fileToInclude)) {
                include_once($fileToInclude);
                if(Tool::classExists($className)) {
                    return $className;
                }
            }
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
     * @return $this
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
     * @return $this
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
        $blockedVars = array("dao","_fulldump"); // _fulldump is a temp var which is used to trigger a full serialized dump in __sleep eg. in Document, \Object_Abstract
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
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $args) {

        // check if the method is defined in ´dao
        if (method_exists($this->getDao(), $method)) {
            try {
                $r = call_user_func_array(array($this->getDao(), $method), $args);
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
        $this->dao = null;
    }

    /**
     * returns object values without the dao
     *
     * @return array
     */
    public function getObjectVars(){
        $data = get_object_vars($this);
        unset($data['dao']);
        return $data;
    }
}
