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

class Pluginadmin_DownloadController extends Pimcore_Controller_Action_Admin {


    public function init() {
        parent::init();

        //check if plugins folder exists and is writeable
        if (!is_dir(PIMCORE_PLUGINS_PATH) or !is_writable(PIMCORE_PLUGINS_PATH)) {
            echo Zend_Json::encode(array(
                "error" => "plugin_dir_error"
            ));
            die();
        }

    }


    public function getDownloadsAction() {

        if(!is_writable(PIMCORE_PLUGINS_PATH)){
            logger::err("Pluginadmin_DownloadController: plugin directory is not writeable");
            die();
        }

        $conf = Pimcore_Config::getSystemConfig();

        $downloadInfos = array();

        if ($conf->plugins->repositories) {
            $repositories = explode(",", $conf->plugins->repositories);

            foreach ($repositories as $updateHost) {
                $url = "http://" . $updateHost . "/pluginliveupdate/getDownloads.php";
            
                $xmlRaw = Pimcore_Tool::getHttpData($url);
                if($xmlRaw) {
                    $xmldata = simplexml_load_string($xmlRaw, null, LIBXML_NOCDATA);
                }

                if($xmldata!=null && $xmldata!==FALSE){
                    if ($xmldata->plugin[0]->pluginName) {
                        $pluginConfigs = $xmldata;
                    } else {
                        $pluginConfigs[0] = $xmldata->plugin[0] = $xmldata->plugin;
                    }

                    $defaultIcon = "/pimcore/static/img/plugin-default.png";
                    foreach ($pluginConfigs as $config) {
                        $dirname = PIMCORE_PLUGINS_PATH . "/" . $config->pluginName->__toString();
                        $icon = $defaultIcon;
                        if($config->pluginIcon){
                            $icon =  $config->pluginIcon->__toString();
                            if(empty($icon)){
                                $icon = $defaultIcon;
                            }
                        }
                        if (!is_dir($dirname)) {
                            $pluginName =  $config->pluginName->__toString();
                            if(isset($config->pluginNiceName)){
                                $pluginName =  $config->pluginNiceName->__toString();
                            }

                            $downloadInfos[] = array(
                                    "pluginName" => $config->pluginName->__toString(),
                                    "pluginNiceName" => $pluginName,
                                    "pluginDescription" => $config->pluginDescription->__toString(),
                                    "pluginVersion" => $config->pluginVersion->__toString(),
                                    "pluginRevision" => $config->pluginRevision->__toString(),
                                    "pluginServer" => $config->pluginServer->__toString() ,
                                    "pluginIcon" => $icon

                            );
                        }
                    }
                } else {
                    logger::error("Plugin Download - cannot parse XML");
                }

            }
        }

        echo Zend_Json::encode(array("plugins"=>$downloadInfos));
        $this->removeViewRenderer();
    }





    public function newDownloadAction() {

        $success = false;
        $plugin = $this->_getParam("plugin");
        $updateHost = $this->_getParam("host");
        $currentRev = $this->_getParam("revision");


        if (!is_dir(PIMCORE_PLUGINS_PATH . "/" . $plugin) and is_writable(PIMCORE_PLUGINS_PATH) and !empty($plugin) and !empty($updateHost) and !empty($currentRev)) {


            // get and store new files
            $url = "http://" . $updateHost . "/pluginliveupdate/getFiles.php?plugin=" . $plugin . "&from=1&to=" . $currentRev;
            $fileData = Pimcore_Tool::getHttpData($url);
            
            if($fileData) {
                $h = fopen(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/plugin_" . $plugin . "_update_files_" . $currentRev . ".xml", "w+");
                fwrite($h, $fileData);
                fclose($h);
            }


            // update files
            $updateFiles = simplexml_load_file(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/plugin_" . $plugin . "_update_files_" . $currentRev . ".xml", null, LIBXML_NOCDATA);

            mkdir(PIMCORE_PLUGINS_PATH . "/" . $plugin);
            chmod(PIMCORE_PLUGINS_PATH . "/" . $plugin, 0755);

            foreach ($updateFiles->file as $file) {

                if ($file->action == "update" || $file->action == "add") {

                    if (!is_dir(dirname(PIMCORE_PLUGINS_PATH . "/" . $plugin . $file->path))) {

                        mkdir(dirname(PIMCORE_PLUGINS_PATH . "/" . $plugin . $file->path), 0755, true);
                        chmod(dirname(PIMCORE_PLUGINS_PATH . "/" . $plugin . $file->path), 0755);

                    }
                    $h = fopen(PIMCORE_PLUGINS_PATH . "/" . $plugin . $file->path, "w+");
                    fwrite($h, base64_decode($file->content));
                    fclose($h);
                }
                else if ($file->action == "delete" and is_file(PIMCORE_PLUGINS_PATH . "/" . $plugin . $file->path)) {
                    unlink(PIMCORE_PLUGINS_PATH . "/" . $plugin . $file->path);
                }
            }
            $success = true;
            //TODO real success check
        }

        echo Zend_Json::encode(array("success" => $success));
        $this->removeViewRenderer();


    }
}
