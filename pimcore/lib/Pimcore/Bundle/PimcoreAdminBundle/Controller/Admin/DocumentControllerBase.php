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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\AdminController;
use Pimcore\Bundle\PimcoreBundle\Controller\EventedControllerInterface;
use Pimcore\Tool\Session;
use Pimcore\Model;
use Pimcore\Model\Property;
use Pimcore\Model\Schedule;
use Pimcore\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

abstract class DocumentControllerBase extends AdminController implements EventedControllerInterface
{

    /**
     * @param Request $request
     * @param Model\Document $document
     */
    protected function addPropertiesToDocument(Request $request, Model\Document $document)
    {

        // properties
        if ($request->get("properties")) {
            $properties = [];
            // assign inherited properties
            foreach ($document->getProperties() as $p) {
                if ($p->isInherited()) {
                    $properties[$p->getName()] = $p;
                }
            }

            $propertiesData = $this->decodeJson($request->get("properties"));

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
     */
    protected function addSchedulerToDocument(Request $request, Model\Document $document)
    {

        // scheduled tasks
        if ($request->get("scheduler")) {
            $tasks = [];
            $tasksData = $this->decodeJson($request->get("scheduler"));

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
     * @param Request $request
     * @param Model\Document $document
     */
    protected function addSettingsToDocument(Request $request, Model\Document $document)
    {

        // settings
        if ($request->get("settings")) {
            if ($document->isAllowed("settings")) {
                $settings = $this->decodeJson($request->get("settings"));
                $document->setValues($settings);
            }
        }
    }

    /**
     * @param Request $request
     * @param Model\Document $document
     */
    protected function addDataToDocument(Request $request, Model\Document $document)
    {

        // data
        if ($request->get("data")) {
            $data = $this->decodeJson($request->get("data"));
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
     * @Route("/save-to-session")
     * @param Request $request
     * @return JsonResponse
     */
    public function saveToSessionAction(Request $request)
    {
        if ($request->get("id")) {
            $key = "document_" . $request->get("id");

            $session = Session::get("pimcore_documents");

            if (!$document = $session->$key) {
                $document = Model\Document::getById($request->get("id"));
                $document = $this->getLatestVersion($document);
            }

            // set _fulldump otherwise the properties will be removed because of the session-serialize
            $document->_fulldump = true;
            $this->setValuesToDocument($request, $document);

            $session->$key = $document;

            Session::writeClose();
        }

        return $this->json(["success" => true]);
    }

    /**
     * @param Model\Document $doc
     * @param bool $useForSave
     */
    protected function saveToSession($doc, $useForSave = false)
    {
        // save to session
        Session::useSession(function (AttributeBagInterface $session) use ($doc, $useForSave) {
            $session->set("document_" . $doc->getId(), $doc);

            if ($useForSave) {
                $session->set("document_" . $doc->getId() . "_useForSave", true);
            }
        }, "pimcore_documents");
    }

    /**
     * @Route("/remove-from-session")
     * @param Request $request
     * @return JsonResponse
     */
    public function removeFromSessionAction(Request $request)
    {
        $key = "document_" . $request->get("id");

        Session::useSession(function (AttributeBagInterface $session) use ($key) {
            $session->remove($key);
        }, "pimcore_documents");

        return $this->json(["success" => true]);
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
     * This is used for pages and snippets to change the master document (which is not saved with the normal save button)
     * @Route("/change-master-document")
     * @param Request $request
     * @return JsonResponse
     */
    public function changeMasterDocumentAction(Request $request)
    {
        $doc = Model\Document::getById($request->get("id"));
        if ($doc instanceof Model\Document\PageSnippet) {
            $doc->setElements([]);
            $doc->setContentMasterDocumentId($request->get("contentMasterDocumentPath"));
            $doc->saveVersion();
        }

        return $this->json(["success" => true]);
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        $request = $event->getRequest();

        // check permissions
        $notRestrictedActions = [];
        if (!in_array($request->get("action"), $notRestrictedActions)) {
            $this->checkPermission("documents");
        }
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }

    /**
     * @param Request $request
     * @param Model\Document $page
     */
    protected abstract function setValuesToDocument(Request $request, Model\Document $page);
}
