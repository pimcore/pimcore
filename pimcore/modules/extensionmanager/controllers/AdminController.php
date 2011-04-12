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

class Extensionmanager_AdminController extends Pimcore_Controller_Action_Admin {

    public function init () {
        parent::init();

        if (!$this->getUser()->isAllowed("plugins")) {
            if ($this->getUser() != null) {
                Logger::err(get_class($this) . ": user [" . $this->getUser()->getId() . "] attempted to install plugin, but has no permission to do so.");
            } else {
                Logger::err(get_class($this) . ": attempt to install plugin, but no user in session.");
            }
        }
    }

    public function getExtensionsAction () {

        $configurations = array();

        $pluginConfigs = Pimcore_ExtensionManager::getPluginConfigs();

        $counter = 0;
        foreach ($pluginConfigs as $config) {
            $className = $config["plugin"]["pluginClassName"];
            if (!empty($className)) {
                $isEnabled = Pimcore_ExtensionManager::isEnabled("plugin", $config["plugin"]["pluginName"]);
                $plugin = array(
                    "id" => $config["plugin"]["pluginName"],
                    "type" => "plugin",
                    "name" => $config["plugin"]["pluginNiceName"],
                    "description" => $config["plugin"]["pluginDescription"],
                    "icon" => $config["plugin"]["pluginIcon"],
                    "version" => $config["plugin"]["pluginVersion"],
                    "installed" => $isEnabled ? $className::isInstalled() : null,
                    "active" => $isEnabled,
                    "configuration" => $config["plugin"]["pluginIframeSrc"]
                );
                $configurations[] = $plugin;
            }
        }

        $this->_helper->json(array("extensions" => $configurations));
    }

    public function toggleExtensionStateAction () {
        $type = $this->_getParam("type");
        $id = $this->_getParam("id");
        $method = $this->_getParam("method");

        if($type && $id) {
            Pimcore_ExtensionManager::$method($type, $id);
        }

        $this->_helper->json(array("success" => true));
    }


    public function installAction() {

        $type = $this->_getParam("type");
        $id = $this->_getParam("id");

        if($type == "plugin") {
            $className = $this->_getParam("className");
            $plugin = $this->_getParam("name");
            $message = $className::install();


            $success = Pimcore_Update::downloadPluginLanguages($plugin);
            if(!$success){
                $message.="could not download all plugin translations";
            }

            $this->_helper->json(array(
                "message" => $message,
                "reload" => $className::needsReloadAfterInstall(),
                "status" => array(
                    "installed" => $className::isInstalled()
                )
            ));
        }
    }

    public function uninstallAction() {

        $type = $this->_getParam("type");
        $id = $this->_getParam("id");

        if($type == "plugin") {
            $className = $this->_getParam("className");
            $message = $className::uninstall();
            echo Zend_Json::encode(array("message" => $message, "pluginJsClassName" => $className::getJsClassName(), "status" => array("installed" => $className::isInstalled())));

            $this->_helper->json(array("success" => true));
        }
    }
}
