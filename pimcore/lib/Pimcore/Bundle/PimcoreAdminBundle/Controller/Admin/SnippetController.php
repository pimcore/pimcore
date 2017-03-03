<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin;

use Pimcore\Event\AdminEvents;
use Pimcore\Model\Element;
use Pimcore\Model\Document;
use Pimcore\Logger;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/snippet")
 */
class SnippetController extends DocumentControllerBase
{

    /**
     * @Route("/get-data-by-id")
     * @param Request $request
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request)
    {
        // check for lock
        if (Element\Editlock::isLocked($request->get("id"), "document")) {
            return $this->json([
                "editlock" => Element\Editlock::getByElement($request->get("id"), "document")
            ]);
        }
        Element\Editlock::lock($request->get("id"), "document");

        $snippet = Document\Snippet::getById($request->get("id"));
        $snippet = clone $snippet;
        $snippet = $this->getLatestVersion($snippet);

        $versions = Element\Service::getSafeVersionInfo($snippet->getVersions());
        $snippet->setVersions(array_splice($versions, 0, 1));
        $snippet->getScheduledTasks();
        $snippet->idPath = Element\Service::getIdPath($snippet);
        $snippet->userPermissions = $snippet->getUserPermissions();
        $snippet->setLocked($snippet->isLocked());
        $snippet->setParent(null);

        if ($snippet->getContentMasterDocument()) {
            $snippet->contentMasterDocumentPath = $snippet->getContentMasterDocument()->getRealFullPath();
        }

        $this->addTranslationsData($snippet);
        $this->minimizeProperties($snippet);

        // unset useless data
        $snippet->setElements(null);

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $data = object2array($snippet);
        $event = new GenericEvent($this, [
            "data" => $data,
            "document" => $snippet
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_GET_PRE_SEND_DATA, $event);
        $data = $event->getArgument("data");

        if ($snippet->isAllowed("view")) {
            return $this->json($data);
        }

        return $this->json(false);
    }

    /**
     * @Route("/save")
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function saveAction(Request $request)
    {
        try {
            if ($request->get("id")) {
                $snippet = Document\Snippet::getById($request->get("id"));
                $snippet = $this->getLatestVersion($snippet);

                $snippet->setUserModification($this->getUser()->getId());

                if ($request->get("task") == "unpublish") {
                    $snippet->setPublished(false);
                }
                if ($request->get("task") == "publish") {
                    $snippet->setPublished(true);
                }


                if (($request->get("task") == "publish" && $snippet->isAllowed("publish")) or ($request->get("task") == "unpublish" && $snippet->isAllowed("unpublish"))) {
                    $this->setValuesToDocument($request, $snippet);

                    try {
                        $snippet->save();
                        $this->saveToSession($snippet);
                        return $this->json(["success" => true]);
                    } catch (\Exception $e) {
                        if ($e instanceof Element\ValidationException) {
                            throw $e;
                        }
                        return $this->json(["success" => false, "message" => $e->getMessage()]);
                    }
                } else {
                    if ($snippet->isAllowed("save")) {
                        $this->setValuesToDocument($request, $snippet);

                        try {
                            $snippet->saveVersion();
                            $this->saveToSession($snippet);
                            return $this->json(["success" => true]);
                        } catch (\Exception $e) {
                            return $this->json(["success" => false, "message" => $e->getMessage()]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::log($e);
            if ($e instanceof Element\ValidationException) {
                return $this->json(["success" => false, "type" => "ValidationException", "message" => $e->getMessage(), "stack" => $e->getTraceAsString(), "code" => $e->getCode()]);
            }
            throw $e;
        }

        return $this->json(false);
    }

    /**
     * @param $request
     * @param Document $snippet
     */
    protected function setValuesToDocument(Request $request, Document $snippet)
    {
        $this->addSettingsToDocument($request, $snippet);
        $this->addDataToDocument($request, $snippet);
        $this->addSchedulerToDocument($request, $snippet);
        $this->addPropertiesToDocument($request, $snippet);
    }
}
