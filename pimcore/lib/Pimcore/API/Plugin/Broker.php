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
     *
     * Calls preAddAsset functions of all registered plugins and system modules
     *
     * @param Asset $asset
     */
    public function preAddAsset(Asset $asset) {

        foreach ($this->_systemModules as $module) {
            $module->preAddAsset($asset);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->preAddAsset($asset);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in preAddAsset");
            }
        }

    }

    /**
     *
     * Calls postAddAsset functions of all registered plugins and system modules
     *
     * @param Asset $asset
     */
    public function postAddAsset(Asset $asset) {

        foreach ($this->_systemModules as $module) {
            $module->postAddAsset($asset);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->postAddAsset($asset);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in postAddAsset");
            }
        }

    }

    /**
     * Calls preDeleteAsset functions of all registered plugins and system modules
     *
     * @param Asset $asset
     */
    public function preDeleteAsset(Asset $asset) {
        foreach ($this->_systemModules as $module) {
            $module->preDeleteAsset($asset);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->preDeleteAsset($asset);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in preDeleteAsset");
            }
        }
    }


    /**
     * Calls postDeleteAsset functions of all registered plugins and system modules
     *
     * @param Asset $asset
     */
    public function postDeleteAsset(Asset $asset) {
        foreach ($this->_systemModules as $module) {
            $module->postDeleteAsset($asset);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->postDeleteAsset($asset);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in postDeleteAsset");
            }
        }
    }

    /**
     * Calls preUpdateAsset functions of all registered plugins and system modules
     *
     * @param Asset $asset
     */
    public function preUpdateAsset(Asset $asset) {
        foreach ($this->_systemModules as $module) {
            $module->preUpdateAsset($asset);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->preUpdateAsset($asset);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in preUpdateAsset");
            }
        }
    }

    /**
     * Calls postUpdateAsset functions of all registered plugins and system modules
     *
     * @param Asset $asset
     */
    public function postUpdateAsset(Asset $asset) {
        foreach ($this->_systemModules as $module) {
            $module->postUpdateAsset($asset);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->postUpdateAsset($asset);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in postUpdateAsset");
            }
        }
    }


    /**
     *
     * Calls preAddDocument functions of all registered plugins and system modules
     *
     * @param Document $document
     */
    public function preAddDocument(Document $document) {
        foreach ($this->_systemModules as $module) {
            $module->preAddDocument($document);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->preAddDocument($document);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in preAddDocument");
            }
        }
    }

    /**
     *
     * Calls postAddDocument functions of all registered plugins and system modules
     *
     * @param Document $document
     */
    public function postAddDocument(Document $document) {
        foreach ($this->_systemModules as $module) {
            $module->postAddDocument($document);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->postAddDocument($document);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in postAddDocument");
            }
        }
    }

    /**
     * Calls preDeleteDocument functions of all registered plugins and system modules
     *
     * @param Document $document
     */
    public function preDeleteDocument(Document $document) {
        foreach ($this->_systemModules as $module) {
            $module->preDeleteDocument($document);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->preDeleteDocument($document);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in preDeleteDocument");
            }
        }
    }

    /**
     * Calls postDeleteDocument functions of all registered plugins and system modules
     *
     * @param Document $document
     */
    public function postDeleteDocument(Document $document) {
        foreach ($this->_systemModules as $module) {
            $module->postDeleteDocument($document);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->postDeleteDocument($document);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in postDeleteDocument");
            }
        }
    }

    /**
     * Calls preUpdateDocument functions of all registered plugins and system modules
     *
     * @param Document $document
     */
    public function preUpdateDocument(Document $document) {
        foreach ($this->_systemModules as $module) {
            $module->preUpdateDocument($document);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->preUpdateDocument($document);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in preUpdateDocument");
            }
        }
    }




    /**
     * Calls postUpdateDocument functions of all registered plugins and system modules
     *
     * @param Document $document
     */
    public function postUpdateDocument(Document $document) {
        foreach ($this->_systemModules as $module) {
            $module->postUpdateDocument($document);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->postUpdateDocument($document);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in postUpdateDocument");
            }
        }
    }


    /**
     * Calls preAddObject functions of all registered plugins and system modules
     *
     * @param Object_Abstract $object
     */
    public function preAddObject(Object_Abstract $object) {
        foreach ($this->_systemModules as $module) {
            $module->preAddObject($object);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->preAddObject($object);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in preAddObject");
            }
        }
    }


    /**
     * Calls postAddObject functions of all registered plugins and system modules
     *
     * @param Object_Abstract $object
     */
    public function postAddObject(Object_Abstract $object) {
        foreach ($this->_systemModules as $module) {
            $module->postAddObject($object);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->postAddObject($object);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in postAddObject");
            }
        }
    }

    /**
     * Calls preDeleteObject functions of all registered plugins and system modules
     *
     * @param Object_Abstract $object
     */
    public function preDeleteObject(Object_Abstract $object) {
        foreach ($this->_systemModules as $module) {
            $module->preDeleteObject($object);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->preDeleteObject($object);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in preDeleteObject");
            }
        }
    }

    /**
     * Calls postDeleteObject functions of all registered plugins and system modules
     *
     * @param Object_Abstract $object
     */
    public function postDeleteObject(Object_Abstract $object) {
        foreach ($this->_systemModules as $module) {
            $module->postDeleteObject($object);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->postDeleteObject($object);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in postDeleteObject");
            }
        }
    }

    /**
     * Calls preUpdateObject functions of all registered plugins and system modules
     *
     * @param Object_Abstract $object
     */
    public function preUpdateObject(Object_Abstract $object) {
        foreach ($this->_systemModules as $module) {
            $module->preUpdateObject($object);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->preUpdateObject($object);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in preUpdateObject");
            }
        }
    }


    /**
     * Calls postUpdateObject functions of all registered plugins and system modules
     *
     * @param Object_Abstract $object
     */
    public function postUpdateObject(Object_Abstract $object) {
        foreach ($this->_systemModules as $module) {
            $module->postUpdateObject($object);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->postUpdateObject($object);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in postUpdateObject");
            }
        }
    }


    /**
     * Calls preLogoutUser functions of all registered plugins and system modules
     *
     * @param User $user
     */
    public function preLogoutUser(User $user) {
        foreach ($this->_systemModules as $module) {
            $module->preLogoutUser($user);
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->preLogoutUser($user);
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in preLogoutUser");
            }
        }
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
            try {
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
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in postLoginUser");
            }
        }

        return null;
    }



    /**
     *
     * Calls preDispatch of all registered plugins and system modules
     */
    public function preDispatch() {
        foreach ($this->_systemModules as $module) {
            $module->preDispatch();
        }
        foreach ($this->_plugins as $plugin) {
            try {
                $plugin->preDispatch();
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in preDispatch");
            }
        }
    }


    /**
     * Calls maintenance functions of all registered plugins and system modules
     */
    public function maintenance() {
        foreach ($this->_plugins as $plugin) {
            try {
                if(method_exists($plugin, "maintainance")) {
                    $plugin->maintainance();
                } else if(method_exists($plugin, "maintenance")) {
                    $plugin->maintenance();
                }
            } catch (Exception $e) {
                Logger::error("Plugin " . get_class($plugin) . " threw Exception in maintenance");
            }
        }
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
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
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
