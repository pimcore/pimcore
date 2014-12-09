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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Controller\Action\Admin;

use Pimcore\Controller\Action\Admin;
use Pimcore\Tool;
use Pimcore\Tool\Session;
use Pimcore\Config;
use Pimcore\Model;
use Pimcore\Model\Element;
use Pimcore\Model\Property;
use Pimcore\Model\Schedule;

abstract class Document extends Admin {

    /**
     * @throws \Exception
     */
    public function init() {
        parent::init();

        // check permissions
        $notRestrictedActions = array();
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("documents");
        }
    }

    /**
     * @param Model\Document $document
     * @throws \Zend_Json_Exception
     */
    protected function addPropertiesToDocument(Model\Document $document) {

        // properties
        if ($this->getParam("properties")) {

            $properties = array();
            // assign inherited properties
            foreach ($document->getProperties() as $p) {
                if ($p->isInherited()) {
                    $properties[$p->getName()] = $p;
                }
            }

            $propertiesData = \Zend_Json::decode($this->getParam("properties"));

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
                    catch (\Exception $e) {
                        \Logger::warning("Can't add " . $propertyName . " to document " . $document->getFullPath());
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

    /**
     * @param Model\Document $document
     * @throws \Zend_Json_Exception
     */
    protected function addSchedulerToDocument(Model\Document $document) {

        // scheduled tasks
        if ($this->getParam("scheduler")) {
            $tasks = array();
            $tasksData = \Zend_Json::decode($this->getParam("scheduler"));

            if (!empty($tasksData)) {
                foreach ($tasksData as $taskData) {
                    $taskData["date"] = strtotime($taskData["date"] . " " . $taskData["time"]);

                    $task = new Schedule\Task($taskData);
                    $tasks[] = $task;
                }
            }

            if ($document->isAllowed("settings")) {
                $document->setScheduledTasks($tasks);
            }
        }
    }

    /**
     * @param Model\Document $document
     * @throws \Zend_Json_Exception
     */
    protected function addSettingsToDocument(Model\Document $document) {

        // settings
        if ($this->getParam("settings")) {
            if ($document->isAllowed("settings")) {
                $settings = \Zend_Json::decode($this->getParam("settings"));
                $document->setValues($settings);
            }
        }
    }

    /**
     * @param Model\Document $document
     * @throws \Zend_Json_Exception
     */
    protected function addDataToDocument(Model\Document $document) {

        // data
        if ($this->getParam("data")) {
            $data = \Zend_Json::decode($this->getParam("data"));
            foreach ($data as $name => $value) {
                $data = $value["data"];
                $type = $value["type"];
                $document->setRawElement($name, $type, $data);
            }
        }
    }

    /**
     *
     */
    public function saveToSessionAction() {

        if ($this->getParam("id")) {

            $key = "document_" . $this->getParam("id");

            $session = Session::get("pimcore_documents");

            if (!$document = $session->$key) {
                $document = Model\Document::getById($this->getParam("id"));
                $document = $this->getLatestVersion($document);
            }

            // set _fulldump otherwise the properties will be removed because of the session-serialize 
            $document->_fulldump = true;
            $this->setValuesToDocument($document);

            $session->$key = $document;

            Session::writeClose();
        }

        $this->_helper->json(array("success" => true));
    }

    /**
     * @param $doc
     */
    protected function saveToSession($doc) {
        // save to session
        Session::useSession(function ($session) use ($doc) {
            $key = "document_" . $doc->getId();
            $session->$key = $doc;
        }, "pimcore_documents");
    }

    /**
     * @throws \Zend_Json_Exception
     */
    public function translateAction () {

        $conf = Config::getSystemConfig();
        $key  = $conf->services->translate->apikey;
        $locale = new \Zend_Locale($this->getParam("language"));
        $language = $locale->getLanguage();

        $supportedTypes = array("input","textarea","wysiwyg");
        $data = \Zend_Json::decode($this->getParam("data"));

        foreach ($data as &$d) {
            if(in_array($d["type"],$supportedTypes)) {

                $response = Tool::getHttpData("https://www.googleapis.com/language/translate/v2?key=" . $key . "&q=" . urlencode($d["data"]) . "&target=" . $language);

                $tData = \Zend_Json::decode($response);
                if($tData["data"]["translations"][0]["translatedText"]) {
                    $d["data"] = $tData["data"]["translations"][0]["translatedText"];
                }

            }
        }

        $this->getRequest()->setParam("data", \Zend_Json::encode($data));

        $this->saveToSessionAction();
    }

    /**
     *
     */
    public function removeFromSessionAction() {
        $key = "document_" . $this->getParam("id");

        Session::useSession(function ($session) use ($key) {
            $session->$key = null;
        }, "pimcore_documents");

        $this->_helper->json(array("success" => true));
    }

    /**
     * @param $document
     */
    protected function minimizeProperties($document) {
        $properties = Element\Service::minimizePropertiesForEditmode($document->getProperties());
        $document->setProperties($properties);
    }

    /**
     * @param Model\Document $document
     * @return Model\Document
     */
    protected function getLatestVersion (Model\Document $document) {
        
        $latestVersion = $document->getLatestVersion();
        if($latestVersion) {
            $latestDoc = $latestVersion->loadData();
            if($latestDoc instanceof Model\Document) {
                $latestDoc->setModificationDate($document->getModificationDate()); // set de modification-date from published version to compare it in js-frontend
                return $latestDoc;
            }
        }
        return $document;
    }

    /**
     * this is used for pages and snippets to change the master document (which is not saved with the normal save button)
     */
    public function changeMasterDocumentAction() {

        $doc = Model\Document::getById($this->getParam("id"));
        if($doc instanceof Model\Document\PageSnippet) {
            $doc->setElements(array());
            $doc->setContentMasterDocumentId($this->getParam("contentMasterDocumentPath"));
            $doc->saveVersion();
        }

        $this->_helper->json(array("success" => true));
    }
}
