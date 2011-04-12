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
class Pluginadmin_AdminController extends Pimcore_Controller_Action_Admin {

    public function init() {
        parent::init();

        // check permissions
        $notRestrictedActions = array();
        if (!in_array($this->_getParam("action"), $notRestrictedActions)) {
            if (!$this->getUser()->isAllowed("plugins")) {

                $this->_redirect("/admin/login");
                die();
            }
        }

        //check if plugins folder exists
        if (!is_dir(PIMCORE_PLUGINS_PATH)) {
            echo Zend_Json::encode(array(
                "error" => "plugin_dir_error"
            ));
            die();
        }

    }

    /**
     *
     * @param String|Array $toConvert
     */
    private function getStringOrArrayAsCSV($toConvert) {
        $csv = null;
        if (is_array($toConvert)) {
            $csv = implode(",", $toConvert);
        } else {
            $csv = $toConvert;
        }
        return $csv;
    }


    public function getPluginsAction() {


        if(!is_writable(PIMCORE_PLUGINS_PATH)){
            logger::err("Pluginadmin_DownloadController: plugin directory is not writeable");
            die();
        }

        $pluginConfigs = Pimcore_ExtensionManager::getPluginConfigs();

        $counter = 0;
        foreach ($pluginConfigs as $config) {
            $className = $pluginConfigs[$counter]["plugin"]["pluginClassName"];
            if (!empty($className)) {
                $pluginConfigs[$counter]["plugin"]["includePathString"] = $this->getStringOrArrayAsCSV($config["plugin"]["pluginIncludePaths"]["path"]);
                unset($pluginConfigs[$counter]["plugin"]["pluginIncludePaths"]);

                $pluginConfigs[$counter]["plugin"]["namespaceString"] = $this->getStringOrArrayAsCSV($config["plugin"]["pluginNamespaces"]["namespace"]);
                unset($pluginConfigs[$counter]["plugin"]["pluginNamespaces"]);

                $pluginConfigs[$counter]["plugin"]["jsPathString"] = $this->getStringOrArrayAsCSV($config["plugin"]["pluginJsPaths"]["path"]);
                unset($pluginConfigs[$counter]["plugin"]["pluginJsPaths"]);

                $pluginConfigs[$counter]["plugin"]["cssPathString"] = $this->getStringOrArrayAsCSV($config["plugin"]["pluginCssPaths"]["path"]);
                unset($pluginConfigs[$counter]["plugin"]["pluginCssPaths"]);

                $pluginConfigs[$counter]["plugin"]["isInstalled"] = $className::isInstalled();

                if(empty($pluginConfigs[$counter]["plugin"]["pluginNiceName"])){
                    $pluginConfigs[$counter]["plugin"]["pluginNiceName"] =  $pluginConfigs[$counter]["plugin"]["pluginName"];  
                }

                $pluginConfigs[$counter]["plugin"]["pluginState"] =  $className::getPluginState();

                if(empty($pluginConfigs[$counter]["plugin"]["pluginIcon"])){
                    $pluginConfigs[$counter]["plugin"]["pluginIcon"] =  "/pimcore/static/img/plugin-default.png";  
                }

                if (!empty($pluginConfigs[$counter]["plugin"]["pluginVersion"]) and
                        !empty($pluginConfigs[$counter]["plugin"]["pluginServer"]) and
                                !empty($pluginConfigs[$counter]["plugin"]["pluginRevision"])) {

                    $pluginConfigs[$counter]["plugin"]["isUpdateAvailable"] = true;
                } else {
                    $pluginConfigs[$counter]["plugin"]["isUpdateAvailable"] = false;
                }

                $counter++;
            }
        }

        $configurations = array();
        foreach($pluginConfigs as $p){
            $configurations[] = $p['plugin'];

        }

        echo Zend_Json::encode(array("plugins"=>$configurations));
        $this->removeViewRenderer();
    }


    public function installAction() {
        if ($this->getUser()->isAllowed("plugins")) {
            $className = $this->_getParam("className");
            $plugin = $this->_getParam("name");
            $message = $className::install();


            $success = Pimcore_Update::downloadPluginLanguages($plugin);
            if(!$success){
                $message.="could not download all plugin translations";
            }

            echo Zend_Json::encode(array("message" => $message, "reload" => $className::needsReloadAfterInstall(), "status" => array("installed" => $className::isInstalled())));

        } else {
            if ($this->getUser() != null) {
                Logger::err(get_class($this) . ": user [" . $this->getUser()->getId() . "] attempted to install plugin, but has no permission to do so.");
            } else {
                Logger::err(get_class($this) . ": attempt to install plugin, but no user in session.");
            }
        }

        $this->removeViewRenderer();
    }

    public function uninstallAction() {
        if ($this->getUser()->isAllowed("plugins")) {
            $className = $this->_getParam("className");
            $message = $className
            ::
            uninstall();
            echo Zend_Json::encode(array("message" => $message, "pluginJsClassName" => $className
            ::
            getJsClassName(), "status" => array("installed" => $className
            ::
            isInstalled())))
            ;

        } else {
            if ($this->getUser() != null) {
                Logger::err(get_class($this) . ": user [" . $this->getUser()->getId() . "] attempted to uninstall plugin, but has no permission to do so.");
            } else {
                Logger::err(get_class($this) . ": attempt to uninstall plugin, but no user in session.");
            }
        }

        $this->removeViewRenderer();
    }

}