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

use Pimcore\File;
use Pimcore\Tool;
use Pimcore\Tool\Session;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Redirect;
use Pimcore\Logger;

class Admin_PageController extends \Pimcore\Controller\Action\Admin\Document
{
    public function getDataByIdAction()
    {

        // check for lock
        if (Element\Editlock::isLocked($this->getParam("id"), "document")) {
            $this->_helper->json([
                "editlock" => Element\Editlock::getByElement($this->getParam("id"), "document")
            ]);
        }
        Element\Editlock::lock($this->getParam("id"), "document");

        $page = Document\Page::getById($this->getParam("id"));
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
        $returnValueContainer = new \Pimcore\Model\Tool\Admin\EventDataContainer(object2array($page));
        \Pimcore::getEventManager()->trigger("admin.document.get.preSendData", $this, [
            "document" => $page,
            "returnValueContainer" => $returnValueContainer
        ]);

        if ($page->isAllowed("view")) {
            $this->_helper->json($returnValueContainer->getData());
        }

        $this->_helper->json(false);
    }

    public function saveAction()
    {
        try {
            if ($this->getParam("id")) {
                $page = Document\Page::getById($this->getParam("id"));

                // check if there's a document in session which should be used as data-source
                // see also self::clearEditableDataAction() | this is necessary to reset all fields and to get rid of
                // outdated and unused data elements in this document (eg. entries of area-blocks)
                $pageSession = Session::useSession(function ($session) use ($page) {
                    if (isset($session->{"document_" . $page->getId()}) && isset($session->{"document_" . $page->getId() . "_useForSave"})) {
                        if ($session->{"document_" . $page->getId() . "_useForSave"}) {
                            // only use the page from the session once
                            unset($session->{"document_" . $page->getId() . "_useForSave"});

                            return $session->{"document_" . $page->getId()};
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

                if ($this->getParam("task") == "unpublish") {
                    $page->setPublished(false);
                }
                if ($this->getParam("task") == "publish") {
                    $page->setPublished(true);
                }

                $settings = [];
                if ($this->getParam("settings")) {
                    $settings = \Zend_Json::decode($this->getParam("settings"));
                }

                // check for redirects
                if ($this->getUser()->isAllowed("redirects") && $this->getParam("settings")) {
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
                if ($this->getParam("settings") && is_array($settings)) {
                    $metaData = [];
                    for ($i=1; $i<30; $i++) {
                        if (array_key_exists("metadata_" . $i, $settings)) {
                            $metaData[] = $settings["metadata_" . $i];
                        }
                    }
                    $page->setMetaData($metaData);
                }

                // only save when publish or unpublish
                if (($this->getParam("task") == "publish" && $page->isAllowed("publish")) or ($this->getParam("task") == "unpublish" && $page->isAllowed("unpublish"))) {
                    $this->setValuesToDocument($page);


                    try {
                        $page->save();
                        $this->saveToSession($page);
                        $this->_helper->json(["success" => true]);
                    } catch (\Exception $e) {
                        if (\Pimcore\Tool\Admin::isExtJS6() && $e instanceof Element\ValidationException) {
                            throw $e;
                        }
                        Logger::err($e);
                        $this->_helper->json(["success" => false, "message"=>$e->getMessage()]);
                    }
                } else {
                    if ($page->isAllowed("save")) {
                        $this->setValuesToDocument($page);

                        try {
                            $page->saveVersion();
                            $this->saveToSession($page);
                            $this->_helper->json(["success" => true]);
                        } catch (\Exception $e) {
                            Logger::err($e);
                            $this->_helper->json(["success" => false, "message"=>$e->getMessage()]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::log($e);
            if (\Pimcore\Tool\Admin::isExtJS6() && $e instanceof Element\ValidationException) {
                $this->_helper->json(["success" => false, "type" => "ValidationException", "message" => $e->getMessage(), "stack" => $e->getTraceAsString(), "code" => $e->getCode()]);
            }
            throw $e;
        }

        $this->_helper->json(false);
    }

    public function getListAction()
    {
        $list = new Document\Listing();
        $list->setCondition("type = ?", ["page"]);
        $data = $list->loadIdPathList();

        $this->_helper->json([
            "success" => true,
            "data" => $data
        ]);
    }

    public function uploadScreenshotAction()
    {
        if ($this->getParam("data") && $this->getParam("id")) {
            $data = substr($this->getParam("data"), strpos($this->getParam("data"), ",")+1);
            $data = base64_decode($data);

            $file = PIMCORE_TEMPORARY_DIRECTORY . "/document-page-previews/document-page-screenshot-" . $this->getParam("id") . ".jpg";
            $dir = dirname($file);
            if (!is_dir($dir)) {
                File::mkdir($dir);
            }

            File::put($file, $data);
        }

        $this->_helper->json(["success" => true]);
    }

    public function generateScreenshotAction()
    {
        $success = false;
        if ($this->getParam("id")) {
            $doc = Document::getById($this->getParam("id"));
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

        $this->_helper->json(["success" => $success]);
    }

    public function checkPrettyUrlAction()
    {
        $docId = $this->getParam("id");
        $path = trim($this->getParam("path"));
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

        $this->_helper->json([
            "success" => $success
        ]);
    }

    public function clearEditableDataAction()
    {
        $personaId = $this->getParam("persona");
        $docId = $this->getParam("id");

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

        $this->_helper->json([
            "success" => true
        ]);
    }

    protected function setValuesToDocument(Document $page)
    {
        $this->addSettingsToDocument($page);
        $this->addDataToDocument($page);
        $this->addPropertiesToDocument($page);
        $this->addSchedulerToDocument($page);
    }
}
