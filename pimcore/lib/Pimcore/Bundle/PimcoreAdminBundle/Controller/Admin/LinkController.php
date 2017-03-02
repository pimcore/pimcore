<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Admin;

use Pimcore\Event\AdminEvents;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Pimcore\Logger;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/link")
 */
class LinkController extends DocumentControllerBase
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

        $link = Document\Link::getById($request->get("id"));
        $link = clone $link;

        $link->setObject(null);
        $link->idPath = Element\Service::getIdPath($link);
        $link->userPermissions = $link->getUserPermissions();
        $link->setLocked($link->isLocked());
        $link->setParent(null);

        $this->addTranslationsData($link);
        $this->minimizeProperties($link);

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $data = object2array($link);
        $event = new GenericEvent($this, [
            "data" => $data,
            "document" => $link
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_GET_PRE_SEND_DATA, $event);
        $data = $event->getArgument("data");

        if ($link->isAllowed("view")) {
            return $this->json($data);
        }

        return $this->json(false);
    }

    /**
     * @Route("/save")
     * @param Request $request
     * @return JsonResponse
     */
    public function saveAction(Request $request)
    {
        try {
            if ($request->get("id")) {
                $link = Document\Link::getById($request->get("id"));
                $this->setValuesToDocument($request, $link);

                $link->setModificationDate(time());
                $link->setUserModification($this->getUser()->getId());

                if ($request->get("task") == "unpublish") {
                    $link->setPublished(false);
                }
                if ($request->get("task") == "publish") {
                    $link->setPublished(true);
                }

                // only save when publish or unpublish
                if (($request->get("task") == "publish" && $link->isAllowed("publish")) || ($request->get("task") == "unpublish" && $link->isAllowed("unpublish"))) {
                    $link->save();

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
     * @param Document\Link $link
     */
    protected function setValuesToDocument(Request $request, Document $link)
    {

        // data
        if ($request->get("data")) {
            $data = $this->decodeJson($request->get("data"));

            if (!empty($data["path"])) {
                if ($document = Document::getByPath($data["path"])) {
                    $data["linktype"] = "internal";
                    $data["internalType"] = "document";
                    $data["internal"] = $document->getId();
                } elseif ($asset = Asset::getByPath($data["path"])) {
                    $data["linktype"] = "internal";
                    $data["internalType"] = "asset";
                    $data["internal"] = $asset->getId();
                } else {
                    $data["linktype"] = "direct";
                    $data["direct"] = $data["path"];
                }
            } else {
                // clear content of link
                $data["linktype"] = "internal";
                $data["direct"] = "";
                $data["internalType"] = null;
                $data["internal"] = null;
            }

            unset($data["path"]);

            $link->setValues($data);
        }

        $this->addPropertiesToDocument($request, $link);
    }
}
