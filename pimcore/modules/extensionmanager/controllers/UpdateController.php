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
 
class Extensionmanager_UpdateController extends Pimcore_Controller_Action_Admin {


    public function getUpdateInformationAction () {

        $id = $this->getParam("id");
        $type = $this->getParam("type");

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

        $rawData = Pimcore_Tool::getHttpData("http://extensions.pimcore.org/update/getUpdateInformation.php", null, array("data" => base64_encode(Pimcore_Tool_Serialize::serialize($remoteConfig))));

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

        $id = $this->getParam("id");
        $type = $this->getParam("type");
        $revision = $this->getParam("revision");

        if($type == "plugin") {
            $extensionPath = PIMCORE_PLUGINS_PATH . "/" . $id;
        } else if ($type = "brick") {
            $extensionPath = PIMCORE_WEBSITE_PATH . "/var/areas/" . $id;
        }


        $maxExecutionTime = 900;
        @ini_set("max_execution_time", $maxExecutionTime);
        set_time_limit($maxExecutionTime);

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