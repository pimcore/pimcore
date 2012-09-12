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
 
class Extensionmanager_DownloadController extends Pimcore_Controller_Action_Admin {

    public function init () {
        parent::init();

        if (!$this->getUser()->isAllowed("plugins")) {
            if ($this->getUser() != null) {
                Logger::err("user [" . $this->getUser()->getId() . "] attempted to install plugin, but has no permission to do so.");
            } else {
                Logger::err("attempt to install plugin, but no user in session.");
            }
        }
    }


    public function getExtensionsAction () {

        // plugins
        $pluginConfigs = Pimcore_ExtensionManager::getPluginConfigs();

        // get remote repo state of plugins
        $remoteConfig = array();
        foreach ($pluginConfigs as $config) {
            $remoteConfig["extensions"][] = array(
                "id" => $config["plugin"]["pluginName"],
                "type" => "plugin"
            );
        }

        $brickConfigs = Pimcore_ExtensionManager::getBrickConfigs();
        // get repo state of bricks
        foreach ($brickConfigs as $id => $config) {
            $remoteConfig["extensions"][] = array(
                "id" => $id,
                "type" => "brick"
            );
        }


        $remoteConfig["token"] = Pimcore_Liveconnect::getToken();
        $rawData = Pimcore_Tool::getHttpData("http://extensions.pimcore.org/download/getExtensions.php", null, array("data" => base64_encode(Pimcore_Tool_Serialize::serialize($remoteConfig))));

        if(!$rawData) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

        echo $rawData;
        exit;
    }

    public function getDownloadInformationAction () {

        $id = $this->getParam("id");
        $type = $this->getParam("type");

        $remoteConfig = array(
            "token" => Pimcore_Liveconnect::getToken(),
            "id" => $id,
            "type" => $type
        );

        $rawData = Pimcore_Tool::getHttpData("http://extensions.pimcore.org/download/getDownloadInformation.php", null, array("data" => base64_encode(Pimcore_Tool_Serialize::serialize($remoteConfig))));

        if(!$rawData) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

        $steps[] = array(
            "action" => "empty-extension-dir",
            "params" => array(
                "id" => $id,
                "type" => $type
            )
        );

        $data = Zend_Json::decode($rawData);
        foreach ($data["files"] as $file) {
            $steps[] = array(
                "action" => "download-file",
                "params" => array(
                    "id" => $id,
                    "type" => $type,
                    "path" => $file["path"],
                    "revision" => $file["revision"]
                )
            );
        }

        $this->_helper->json(array("steps" => $steps));
    }

    public function downloadFileAction () {
        $id = $this->getParam("id");
        $type = $this->getParam("type");
        $path = $this->getParam("path");
        $revision = $this->getParam("revision");

        $remoteConfig = $this->getAllParams();
        $remoteConfig["token"] = Pimcore_Liveconnect::getToken();
        $rawData = Pimcore_Tool::getHttpData("http://extensions.pimcore.org/download/downloadFile.php?data=" . base64_encode(Pimcore_Tool_Serialize::serialize($remoteConfig)));

        if(!$rawData) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

        $file = Zend_Json::decode($rawData);

        if($type == "plugin") {
            $parentPath = PIMCORE_PLUGINS_PATH;
        } else if ($type == "brick") {
            $parentPath = PIMCORE_WEBSITE_PATH . "/var/areas";
        }

        if(!is_dir($parentPath)) {
            mkdir($parentPath, 0755, true);
        }

        $fileDestPath = $parentPath . $path;
        if(!is_dir(dirname($fileDestPath))) {
            mkdir(dirname($fileDestPath), 0755, true);
        }

        file_put_contents($fileDestPath, base64_decode($file["content"]));
        chmod($fileDestPath, 0766);

        // write revision information
        $revisionFile = $parentPath . "/" . $id .  "/.pimcore_extension_revision";
        file_put_contents($revisionFile, $revision);
        chmod($revisionFile, 0766);


        $this->_helper->json(array("success" => true));
    }

    public function deleteAction () {
        $id = $this->getParam("id");
        $type = $this->getParam("type");
        $path = $this->getParam("path");
        $revision = $this->getParam("revision");


        if($type == "plugin") {
            $parentPath = PIMCORE_PLUGINS_PATH;
        } else if ($type == "brick") {
            $parentPath = PIMCORE_WEBSITE_PATH . "/var/areas";
        }

        if(!is_dir($parentPath)) {
            mkdir($parentPath, 0755, true);
        }

        $fileDestPath = $parentPath . $path;
        if(!is_dir(dirname($fileDestPath))) {
            mkdir(dirname($fileDestPath), 0755, true);
        }

        @unlink($fileDestPath);

        // write revision information
        $revisionFile = $parentPath . "/" . $id .  "/.pimcore_extension_revision";
        file_put_contents($revisionFile, $revision);
        chmod($revisionFile, 0766);

        $this->_helper->json(array("success" => true));
    }

    public function emptyExtensionDirAction () {
        $id = $this->getParam("id");
        $type = $this->getParam("type");

        if($type == "plugin") {
            $extensionPath = PIMCORE_PLUGINS_PATH . "/" . $id;
        } else if ($type = "brick") {
            $extensionPath = PIMCORE_WEBSITE_PATH . "/var/areas/" . $id;
        }

        if(is_dir($extensionPath)) {
            recursiveDelete($extensionPath,true);
        }


        $this->_helper->json(array("success" => true));
    }
}