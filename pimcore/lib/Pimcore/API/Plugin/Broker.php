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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_API_Plugin_Broker {

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


    public static function getInstance() {

        if(Zend_Registry::isRegistered("Pimcore_API_Plugin_Broker")) {
            $broker = Zend_Registry::get("Pimcore_API_Plugin_Broker");
            if ($broker instanceof Pimcore_API_Plugin_Broker) {
                return $broker;
            }
        }

        $broker = new Pimcore_API_Plugin_Broker();
        Zend_Registry::set("Pimcore_API_Plugin_Broker", $broker);
        return $broker;
    }

    /**
     * @param string $module
     * @return void
     */
    public function registerModule($module) {
        if (Pimcore_Tool::classExists($module)) {
            $moduleInstance = new $module;
            $moduleInstance->init();
            $this->_systemModules[] = $moduleInstance;
        } else {
            throw new Exception("unknown module [ $module ].");
        }
    }


    /**
     *
     * Register a Pimcore plugin
     *
     * @param Pimcore_API_Plugin_Abstract $plugin
     * @param int $stackIndex
     */
    public function registerPlugin(Pimcore_API_Plugin_Abstract $plugin, $stackIndex = null) {
        if (false !== array_search($plugin, $this->_plugins, true)) {
            throw new Pimcore_API_Plugin_Exception('Plugin already registered');
        }

        //installed?
        if (!$plugin::isInstalled()) {
            if (is_object($plugin)) {
                $className = get_class($plugin);
                Logger::debug("Not registering plugin [ " . $className . " ] because it is not installed");
            } else {
                Logger::debug("Not registering plugin, it is not an object");
            }
            return $this;
        }


        $stackIndex = (int) $stackIndex;

        if ($stackIndex) {
            if (isset($this->_plugins[$stackIndex])) {
                throw new Pimcore_API_Plugin_Exception('Plugin with stackIndex "' . $stackIndex . '" already registered');
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
     * Unregister a Pimcore plugin.
     *
     * @param string|Pimcore_API_Plugin_Abstract $plugin Plugin object or class name
     */
    public function unregisterPlugin($plugin) {
        if ($plugin instanceof Pimcore_API_Plugin_Abstract) {
            // Given a plugin object, find it in the array
            $key = array_search($plugin, $this->_plugins, true);
            if (false === $key) {
                throw new Pimcore_API_Plugin_Exception('Plugin never registered.');
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
     * Retrieve a plugin or plugins by class
     *
     * @param  string $class Class name of plugin(s) desired
     * @return false|Pimcore_API_Plugin_Abstract|array Returns false if none found, plugin if only one found, and array of plugins if multiple plugins of same class found
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
                Logger::error("Plugin " . get_class($plugin) . " threw Exception when trying to get translations");
            }
        }
        return $translations;

    }
}
