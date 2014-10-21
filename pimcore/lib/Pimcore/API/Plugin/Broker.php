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

namespace Pimcore\API\Plugin;

use Pimcore\Tool;

class Broker {

    /**
     * Array of instance of objects extending Pimcore_API_Plugin_Abstract
     *
     * @var array
     */
    protected $_plugins = array();

    /**
     * Array of system compontents which need to be notified of hooks
     *
     * @var array
     */
    protected $_systemModules = array();

    /**
     * @return mixed|Broker
     * @throws \Zend_Exception
     */
    public static function getInstance() {

        if(\Zend_Registry::isRegistered("Pimcore_API_Plugin_Broker")) {
            $broker = \Zend_Registry::get("Pimcore_API_Plugin_Broker");
            if ($broker instanceof Broker) {
                return $broker;
            }
        }

        $broker = new Broker();
        \Zend_Registry::set("Pimcore_API_Plugin_Broker", $broker);
        return $broker;
    }

    /**
     * @param $module
     * @throws \Exception
     */
    public function registerModule($module) {
        if (Tool::classExists($module)) {
            $moduleInstance = new $module;
            $moduleInstance->init();
            $this->_systemModules[] = $moduleInstance;
        } else {
            throw new \Exception("unknown module [ $module ].");
        }
    }


    /**
     * @param AbstractPlugin $plugin
     * @param null $stackIndex
     * @return $this
     * @throws Exception
     */
    public function registerPlugin(AbstractPlugin $plugin, $stackIndex = null) {
        if (false !== array_search($plugin, $this->_plugins, true)) {
            throw new Exception('Plugin already registered');
        }

        //installed?
        if (!$plugin::isInstalled()) {
            if (is_object($plugin)) {
                $className = get_class($plugin);
                \Logger::debug("Not registering plugin [ " . $className . " ] because it is not installed");
            } else {
                \Logger::debug("Not registering plugin, it is not an object");
            }
            return $this;
        }


        $stackIndex = (int) $stackIndex;

        if ($stackIndex) {
            if (isset($this->_plugins[$stackIndex])) {
                throw new Exception('Plugin with stackIndex "' . $stackIndex . '" already registered');
            }
            $this->_plugins[$stackIndex] = $plugin;
        } else {
            $stackIndex = count($this->_plugins);
            while (isset($this->_plugins[$stackIndex])) {
                ++$stackIndex;
            }
            $this->_plugins[$stackIndex] = $plugin;
        }

        ksort($this->_plugins);

        $plugin->init();

        return $this;
    }

    /**
     * @param $plugin
     * @return $this
     * @throws Exception
     */
    public function unregisterPlugin($plugin) {
        if ($plugin instanceof AbstractPlugin) {
            // Given a plugin object, find it in the array
            $key = array_search($plugin, $this->_plugins, true);
            if (false === $key) {
                throw new Exception('Plugin never registered.');
            }
            unset($this->_plugins[$key]);
        } elseif (is_string($plugin)) {
            // Given a plugin class, find all plugins of that class and unset them
            foreach ($this->_plugins as $key => $_plugin) {
                $type = get_class($_plugin);
                if ($plugin == $type) {
                    unset($this->_plugins[$key]);
                }
            }
        }
        return $this;
    }

    /**
     * Is a plugin of a particular class registered?
     *
     * @param  string $class
     * @return bool
     */
    public function hasPlugin($class) {
        foreach ($this->_plugins as $plugin) {
            $type = get_class($plugin);
            if ($class == $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is a module of a particular class registered?
     *
     * @param  string $class
     * @return bool
     */
    public function hasModule($class) {
        foreach ($this->_systemModules as $module) {
            $type = get_class($module);
            if ($class == $type) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $class
     * @return array|bool
     */
    public function getPlugin($class) {
        $found = array();
        foreach ($this->_plugins as $plugin) {
            $type = get_class($plugin);
            if ($class == $type) {
                $found[] = $plugin;
            }
        }

        switch (count($found)) {
            case 0:
                return false;
            case 1:
                return $found[0];
            default:
                return $found;
        }
    }

    /**
     * Retrieve all plugins
     *
     * @return array
     */
    public function getPlugins() {
        return $this->_plugins;
    }

    /**
     * Retrieve all modules
     *
     * @return array
     */
    public function getModules() {
        return $this->_systemModules;
    }

    /**
     * Returns Plugins and Modules
     * @return array
     */
    public function getSystemComponents(){
        $modules = (array)$this->getModules();
        $plugins = (array)$this->getPlugins();
        return array_merge($modules,$plugins);
    }


    /**
     *
     * @param string $language
     * @return Array $translations
     */
    public function getTranslations($language) {

        $translations = array();
        foreach ($this->_plugins as $plugin) {
            try {
                $pluginLanguageFile = $plugin->getTranslationFile($language);
                if (!empty($pluginLanguageFile)) {
                    $languageFile = PIMCORE_PLUGINS_PATH . $pluginLanguageFile;

                    if (is_file($languageFile) and strtolower(substr($languageFile, -4, 4)) == ".csv") {

                        $handle = fopen($languageFile, "r");
                        while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                            $pluginTranslations[$data[0]] = $data[1];
                        }
                        fclose($handle);

                        if(is_array($pluginTranslations)){
                            $translations = array_merge($translations, $pluginTranslations);
                        }

                    }
                }
            } catch (Exception $e) {
                \Logger::error("Plugin " . get_class($plugin) . " threw Exception when trying to get translations");
            }
        }
        return $translations;

    }
}
