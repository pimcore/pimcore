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

class Update_IndexController extends Pimcore_Controller_Action_Admin {


    public function checkFilePermissionsAction () {
        
        $success = false;
        if(Pimcore_Update::isWriteable()) {
            $success = true;
        }

        $this->_helper->json(array(
            "success" => $success
        ));
    }
    
    public function getAvailableUpdatesAction () {

        $availableUpdates = Pimcore_Update::getAvailableUpdates();
        $this->_helper->json($availableUpdates);
    }
    
    public function getJobsAction () {

        $jobs = Pimcore_Update::getJobs($this->getParam("toRevision"));
        
        $this->_helper->json($jobs);
    }
    
    public function jobParallelAction () {
        if($this->getParam("type") == "download") {
            Pimcore_Update::downloadData($this->getParam("revision"), $this->getParam("url"));
        }
        
        $this->_helper->json(array("success" => true));
    }
    
    public function jobProceduralAction () {
        
        $status = array("success" => true);
        
        if($this->getParam("type") == "files") {
            Pimcore_Update::installData($this->getParam("revision"));
        } else if ($this->getParam("type") == "clearcache") {
            Pimcore_Model_Cache::clearAll();
        } else if ($this->getParam("type") == "preupdate") {
            $status = Pimcore_Update::executeScript($this->getParam("revision"), "preupdate");
        } else if ($this->getParam("type") == "postupdate") {
            $status = Pimcore_Update::executeScript($this->getParam("revision"), "postupdate");
        } else if ($this->getParam("type") == "cleanup") {
            Pimcore_Update::cleanup();
        } else if ($this->getParam("type") == "languages") {
            Pimcore_Update::downloadLanguage();
        }

        $this->_helper->json($status);
    }
    
    
    public function getLanguagesAction() {
        
        $languagesJson = Pimcore_Tool::getHttpData("http://www.pimcore.org/community/translations/pimcore_download");
        
        echo $languagesJson;
        exit;
        
        $languagesData = Zend_Json_Decoder::decode($languagesJson);
        $languages = $languagesData["languages"];
        if (is_array($languages)) {
            for ($i = 0; $i < count($languages); $i++) {
                if (is_file($filesDir = PIMCORE_WEBSITE_PATH."/var/config/texts/" . $languages[$i]['key'] . ".csv")) {
                    $languages[$i]["exists"] = true;
                } else {
                    $languages[$i]["exists"] = false;
                }
            }
        }
        
        $this->_helper->json(array(
            "languages" => $languages
        ));
    }

    public function downloadLanguageAction() {

        $lang = $this->getParam("language");
        $success = Pimcore_Update::downloadLanguage($lang);
        
        $this->_helper->json(array(
            "success" => $success
        ));
    }
}
