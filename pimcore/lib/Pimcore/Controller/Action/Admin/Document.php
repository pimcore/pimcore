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

abstract class Pimcore_Controller_Action_Admin_Document extends Pimcore_Controller_Action_Admin {

    public function init() {
        parent::init();

        // check permissions
        $notRestrictedActions = array();
        if (!in_array($this->_getParam("action"), $notRestrictedActions)) {
            if (!$this->getUser()->isAllowed("documents")) {

                $this->_redirect("/admin/login");
                die();
            }
        }
    }

    protected function addPropertiesToDocument(Document $document) {

        // properties
        if ($this->_getParam("properties")) {

            $properties = array();
            // assign inherited properties
            foreach ($document->getProperties() as $p) {
                if ($p->isInherited()) {
                    $properties[$p->getName()] = $p;
                }
            }

            $propertiesData = Zend_Json::decode($this->_getParam("properties"));

            if (is_array($propertiesData)) {
                foreach ($propertiesData as $propertyName => $propertyData) {

                    $value = $propertyData["data"];

                    try {
                        $property = new Property();
                        $property->setType($propertyData["type"]);
                        $property->setName($propertyName);
                        $property->setCtype("document");
                        $property->setDataFromEditmode($value);
                        $property->setInheritable($propertyData["inheritable"]);

                        $properties[$propertyName] = $property;
                    }
                    catch (Exception $e) {
                        logger::warning("Can't add " . $propertyName . " to document " . $document->getFullPath());
                    }

                }
            }
            if ($document->isAllowed("properties")) {
                $document->setProperties($properties);
            }
        }

        // force loading of properties
        $document->getProperties();
    }
    
    protected function addSchedulerToDocument(Document $document) {

        // scheduled tasks
        if ($this->_getParam("scheduler")) {
            $tasks = array();
            $tasksData = Zend_Json::decode($this->_getParam("scheduler"));

            if (!empty($tasksData)) {
                foreach ($tasksData as $taskData) {
                    $taskData["date"] = strtotime($taskData["date"] . " " . $taskData["time"]);

                    $task = new Schedule_Task($taskData);
                    $tasks[] = $task;
                }
            }

            if ($document->isAllowed("settings")) {
                $document->setScheduledTasks($tasks);
            }
        }
    }

    protected function addSettingsToDocument(Document $document) {

        // settings
        if ($this->_getParam("settings")) {
            if ($document->isAllowed("settings")) {
                $settings = Zend_Json::decode($this->_getParam("settings"));
                $document->setValues($settings);
            }
        }
    }

    protected function addDataToDocument(Document $document) {

        // data
        if ($this->_getParam("data")) {
            $data = Zend_Json::decode($this->_getParam("data"));
            foreach ($data as $name => $value) {
                $data = $value["data"];
                $type = $value["type"];
                $document->setRawElement($name, $type, $data);
            }
        }
    }

    public function saveToSessionAction() {

        if ($this->_getParam("id")) {

            $key = "document_" . $this->_getParam("id");
            $session = new Zend_Session_Namespace("pimcore_documents");

            if (!$document = $session->$key) {
                $document = Document::getById($this->_getParam("id"));
                $document->getPermissionsForUser($this->getUser());
            }

            // set _fulldump otherwise the properties will be removed because of the session-serialize 
            $document->_fulldump = true;
            $this->setValuesToDocument($document);

            $session->$key = $document;
        }

        $this->removeViewRenderer();
    }

    public function translateAction () {

        $conf = Pimcore_Config::getSystemConfig();
        $key  = $conf->services->translate->apikey;
        $locale = new Zend_Locale($this->_getParam("language"));
        $language = $locale->getLanguage();

        $supportedTypes = array("input","textarea","wysiwyg");
        $data = Zend_Json::decode($this->_getParam("data"));

        foreach ($data as &$d) {
            if(in_array($d["type"],$supportedTypes)) {

                $response = Pimcore_Tool::getHttpData("https://www.googleapis.com/language/translate/v2?key=" . $key . "&q=" . urlencode($d["data"]) . "&target=" . $language);

                $tData = Zend_Json::decode($response);
                if($tData["data"]["translations"][0]["translatedText"]) {
                    $d["data"] = $tData["data"]["translations"][0]["translatedText"];
                }

            }
        }

        $this->getRequest()->setParam("data", Zend_Json::encode($data));

        $this->saveToSessionAction();
    }

    public function removeFromSessionAction() {
        $key = "document_" . $this->_getParam("id");
        $session = new Zend_Session_Namespace("pimcore_documents");

        if ($document = $session->$key) {
            $session->$key = null;
        }

        $this->removeViewRenderer();
    }

    protected function minimizeProperties($document) {
        $properties = Element_Service::minimizePropertiesForEditmode($document->getProperties());
        $document->setProperties($properties);
    }
    
    protected function getLatestVersion (Document $document) {
        
        $latestVersion = $document->getLatestVersion();
        if($latestVersion) {
            $latestDoc = $latestVersion->loadData();
            if($latestDoc instanceof Document) {
                $latestDoc->setModificationDate($document->getModificationDate()); // set de modification-date from published version to compare it in js-frontend
                return $latestDoc;
            }
        }
        return $document;
    }

}
