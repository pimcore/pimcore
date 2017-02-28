<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/folder")
 */
class FolderController extends DocumentControllerBase
{
    /**
     * @Route("/get-data-by-id")
     * @param Request $request
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

        $folder = Document\Folder::getById($request->get("id"));
        $folder = clone $folder;

        $folder->idPath = Element\Service::getIdPath($folder);
        $folder->userPermissions = $folder->getUserPermissions();
        $folder->setLocked($folder->isLocked());
        $folder->setParent(null);

        $this->addTranslationsData($folder);
        $this->minimizeProperties($folder);

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $returnValueContainer = new \Pimcore\Model\Tool\Admin\EventDataContainer(object2array($folder));
        \Pimcore::getEventManager()->trigger("admin.document.get.preSendData", $this, [
            "document" => $folder,
            "returnValueContainer" => $returnValueContainer
        ]);

        if ($folder->isAllowed("view")) {
            return $this->json($returnValueContainer->getData());
        }

        return $this->json(false);
    }

    /**
     * @Route("/save")
     * @param Request $request
     */
    public function saveAction(Request $request)
    {
        try {
            if ($request->get("id")) {
                $folder = Document\Folder::getById($request->get("id"));
                $folder->setModificationDate(time());
                $folder->setUserModification($this->getUser()->getId());

                if ($folder->isAllowed("publish")) {
                    $this->setValuesToDocument($request, $folder);
                    $folder->save();

                    return $this->json(["success" => true]);
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
     * @param Request $request
     * @param Document $folder
     */
    protected function setValuesToDocument(Request $request, Document $folder)
    {
        $this->addPropertiesToDocument($request, $folder);
    }
}
