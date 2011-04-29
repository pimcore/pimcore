<?php
/**
 * Created by JetBrains PhpStorm.
 * User: brusch
 * Date: 07.04.11
 * Time: 07:51
 * To change this template use File | Settings | File Templates.
 */
 
class Extensionmanager_UpdateController extends Pimcore_Controller_Action_Admin {


    public function getUpdateInformationAction () {

        $id = $this->_getParam("id");
        $type = $this->_getParam("type");

        if($type == "plugin") {
            $extensionPath = PIMCORE_PLUGINS_PATH . "/" . $id;
        } else if ($type = "brick") {
            $extensionPath = PIMCORE_WEBSITE_PATH . "/var/areas/" . $id;
        }

        $remoteConfig = array(
            "token" => Pimcore_Liveconnect::getToken(),
            "id" => $id,
            "type" => $type,
            "revision" => trim(file_get_contents($extensionPath."/.pimcore_extension_revision"))
        );

        $rawData = Pimcore_Tool::getHttpData("http://extensions.pimcore.org/getUpdateInfo.php?data=" . base64_encode(serialize($remoteConfig)));

        if(!$rawData) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }


        $steps = array();
        $numberOfFiles = 0;
        $data = Zend_Json::decode($rawData);
        foreach ($data["revisions"] as $revision) {

            foreach ($revision["files"] as $file) {
                $steps[] = array(
                    "action" => $file["action"],
                    "controller" => "download",
                    "params" => array(
                        "id" => $id,
                        "type" => $type,
                        "path" => $file["path"],
                        "revision" => $file["revision"]
                    )
                );
                $numberOfFiles++;
            }

            $steps[] = array(
                "action" => "check-update-script",
                "controller" => "update",
                "params" => array(
                    "id" => $id,
                    "type" => $type,
                    "revision" => $revision["revision"]
                )
            );
        }



        $this->_helper->json(array("steps" => $steps, "fileAmount" => $numberOfFiles));
    }


    public function checkUpdateScriptAction () {

        $id = $this->_getParam("id");
        $type = $this->_getParam("type");
        $revision = $this->_getParam("revision");

        if($type == "plugin") {
            $extensionPath = PIMCORE_PLUGINS_PATH . "/" . $id;
        } else if ($type = "brick") {
            $extensionPath = PIMCORE_WEBSITE_PATH . "/var/areas/" . $id;
        }

        $updateFile = $extensionPath."/"."update.php";
        if(is_file($updateFile)) {
            ob_start();
            include($updateFile);
            $message = ob_get_clean();

            unlink($updateFile);
        }

        $this->_helper->json(array("success" => true, "message" => $message));
    }
}