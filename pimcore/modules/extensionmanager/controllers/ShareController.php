<?php
/**
 * Created by JetBrains PhpStorm.
 * User: brusch
 * Date: 07.04.11
 * Time: 07:51
 * To change this template use File | Settings | File Templates.
 */
 
class Extensionmanager_ShareController extends Pimcore_Controller_Action_Admin {

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

        // get repo state of bricks
        // @TODO


        $remoteConfig["token"] = Pimcore_Liveconnect::getToken();
        $rawData = Pimcore_Tool::getHttpData("http://extensions.pimcore.org/getExtensionInfo.php?data=" . base64_encode(serialize($remoteConfig)));

        if(!$rawData) {
            //header('HTTP/1.1 403 Forbidden');
            echo $rawData;
            exit;
        }

        $hubInfos = Zend_Json::decode($rawData);

        // create configuration for plguins
        foreach ($pluginConfigs as $config) {
            $plugin = array(
                "id" => $config["plugin"]["pluginName"],
                "type" => "plugin",
                "name" => $config["plugin"]["pluginNiceName"],
                "description" => $config["plugin"]["pluginDescription"],
                "icon" => $config["plugin"]["pluginIcon"],
                "version" => $config["plugin"]["pluginVersion"],
                "exists" => (bool) $hubInfos["extensions"][$config["plugin"]["pluginName"]]["existing"]
            );

            if($hubInfos["extensions"][$plugin["id"]]["allowed"]) {
                $configurations[] = $plugin;
            }
        }

        // create configuration für bricks
        // @TODO

        $this->_helper->json(array("extensions" => $configurations));
    }

    public function getUpdateInformationAction () {

        $id = $this->_getParam("id");
        $type = $this->_getParam("type");

        $steps = array();

        $steps[] = array(
            "action" => "start-upload",
            "params" => array(
                "id" => $id,
                "type" => $type
            )
        );

        if($type == "plugin") {
            $pluginDir = PIMCORE_PLUGINS_PATH . "/" . $id . "/";
            $files = rscandir($pluginDir);

            foreach ($files as $file) {
                if(is_file($file)) {
                    $steps[] = array(
                        "action" => "upload-file",
                        "params" => array(
                            "path" => str_replace(PIMCORE_PLUGINS_PATH,"",$file),
                            "id" => $id,
                            "type" => $type
                        )
                    );
                }
            }
        }

        $steps[] = array(
            "action" => "verify-upload",
            "params" => array(
                "id" => $id,
                "type" => $type
            )
        );

        $this->_helper->json(array("steps" => $steps));
    }

    public function startUploadAction () {

        $client = Pimcore_Tool::getHttpClient();
        $client->setParameterPost("data", base64_encode(serialize(array(
            "id" => $this->_getParam("id"),
            "type" => $this->_getParam("type"),
            "token" => Pimcore_Liveconnect::getToken()
        ))));
        $client->setUri("http://extensions.pimcore.org/createNewRevision.php");

        $response = $client->request(Zend_Http_Client::POST);

        $this->_helper->json(array("success" => true));
    }

    public function uploadFileAction () {

        $client = Pimcore_Tool::getHttpClient();
        $client->setParameterPost("data", base64_encode(serialize(array(
            "id" => $this->_getParam("id"),
            "type" => $this->_getParam("type"),
            "token" => Pimcore_Liveconnect::getToken(),
            "path" => $this->_getParam("path"),
            "data" => base64_encode(file_get_contents(PIMCORE_PLUGINS_PATH . $this->_getParam("path")))
        ))));
        $client->setUri("http://extensions.pimcore.org/addFile.php");

        $response = $client->request(Zend_Http_Client::POST);

        $this->_helper->json(array("success" => true));
    }

    public function verifyUploadAction () {

        $client = Pimcore_Tool::getHttpClient();
        $client->setParameterPost("data", base64_encode(serialize(array(
            "id" => $this->_getParam("id"),
            "type" => $this->_getParam("type"),
            "token" => Pimcore_Liveconnect::getToken()
        ))));
        $client->setUri("http://extensions.pimcore.org/complete.php");

        $response = $client->request(Zend_Http_Client::POST);

        $this->_helper->json(array("success" => true));
    }
}