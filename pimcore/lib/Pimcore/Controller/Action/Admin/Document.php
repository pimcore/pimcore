<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Controller\Action\Admin;

use Pimcore\Controller\Action\Admin;
use Pimcore\Tool;
use Pimcore\Tool\Session;
use Pimcore\Config;
use Pimcore\Model;
use Pimcore\Model\Property;
use Pimcore\Model\Schedule;
use Pimcore\Logger;

abstract class Document extends Admin
{

    /**
     * @throws \Exception
     */
    public function init()
    {
        parent::init();

        // check permissions
        $notRestrictedActions = [];
        if (!in_array($this->getParam("action"), $notRestrictedActions)) {
            $this->checkPermission("documents");
        }
    }

    /**
     * @param Model\Document $document
     * @throws \Zend_Json_Exception
     */
    protected function addPropertiesToDocument(Model\Document $document)
    {

        // properties
        if ($this->getParam("properties")) {
            $properties = [];
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
                    } catch (\Exception $e) {
                        Logger::warning("Can't add " . $propertyName . " to document " . $document->getRealFullPath());
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
    protected function addSchedulerToDocument(Model\Document $document)
    {

        // scheduled tasks
        if ($this->getParam("scheduler")) {
            $tasks = [];
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
    protected function addSettingsToDocument(Model\Document $document)
    {

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
    protected function addDataToDocument(Model\Document $document)
    {

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
     * @param Model\Document $document
     */
    protected function addTranslationsData(Model\Document $document)
    {
        $service = new Model\Document\Service;
        $translations = $service->getTranslations($document);
        $language = $document->getProperty("language");
        unset($translations[$language]);
        $document->translations = $translations;
    }

    /**
     *
     */
    public function saveToSessionAction()
    {
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

        $this->_helper->json(["success" => true]);
    }

    /**
     * @param $doc
     * @param bool $useForSave
     */
    protected function saveToSession($doc, $useForSave = false)
    {
        // save to session
        Session::useSession(function ($session) use ($doc, $useForSave) {
            $session->{"document_" . $doc->getId()} = $doc;

            if ($useForSave) {
                $session->{"document_" . $doc->getId() . "_useForSave"} = true;
            }
        }, "pimcore_documents");
    }

    /**
     *
     */
    public function removeFromSessionAction()
    {
        $key = "document_" . $this->getParam("id");

        Session::useSession(function ($session) use ($key) {
            $session->$key = null;
        }, "pimcore_documents");

        $this->_helper->json(["success" => true]);
    }

    /**
     * @param $document
     */
    protected function minimizeProperties($document)
    {
        $properties = Model\Element\Service::minimizePropertiesForEditmode($document->getProperties());
        $document->setProperties($properties);
    }

    /**
     * @param Model\Document $document
     * @return Model\Document
     */
    protected function getLatestVersion(Model\Document $document)
    {
        $latestVersion = $document->getLatestVersion();
        if ($latestVersion) {
            $latestDoc = $latestVersion->loadData();
            if ($latestDoc instanceof Model\Document) {
                $latestDoc->setModificationDate($document->getModificationDate()); // set de modification-date from published version to compare it in js-frontend
                return $latestDoc;
            }
        }

        return $document;
    }

    /**
     * this is used for pages and snippets to change the master document (which is not saved with the normal save button)
     */
    public function changeMasterDocumentAction()
    {
        $doc = Model\Document::getById($this->getParam("id"));
        if ($doc instanceof Model\Document\PageSnippet) {
            $doc->setElements([]);
            $doc->setContentMasterDocumentId($this->getParam("contentMasterDocumentPath"));
            $doc->saveVersion();
        }

        $this->_helper->json(["success" => true]);
    }
}
