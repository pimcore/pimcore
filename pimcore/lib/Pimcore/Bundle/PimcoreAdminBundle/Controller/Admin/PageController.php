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

use Pimcore\Event\AdminEvents;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Redirect;
use Pimcore\Tool;
use Pimcore\Tool\Session;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/page")
 */
class PageController extends DocumentControllerBase
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

        $page = Document\Page::getById($request->get("id"));
        $page = clone $page;
        $page = $this->getLatestVersion($page);

        $pageVersions = Element\Service::getSafeVersionInfo($page->getVersions());
        $page->setVersions(array_splice($pageVersions, 0, 1));
        $page->getScheduledTasks();
        $page->idPath = Element\Service::getIdPath($page);
        $page->userPermissions = $page->getUserPermissions();
        $page->setLocked($page->isLocked());
        $page->setParent(null);

        if ($page->getContentMasterDocument()) {
            $page->contentMasterDocumentPath = $page->getContentMasterDocument()->getRealFullPath();
        }

        // get depending redirects
        $redirectList = new Redirect\Listing();
        $redirectList->setCondition("target = ?", $page->getId());
        $page->redirects = $redirectList->load();

        // unset useless data
        $page->setElements(null);
        $page->childs = null;

        $this->addTranslationsData($page);
        $this->minimizeProperties($page);

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $data = object2array($page);
        $event = new GenericEvent($this, [
            "data" => $data,
            "document" => $page
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_GET_PRE_SEND_DATA, $event);
        $data = $event->getArgument("data");

        if ($page->isAllowed("view")) {
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
                $page = Document\Page::getById($request->get("id"));

                // check if there's a document in session which should be used as data-source
                // see also self::clearEditableDataAction() | this is necessary to reset all fields and to get rid of
                // outdated and unused data elements in this document (eg. entries of area-blocks)
                $pageSession = Session::useSession(function (AttributeBagInterface $session) use ($page) {
                    $documentKey   = "document_" . $page->getId();
                    $useForSaveKey = "document_" . $page->getId() . "_useForSave";

                    if ($session->has($documentKey) && $session->has($useForSaveKey)) {
                        if ($session->get($useForSaveKey)) {
                            // only use the page from the session once
                            $session->remove($useForSaveKey);

                            return $session->get($documentKey);
                        }
                    }

                    return null;
                }, "pimcore_documents");

                if ($pageSession) {
                    $page = $pageSession;
                } else {
                    $page = $this->getLatestVersion($page);
                }

                $page->setUserModification($this->getUser()->getId());

                if ($request->get("task") == "unpublish") {
                    $page->setPublished(false);
                }
                if ($request->get("task") == "publish") {
                    $page->setPublished(true);
                }

                $settings = [];
                if ($request->get("settings")) {
                    $settings = $this->decodeJson($request->get("settings"));
                }

                // check for redirects
                if ($this->getUser()->isAllowed("redirects") && $request->get("settings")) {
                    if (is_array($settings)) {
                        $redirectList = new Redirect\Listing();
                        $redirectList->setCondition("target = ?", $page->getId());
                        $existingRedirects = $redirectList->load();
                        $existingRedirectIds = [];
                        foreach ($existingRedirects as $existingRedirect) {
                            $existingRedirectIds[$existingRedirect->getId()] = $existingRedirect->getId();
                        }

                        for ($i=1; $i<100; $i++) {
                            if (array_key_exists("redirect_url_".$i, $settings)) {

                                // check for existing
                                if ($settings["redirect_id_".$i]) {
                                    $redirect = Redirect::getById($settings["redirect_id_".$i]);
                                    unset($existingRedirectIds[$redirect->getId()]);
                                } else {
                                    // create new one
                                    $redirect = new Redirect();
                                }

                                $redirect->setSource($settings["redirect_url_".$i]);
                                $redirect->setTarget($page->getId());
                                $redirect->setStatusCode(301);
                                $redirect->save();
                            }
                        }

                        // remove existing redirects which were delete
                        foreach ($existingRedirectIds as $existingRedirectId) {
                            $redirect = Redirect::getById($existingRedirectId);
                            $redirect->delete();
                        }
                    }
                }

                // check if settings exist, before saving meta data
                if ($request->get("settings") && is_array($settings)) {
                    $metaData = [];
                    for ($i=1; $i<30; $i++) {
                        if (array_key_exists("metadata_" . $i, $settings)) {
                            $metaData[] = $settings["metadata_" . $i];
                        }
                    }
                    $page->setMetaData($metaData);
                }

                // only save when publish or unpublish
                if (($request->get("task") == "publish" && $page->isAllowed("publish")) or ($request->get("task") == "unpublish" && $page->isAllowed("unpublish"))) {
                    $this->setValuesToDocument($request, $page);


                    try {
                        $page->save();
                        $this->saveToSession($page);

                        return $this->json(["success" => true]);
                    } catch (\Exception $e) {
                        if ($e instanceof Element\ValidationException) {
                            throw $e;
                        }
                        Logger::err($e);

                        return $this->json(["success" => false, "message"=>$e->getMessage()]);
                    }
                } else {
                    if ($page->isAllowed("save")) {
                        $this->setValuesToDocument($request, $page);

                        try {
                            $page->saveVersion();
                            $this->saveToSession($page);

                            return $this->json(["success" => true]);
                        } catch (\Exception $e) {
                            Logger::err($e);

                            return $this->json(["success" => false, "message"=>$e->getMessage()]);
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
     * @Route("/get-list")
     * @param Request $request
     * @return JsonResponse
     */
    public function getListAction(Request $request)
    {
        $list = new Document\Listing();
        $list->setCondition("type = ?", ["page"]);
        $data = $list->loadIdPathList();

        return $this->json([
            "success" => true,
            "data" => $data
        ]);
    }

    /**
     * @Route("/upload-screenshot")
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadScreenshotAction(Request $request)
    {
        if ($request->get("data") && $request->get("id")) {
            $data = substr($request->get("data"), strpos($request->get("data"), ",")+1);
            $data = base64_decode($data);

            $file = PIMCORE_TEMPORARY_DIRECTORY . "/document-page-previews/document-page-screenshot-" . $request->get("id") . ".jpg";
            $dir = dirname($file);
            if (!is_dir($dir)) {
                File::mkdir($dir);
            }

            File::put($file, $data);
        }

        return $this->json(["success" => true]);
    }

    /**
     * @Route("/generate-screenshot")
     * @param Request $request
     * @return JsonResponse
     */
    public function generateScreenshotAction(Request $request)
    {
        $success = false;
        if ($request->get("id")) {
            $doc = Document::getById($request->get("id"));
            $url = Tool::getHostUrl() . $doc->getRealFullPath();

            $config = \Pimcore\Config::getSystemConfig();
            if ($config->general->http_auth) {
                $username = $config->general->http_auth->username;
                $password = $config->general->http_auth->password;
                if ($username && $password) {
                    $url = str_replace("://", "://" . $username .":". $password . "@", $url);
                }
            }

            $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/screenshot_tmp_" . $doc->getId() . ".png";
            $file = PIMCORE_TEMPORARY_DIRECTORY . "/document-page-previews/document-page-screenshot-" . $doc->getId() . ".jpg";

            $dir = dirname($file);
            if (!is_dir($dir)) {
                File::mkdir($dir);
            }

            try {
                if (\Pimcore\Image\HtmlToImage::convert($url, $tmpFile)) {
                    $im = \Pimcore\Image::getInstance();
                    $im->load($tmpFile);
                    $im->scaleByWidth(400);
                    $im->save($file, "jpeg", 85);

                    unlink($tmpFile);

                    $success = true;
                }
            } catch (\Exception $e) {
                Logger::error($e);
            }
        }

        return $this->json(["success" => $success]);
    }

    /**
     * @Route("/check-pretty-url")
     * @param Request $request
     * @return JsonResponse
     */
    public function checkPrettyUrlAction(Request $request)
    {
        $docId = $request->get("id");
        $path = trim($request->get("path"));
        $path = rtrim($path, "/");

        $success = true;

        // must start with /
        if (strpos($path, "/") !== 0) {
            $success = false;
        }

        if (strlen($path) < 2) {
            $success = false;
        }

        if (!Tool::isValidPath($path)) {
            $success = false;
        }

        $list = new Document\Listing();
        $list->setCondition("(CONCAT(path, `key`) = ? OR id IN (SELECT id from documents_page WHERE prettyUrl = ?))
            AND id != ?", [
            $path, $path, $docId
        ]);

        if ($list->getTotalCount() > 0) {
            $success = false;
        }

        return $this->json([
            "success" => $success
        ]);
    }

    /**
     * @Route("/clear-editable-data")
     * @param Request $request
     * @return JsonResponse
     */
    public function clearEditableDataAction(Request $request)
    {
        $personaId = $request->get("persona");
        $docId = $request->get("id");

        $doc = Document::getById($docId);

        foreach ($doc->getElements() as $element) {
            if ($personaId && $doc instanceof Document\Page) {
                if (preg_match("/^" . preg_quote($doc->getPersonaElementPrefix($personaId), "/") . "/", $element->getName())) {
                    $doc->removeElement($element->getName());
                }
            } else {
                // remove all but persona data
                if (!preg_match("/^persona_\-/", $element->getName())) {
                    $doc->removeElement($element->getName());
                }
            }
        }

        $this->saveToSession($doc, true);

        return $this->json([
            "success" => true
        ]);
    }

    /**
     * @param Request $request
     * @param Document $page
     */
    protected function setValuesToDocument(Request $request, Document $page)
    {
        $this->addSettingsToDocument($request, $page);
        $this->addDataToDocument($request, $page);
        $this->addPropertiesToDocument($request, $page);
        $this->addSchedulerToDocument($request, $page);
    }
}
