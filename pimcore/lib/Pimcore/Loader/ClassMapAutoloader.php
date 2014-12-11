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

namespace Pimcore\Loader;

use Pimcore\Tool;

class ClassMapAutoloader extends \Zend_Loader_ClassMapAutoloader {

    public function autoload($class) {

        parent::autoload($class);

        // reverse compatibility from namespaced to prefixed class names e.g. Pimcore\Model\Document => Document
        if(strpos($class, "Pimcore\\") === 0) {

            // first check for a model, if it doesnt't work fall back to the default autoloader
            if(!class_exists($class, false) && !interface_exists($class, false)) {
                if(!$this->loadModel($class)) {
                    $loader = \Zend_Loader_Autoloader::getInstance();
                    $loader->autoload($class);
                }
            }

            if(class_exists($class, false) || interface_exists($class, false)) {
                // create an alias
                $alias = str_replace("\\", "_", $class);
                $alias = preg_replace("/_Abstract(.*)/", "_Abstract", $alias);
                $alias = preg_replace("/_[^_]+Interface/", "_Interface", $alias);
                $alias = str_replace("_Listing_", "_List_", $alias);
                $alias = preg_replace("/_Listing$/", "_List", $alias);
                $alias = str_replace("Object_ClassDefinition", "Object_Class", $alias);

                if(strpos($alias, "Pimcore_Model") === 0) {
                    if(!preg_match("/^Pimcore_Model_(Abstract|List|Resource|Cache)/", $alias)) {
                        $alias = str_replace("Pimcore_Model_", "", $alias);
                    }
                }

                if(!class_exists($alias, false) && !interface_exists($alias, false)) {
                    class_alias($class, $alias);
                    return; // skip here, nothing more to do ...
                }
            }
        }

        // compatibility layer from prefixed to namespaced e.g. Document => Pimcore\Model\Document
        $isLegacyClass = preg_match("/^(Pimcore_|Asset|Dependency|Document|Element|Glossary|Metadata|Object|Property|Redirect|Schedule|Site|Staticroute|Tool|Translation|User|Version|Webservice|WebsiteSetting|Search)/", $class);
        if(!class_exists($class, false) && !interface_exists($class, false) && $isLegacyClass) {

            // this is for debugging purposes, to find legacy class names
            if(PIMCORE_DEBUG) {
                $backtrace = debug_backtrace();
                foreach($backtrace as $step) {
                    if(isset($step["file"]) && !empty($step["file"]) && $step["function"] != "class_exists") {
                        $logName = "legacy-class-names";
                        if(preg_match("@^" . preg_quote(PIMCORE_PATH, "@") . "@", $step["file"])) {
                            $logName .= "-admin";
                        }

                        \Pimcore\Log\Simple::log($logName, $class . " used in " . $step["file"] . " at line " . $step["line"]);
                        break;
                    }
                }
            }

            $namespacedClass = $class;
            $namespacedClass = str_replace("_List", "_Listing", $namespacedClass);
            $namespacedClass = str_replace("Object_Class", "Object_ClassDefinition", $namespacedClass);
            $namespacedClass = preg_replace("/([^_]+)_Abstract$/", "$1_Abstract$1", $namespacedClass);
            $namespacedClass = preg_replace("/([^_]+)_Interface$/", "$1_$1Interface", $namespacedClass);
            $namespacedClass = str_replace("_", "\\", $namespacedClass);

            if(strpos($namespacedClass, "Pimcore") !== 0) {
                $namespacedClass = "Pimcore\\Model\\" . $namespacedClass;
            }

            // check if the class is a model, if so, load it
            $this->loadModel($namespacedClass);

            if(Tool::classExists($namespacedClass) || Tool::interfaceExists($namespacedClass)) {
                if(!class_exists($class, false) && !interface_exists($class, false)) {
                    class_alias($namespacedClass, $class);
                }
            }
        }
    }

    protected function loadModel($class) {
        if(strpos($class, "Pimcore\\Model\\") === 0) {
            $modelFile = PIMCORE_PATH . "/models/" . str_replace(["Pimcore\\Model\\","\\"], ["","/"], $class) . ".php";
            if(file_exists($modelFile)) {
                include_once $modelFile;
                return true;
            }

            if(strpos($class, "Pimcore\\Model\\Object\\") === 0) {
                $modelFile = PIMCORE_CLASS_DIRECTORY . "/" . str_replace(["Pimcore\\Model\\","\\"], ["","/"], $class) . ".php";
                if(file_exists($modelFile)) {
                    include_once $modelFile;
                    return true;
                }
            }
        }
    }
}
