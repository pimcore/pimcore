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
            $this->_systemModules[] = new $module();
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
     * Calls preAddAsset functions of all registered plugins and system modules
     *
     * @param Asset $asset
     */
    public function preAddAsset(Asset $asset) {
        $this->executeMethod('preAddAsset', $asset);
    }

    /**
     *
     * Calls postAddAsset functions of all registered plugins and system modules
     *
     * @param Asset $asset
     */
    public function postAddAsset(Asset $asset) {
        $this->executeMethod('postAddAsset', $asset);
    }

    /**
     * Calls preDeleteAsset functions of all registered plugins and system modules
     *
     * @param Asset $asset
     */
    public function preDeleteAsset(Asset $asset) {
        $this->executeMethod('preDeleteAsset', $asset);
    }


    /**
     * Calls postDeleteAsset functions of all registered plugins and system modules
     *
     * @param Asset $asset
     */
    public function postDeleteAsset(Asset $asset) {
        $this->executeMethod('postDeleteAsset', $asset);
    }

    /**
     * Calls preUpdateAsset functions of all registered plugins and system modules
     *
     * @param Asset $asset
     */
    public function preUpdateAsset(Asset $asset) {
        $this->executeMethod('preUpdateAsset', $asset);
    }

    /**
     * Calls postUpdateAsset functions of all registered plugins and system modules
     *
     * @param Asset $asset
     */
    public function postUpdateAsset(Asset $asset) {
        $this->executeMethod('postUpdateAsset', $asset);
    }


    /**
     *
     * Calls preAddDocument functions of all registered plugins and system modules
     *
     * @param Document $document
     */
    public function preAddDocument(Document $document) {
        $this->executeMethod('preAddDocument', $document);
    }

    /**
     *
     * Calls postAddDocument functions of all registered plugins and system modules
     *
     * @param Document $document
     */
    public function postAddDocument(Document $document) {
        $this->executeMethod('postAddDocument', $document);
    }

    /**
     * Calls preDeleteDocument functions of all registered plugins and system modules
     *
     * @param Document $document
     */
    public function preDeleteDocument(Document $document) {
        $this->executeMethod('preDeleteDocument', $document);
    }

    /**
     * Calls postDeleteDocument functions of all registered plugins and system modules
     *
     * @param Document $document
     */
    public function postDeleteDocument(Document $document) {
        $this->executeMethod('postDeleteDocument', $document);
    }

    /**
     * Calls preUpdateDocument functions of all registered plugins and system modules
     *
     * @param Document $document
     */
    public function preUpdateDocument(Document $document) {
        $this->executeMethod('preUpdateDocument', $document);
    }

    /**
     * Calls postUpdateDocument functions of all registered plugins and system modules
     *
     * @param Document $document
     */
    public function postUpdateDocument(Document $document) {
        $this->executeMethod('postUpdateDocument', $document);
    }



    /**
     * Calls preAddObject functions of all registered plugins and system modules
     *
     * @param Object_Abstract $object
     */
    public function preAddObject(Object_Abstract $object) {
        $this->executeMethod('preAddObject', $object);
    }

    /**
     * Calls postAddObject functions of all registered plugins and system modules
     *
     * @param Object_Abstract $object
     */
    public function postAddObject(Object_Abstract $object) {
        $this->executeMethod('postAddObject', $object);
    }

    /**
     * Calls preDeleteObject functions of all registered plugins and system modules
     *
     * @param Object_Abstract $object
     */
    public function preDeleteObject(Object_Abstract $object) {
        $this->executeMethod('preDeleteObject', $object);
    }

    /**
     * Calls postDeleteObject functions of all registered plugins and system modules
     *
     * @param Object_Abstract $object
     */
    public function postDeleteObject(Object_Abstract $object) {
        $this->executeMethod('postDeleteObject', $object);
    }

    /**
     * Calls preUpdateObject functions of all registered plugins and system modules
     *
     * @param Object_Abstract $object
     */
    public function preUpdateObject(Object_Abstract $object) {
        $this->executeMethod('preUpdateObject', $object);
    }

    /**
     * Calls postUpdateObject functions of all registered plugins and system modules
     *
     * @param Object_Abstract $object
     */
    public function postUpdateObject(Object_Abstract $object) {
        $this->executeMethod('postUpdateObject', $object);
    }


    /**
     * Calls preLogoutUser functions of all registered plugins and system modules
     *
     * @param User $user
     */
    public function preLogoutUser(User $user) {
        $this->executeMethod('preLogoutUser', $user);
    }

    /**
     * Calls postLoginUser functions of system modules and registered plugins,
     * stops once the user has been authenticated successfully
     * by a module or plugin and the user is valid
     *
     * @param string $username
     * @param string password
     * @return User $user
     */
    public function authenticateUser($username, $password) {
        foreach ($this->_systemModules as $module) {
            $user = $module->authenticateUser($username, $password);
            if($user instanceof User){
                if(!$user->isActive()){
                    Logger::error("User provided by module [ ".get_class($module)." ] is inactive");
                } else if (!$user->getId()){
                    Logger::error("User provided by module [ ".get_class($module)." ] has no id");
                } else {
                    return $user;
                }
            }


        }
        foreach ($this->_plugins as $plugin) {
            $user = $plugin->authenticateUser($username, $password);
            if($user instanceof User){
                if(!$user->isActive()){
                    Logger::error("User provided by plugin [ ".get_class($plugin)." ] is inactive");
                } else if (!$user->getId()){
                    Logger::error("User provided by plugin [ ".get_class($plugin)." ] has no id");
                } else {
                    return $user;
                }
            }
        }

        return null;
    }



    /**
     *
     * Calls preDispatch of all registered plugins and system modules
     */
    public function preDispatch() {
        $this->executeMethod('preDispatch');
    }


    /**
     * Calls maintenance functions of all registered plugins and system modules
     */
    public function maintenance() {
        $this->executeMethod('maintenance');
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


    /**
     *
     * Calls preAddKeyValueKeyConfig functions of all registered plugins and system modules
     *
     * @param Object_KeyValue_KeyConfig $config
     */
    public function preAddKeyValueKeyConfig(Object_KeyValue_KeyConfig $config) {
        $this->executeMethod('preAddKeyValueKeyConfig', $config);
    }

    /**
     *
     * Calls postAddKeyValueKeyConfig functions of all registered plugins and system modules
     *
     * @param Object_KeyValue_KeyConfig $config
     */
    public function postAddKeyValueKeyConfig(Object_KeyValue_KeyConfig $config) {
        $this->executeMethod('postAddKeyValueKeyConfig', $config);
    }

    /**
     * Calls preDeleteKeyValueKeyConfig functions of all registered plugins and system modules
     *
     * @param preDeleteKeyValueKeyConfig $config
     */
    public function preDeleteKeyValueKeyConfig(Object_KeyValue_KeyConfig $config) {
        $this->executeMethod('preDeleteKeyValueKeyConfig', $config);
    }


    /**
     * Calls postDeleteKeyValueKeyConfig functions of all registered plugins and system modules
     *
     * @param Object_KeyValue_KeyConfig $asset
     */
    public function postDeleteKeyValueKeyConfig(Object_KeyValue_KeyConfig $config) {
        $this->executeMethod('postDeleteKeyValueKeyConfig', $config);
    }

    /**
     * Calls preUpdateKeyValueKeyConfig functions of all registered plugins and system modules
     *
     * @param Object_KeyValue_KeyConfig $asset
     */
    public function preUpdateKeyValueKeyConfig(Object_KeyValue_KeyConfig $config) {
        $this->executeMethod('preUpdateKeyValueKeyConfig', $config);
    }

    /**
     * Calls postUpdateKeyValueKeyConfig functions of all registered plugins and system modules
     *
     * @param Object_KeyValue_KeyConfig $config
     */
    public function postUpdateKeyValueKeyConfig(Object_KeyValue_KeyConfig $config) {
        $this->executeMethod('postUpdateKeyValueKeyConfig', $config);
    }


    /**
     *
     * Calls preAddKeyValueGroupConfig functions of all registered plugins and system modules
     *
     * @param Object_KeyValue_GroupConfig $config
     */
    public function preAddKeyValueGroupConfig(Object_KeyValue_GroupConfig $config) {
        $this->executeMethod('preAddKeyValueGroupConfig', $config);
    }

    /**
     *
     * Calls postAddKeyValueGroupConfig functions of all registered plugins and system modules
     *
     * @param Object_KeyValue_GroupConfig $config
     */
    public function postAddKeyValueGroupConfig(Object_KeyValue_GroupConfig $config) {
        $this->executeMethod('postAddKeyValueGroupConfig', $config);
    }

    /**
     * Calls preDeleteKeyValueGroupConfig functions of all registered plugins and system modules
     *
     * @param preDeleteKeyValueGroupConfig $config
     */
    public function preDeleteKeyValueGroupConfig(Object_KeyValue_GroupConfig $config) {
        $this->executeMethod('preDeleteKeyValueGroupConfig', $config);
    }


    /**
     * Calls postDeleteKeyValueGroupConfig functions of all registered plugins and system modules
     *
     * @param Object_KeyValue_GroupConfig $asset
     */
    public function postDeleteKeyValueGroupConfig(Object_KeyValue_GroupConfig $config) {
        $this->executeMethod('postDeleteKeyValueGroupConfig', $config);
    }

    /**
     * Calls preUpdateKeyValueGroupConfig functions of all registered plugins and system modules
     *
     * @param Object_KeyValue_GroupConfig $asset
     */
    public function preUpdateKeyValueGroupConfig(Object_KeyValue_GroupConfig $config) {
        $this->executeMethod('preUpdateKeyValueGroupConfig', $config);
    }

    /**
     * Calls postUpdateKeyValueGroupConfig functions of all registered plugins and system modules
     *
     * @param Object_KeyValue_GroupConfig $config
     */
    public function postUpdateKeyValueGroupConfig(Object_KeyValue_GroupConfig $config) {
        $this->executeMethod('postUpdateKeyValueGroupConfig', $config);
    }

    /**
     * Calls preAddObjectClass functions of all registered plugins and system modules
     *
     * @param Object_Class $class
     */
    public function preAddObjectClass(Object_Class $class) {
        $this->executeMethod('preAddObjectClass',$class);
    }

    /**
     * Calls preUpdateObjectClass functions of all registered plugins and system modules
     *
     * @param Object_Class $class
     */
    public function preUpdateObjectClass(Object_Class $class) {
        $this->executeMethod('preUpdateObjectClass',$class);
    }



    protected function executeMethod($method){

        $arguments = func_get_args();
        array_shift($arguments);

        foreach ($this->_systemModules as $module) {
            call_user_func_array(array($module, $method), $arguments);
        }
        foreach ($this->_plugins as $plugin) {
            call_user_func_array(array($plugin, $method), $arguments);
        }
    }

}
