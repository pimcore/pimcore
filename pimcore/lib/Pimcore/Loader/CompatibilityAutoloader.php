<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Loader;

use Pimcore\Tool;

class CompatibilityAutoloader
{
    /**
     * @var null|\Composer\Autoload\ClassLoader
     */
    protected $composerAutoloader = null;

    /**
     * CompatibilityAutoloader constructor.
     * @param $composerAutoloader
     */
    public function __construct($composerAutoloader)
    {
        $this->composerAutoloader = $composerAutoloader;
    }

    /**
     * @param $class
     * @return bool
     */
    public function loadClass($class)
    {
        // manual aliasing
        $classAliases = [
            "Pimcore\\Resource" => "Pimcore\\Db",
            "Pimcore_Resource" => "Pimcore\\Db",
            "Pimcore\\Resource\\Mysql" => "Pimcore\\Db",
            "Pimcore_Resource_Mysql" => "Pimcore\\Db",
            "Pimcore\\Log\\Log" => "Pimcore\\Log\\ApplicationLogger",
            "Pimcore\\Log\\Writer\\Db" => "Pimcore\\Log\\Handler\\ApplicationLoggerDb",
            "Pimcore\\Model\\Cache" => "Pimcore\\Cache",
            "Logger" => "Pimcore\\Logger",
        ];

        if (array_key_exists($class, $classAliases)) {
            class_alias($classAliases[$class], $class);

            return true;
        }

        // compatibility from Resource => Dao
        if (strpos($class, "Resource") && !class_exists($class, false) && !interface_exists($class, false)) {
            $daoClass = str_replace("Resource", "Dao", $class);
            if (Tool::classExists($daoClass) || Tool::interfaceExists($daoClass)) {
                if (!class_exists($class, false) && !interface_exists($class, false)) {
                    class_alias($daoClass, $class);
                }
            }
        }

        // reverse compatibility from namespaced to prefixed class names e.g. Pimcore\Model\Document => Document
        if (strpos($class, "Pimcore\\") === 0) {

            // first check for a model, if it doesnt't work fall back to the default autoloader
            if (!class_exists($class, false) && !interface_exists($class, false)) {
                $this->composerAutoloader->loadClass($class);
            }

            if (class_exists($class, false) || interface_exists($class, false)) {
                // create an alias
                $alias = str_replace("\\", "_", $class);
                $alias = preg_replace("/_Abstract([^_]+)/", "_Abstract", $alias);
                $alias = preg_replace("/_[^_]+Interface/", "_Interface", $alias);
                $alias = str_replace("_Listing_", "_List_", $alias);
                $alias = preg_replace("/_Listing$/", "_List", $alias);
                $alias = str_replace("Object_ClassDefinition", "Object_Class", $alias);

                if (strpos($alias, "Pimcore_Model") === 0) {
                    if (!preg_match("/^Pimcore_Model_(Abstract|List|Resource|Cache)/", $alias)) {
                        $alias = str_replace("Pimcore_Model_", "", $alias);
                    }
                }

                if (!class_exists($alias, false) && !interface_exists($alias, false)) {
                    class_alias($class, $alias);

                    return true; // skip here, nothing more to do ...
                }
            }
        }

        // compatibility layer from prefixed to namespaced e.g. Document => Pimcore\Model\Document
        $isLegacyClass = preg_match("/^(Pimcore_|Asset|Dependency|Document|Element|Glossary|Metadata|Object|Property|Redirect|Schedule|Site|Staticroute|Tool|Translation|User|Version|Webservice|WebsiteSetting|Search)/", $class);
        if (!class_exists($class, false) && !interface_exists($class, false) && $isLegacyClass) {

            // this is for debugging purposes, to find legacy class names
            if (PIMCORE_DEBUG) {
                $backtrace = debug_backtrace();
                foreach ($backtrace as $step) {
                    if (isset($step["file"]) && !empty($step["file"]) && $step["function"] != "class_exists") {
                        $logName = "legacy-class-names";
                        if (preg_match("@^" . preg_quote(PIMCORE_PATH, "@") . "@", $step["file"])) {
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

            if (strpos($namespacedClass, "Pimcore") !== 0) {
                $namespacedClass = "Pimcore\\Model\\" . $namespacedClass;
            }

            // check if the class is a model, if so, load it
            if (!class_exists($namespacedClass, false) && !interface_exists($namespacedClass, false)) {
                $this->composerAutoloader->loadClass($namespacedClass);
            }

            if (Tool::classExists($namespacedClass) || Tool::interfaceExists($namespacedClass)) {
                if (!class_exists($class, false) && !interface_exists($class, false)) {
                    class_alias($namespacedClass, $class);

                    return true;
                }
            }
        }
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool $prepend
     */
    public function register($prepend = false)
    {
        spl_autoload_register([$this, 'loadClass'], true, $prepend);
    }

    /**
     * Removes this instance from the registered autoloaders.
     */
    public function unregister()
    {
        spl_autoload_unregister([$this, 'loadClass']);
    }
}
