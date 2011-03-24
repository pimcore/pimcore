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

class Pluginadmin_UpdateController extends Pimcore_Controller_Action_Admin {


    public function init() {
        parent::init();

        //check if plugins folder exists and is writeable
        if (!is_dir(PIMCORE_PLUGINS_PATH) or !is_writable(PIMCORE_PLUGINS_PATH)) {
            $this->_helper->json(array(
                "error" => "plugin_dir_error"
            ));
        }

    }



    public function getUpdatesAction() {

        $currentRev = $this->_getParam("revision");
        $plugin = $this->_getParam("plugin");
        $updateHost = $this->_getParam("host");

        $url = "http://" . $updateHost . "/pluginliveupdate/getUpdateInfo.php?plugin=" . $plugin . "&revision=" . $currentRev;
        $xmlRaw = Pimcore_Tool::getHttpData($url);
        
        if(!$xmlRaw) {
            die("Invalid response from update-server.");
        }
        
        $xml = simplexml_load_string($xmlRaw, null, LIBXML_NOCDATA);

        $revisions = array();
        $releases = array();

        foreach ($xml->revision as $r) {

            $date = new Zend_Date($r->date);

            if (strlen(strval($r->version)) > 0) {
                $releases[] = array(
                    "id" => strval($r->id),
                    "date" => strval($r->date),
                    "version" => strval($r->version),
                    "text" => strval($r->id) . " - " . $date->get(Zend_Date::DATETIME_MEDIUM)
                );
            }
            else {
                $revisions[] = array(
                    "id" => strval($r->id),
                    "date" => strval($r->date),
                    "text" => strval($r->id) . " - " . $date->get(Zend_Date::DATETIME_MEDIUM)
                );
            }
        }

        // check permissions
        $files = rscandir(PIMCORE_PLUGINS_PATH . "/" . $plugin . "/");

        foreach ($files as $file) {
            if (!is_writable($file)) {
                die("not writeable: " . $file);
            }
        }
        
        $this->_helper->json(array(
            "releases" => $releases,
            "revisions" => $revisions
        ));
    }


    public function downloadAction() {

        $id = $this->_getParam("id");
        $plugin = $this->_getParam("plugin");
        $updateHost = $this->_getParam("host");
        $currentRev = $this->_getParam("revision");


        // get and store new files
        $url = "http://" . $updateHost . "/pluginliveupdate/getFiles.php?plugin=" . $plugin . "&from=" . $currentRev . "&to=" . $id;
        $fileData = Pimcore_Tool::getHttpData($url);
        $h = fopen(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/plugin_" . $plugin . "_update_files_" . $id . ".xml", "w+");
        fwrite($h, $fileData);
        fclose($h);


        // get and store update scripts
        $url = "http://" . $updateHost . "/pluginliveupdate/getUpdateFiles.php?plugin=" . $plugin . "&from=" . $currentRev . "&to=" . $id;
        $fileData = Pimcore_Tool::getHttpData($url);
        $h = fopen(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/plugin_" . $plugin . "_update_scripts_" . $id . ".xml", "w+");
        fwrite($h, $fileData);
        fclose($h);

        die();
    }


    public function updateAction() {

        $id = $this->_getParam("id");
        $plugin = $this->_getParam("plugin");

        //TODO: What if there are no update scripts?
        $updateScripts = simplexml_load_file(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/plugin_" . $plugin . "_update_scripts_" . $id . ".xml", null, LIBXML_NOCDATA);
        // make directory for update scripts
        $updateSteps = array();

        foreach ($updateScripts->files as $files) {

            $revId = intval($files["revision"]);
            $revDir = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/plugins/" . $plugin . "/" . intval($files["revision"]);

            $updateSteps[] = $revId;
            if (!is_dir($revDir)) {
                mkdir($revDir, 0755, true);
            }
            chmod($revDir, 0755);


            foreach ($files as $file) {
                if (!is_dir(dirname($revDir . $file->path))) {
                    mkdir(dirname($revDir . $file->path), 0755, true);
                    chmod(dirname($revDir . $file->path), 0755);
                }
                file_put_contents($revDir . $file->path, base64_decode($file->content));
            }
        }

        // preupdate
        foreach ($updateSteps as $step) {
            $preupdate = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/plugins/" . $plugin . "/" . $step . "/preupdate.php";
            if (is_file($preupdate)) {
                include($preupdate);
            }
        }


        // update files
        $updateFiles = simplexml_load_file(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/plugin_" . $plugin . "_update_files_" . $id . ".xml", null, LIBXML_NOCDATA);

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
            else if ($file->action == "delete") {
                unlink(PIMCORE_PLUGINS_PATH . "/" . $plugin . $file->path);
            }
        }

        // postupdate
        foreach ($updateSteps as $step) {
            $postupdate = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/plugins/" . $plugin . "/" . $step . "/postupdate.php";
            if (is_file($postupdate)) {
                include($postupdate);
            }
        }


        // delete files
        foreach ($updateSteps as $step) {
            recursiveDelete(PIMCORE_SYSTEM_TEMP_DIRECTORY . "/plugins/" . $plugin . "/" . $step, true);
        }

        $success = Pimcore_Update::downloadPluginLanguages($plugin);
       
        $this->_helper->json(array(
            "success" => $success
        ));
    }


}
