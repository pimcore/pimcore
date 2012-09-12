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
 
class Extensionmanager_ShareController extends Pimcore_Controller_Action_Admin {

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

        $configurations = array();

        // plugins
        $pluginConfigs = Pimcore_ExtensionManager::getPluginConfigs();

        // get remote repo state of plugins
        $remoteConfig = array();
        foreach ($pluginConfigs as $config) {
            if(!is_file(Pimcore_ExtensionManager::getPathForExtension($config["plugin"]["pluginName"], "plugin") . "/.pimcore_extension_revision")) {
                $remoteConfig["extensions"][] = array(
                    "id" => $config["plugin"]["pluginName"],
                    "type" => "plugin"
                );
            }
        }

        $brickConfigs = Pimcore_ExtensionManager::getBrickConfigs();
        // get repo state of bricks
        foreach ($brickConfigs as $id => $config) {
            if(!is_file(Pimcore_ExtensionManager::getPathForExtension($id, "brick") . "/.pimcore_extension_revision")) {
                $remoteConfig["extensions"][] = array(
                    "id" => $id,
                    "type" => "brick"
                );
            }
        }


        $remoteConfig["token"] = Pimcore_Liveconnect::getToken();
        $rawData = Pimcore_Tool::getHttpData("http://extensions.pimcore.org/share/getExtensions.php", null, array("data" => base64_encode(Pimcore_Tool_Serialize::serialize($remoteConfig))));

        if(!$rawData) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

        $hubInfos = Zend_Json::decode($rawData);

        // create configuration for plugins
        foreach ($pluginConfigs as $config) {
            $plugin = array(
                "id" => $config["plugin"]["pluginName"],
                "type" => "plugin",
                "name" => $config["plugin"]["pluginNiceName"],
                "description" => $config["plugin"]["pluginDescription"],
                "icon" => $config["plugin"]["pluginIcon"],
                "exists" => (bool) $hubInfos["extensions"][$config["plugin"]["pluginName"]]["existing"]
            );

            if($hubInfos["extensions"][$plugin["id"]]["allowed"]) {
                $configurations[] = $plugin;
            }
        }

        // create configuration for bricks
        foreach ($brickConfigs as $id => $config) {
            $brick = array(
                "id" => $id,
                "type" => "brick",
                "name" => $config->name,
                "description" => $config->description,
                "exists" => (bool) $hubInfos["extensions"][$id]["existing"]
            );

            if($hubInfos["extensions"][$id]["allowed"]) {
                $configurations[] = $brick;
            }
        }

        $this->_helper->json(array("extensions" => $configurations));
    }

    public function getUpdateInformationAction () {

        $id = $this->getParam("id");
        $type = $this->getParam("type");
        $excludes = explode("\n", $this->getParam("exclude"));
        if(!$excludes[0]) {
            $excludes = array();
        }

        // always exclude the revision information
        $excludes[] = "/pimcore_extension_revision/";

        $steps = array();
        $actions = array();
        $filesTransferred = array();

        $actions["start"] = array(
            "action" => "start-upload",
            "params" => array(
                "id" => $id,
                "type" => $type
            )
        );

        $extensionDir = Pimcore_ExtensionManager::getPathForExtension($id, $type);
        $extensionDir .= "/"; // add trailing slash
        
        if($type == "plugin") {
            $pathPrefix = PIMCORE_PLUGINS_PATH;
        } else if ($type == "brick") {
            $tmpSplit = explode("areas/".$id."/", $extensionDir);
            $pathPrefix = $tmpSplit[0]."areas";
        }

        $pathPrefix = str_replace(DIRECTORY_SEPARATOR, "/", $pathPrefix);
        $files = rscandir($extensionDir);

        foreach ($files as $file) {
            if(is_file($file)) {

                $file = str_replace(DIRECTORY_SEPARATOR, "/", $file);
                // check for excludes
                try {
                    foreach ($excludes as $regexp) {
                        if (@preg_match($regexp, str_replace($pathPrefix,"",$file), $matches)) {
                            throw new Exception("Not allowed because of the regular expressions.");
                        }
                    }
                } catch (Exception $e) {
                    continue;
                }

                $steps[] = array(
                    "action" => "upload-file",
                    "params" => array(
                        "path" => $file,
                        "pathPrefix" => $pathPrefix,
                        "id" => $id,
                        "type" => $type
                    )
                );

                $filesTransferred[] = str_replace($pathPrefix,"",$file);
            }
        }


        $actions["verify"] = array(
            "action" => "verify-upload",
            "params" => array(
                "id" => $id,
                "type" => $type
            )
        );

        $this->_helper->json(array("steps" => $steps, "files" => $filesTransferred, "actions" => $actions));
    }

    public function startUploadAction () {

        $client = Pimcore_Tool::getHttpClient();
        $client->setParameterPost("data", base64_encode(Pimcore_Tool_Serialize::serialize(array(
            "id" => $this->getParam("id"),
            "type" => $this->getParam("type"),
            "token" => Pimcore_Liveconnect::getToken()
        ))));
        $client->setUri("http://extensions.pimcore.org/share/startUpload.php");

        $response = $client->request(Zend_Http_Client::POST);

        // call share.php inside the extension
        $extensionDir = Pimcore_ExtensionManager::getPathForExtension($this->getParam("id"), $this->getParam("type"));
        $shareScript = $extensionDir . "/share.php";
        if(is_file($shareScript)) {
            include($shareScript);
        }

        $this->_helper->json(array("success" => true));
    }

    public function uploadFileAction () {

        $client = Pimcore_Tool::getHttpClient();
        $client->setParameterPost("data", base64_encode(Pimcore_Tool_Serialize::serialize(array(
            "id" => $this->getParam("id"),
            "type" => $this->getParam("type"),
            "token" => Pimcore_Liveconnect::getToken(),
            "path" => str_replace($this->getParam("pathPrefix"),"",$this->getParam("path")),
            "data" => base64_encode(file_get_contents(str_replace("/",DIRECTORY_SEPARATOR,$this->getParam("path"))))
        ))));
        $client->setUri("http://extensions.pimcore.org/share/uploadFile.php");

        $response = $client->request(Zend_Http_Client::POST);

        $this->_helper->json(array(
            "success" => true,
            "response" => $response->getBody()
        ));
    }

    public function verifyUploadAction () {

        $client = Pimcore_Tool::getHttpClient();
        $client->setParameterPost("data", base64_encode(Pimcore_Tool_Serialize::serialize(array(
            "id" => $this->getParam("id"),
            "type" => $this->getParam("type"),
            "token" => Pimcore_Liveconnect::getToken()
        ))));
        $client->setUri("http://extensions.pimcore.org/share/verifyUpload.php");

        $response = $client->request(Zend_Http_Client::POST);

        $this->_helper->json(array("success" => true));
    }
}