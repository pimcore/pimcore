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

use Pimcore\File;
use Pimcore\Tool;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Redirect;

class Admin_PageController extends \Pimcore\Controller\Action\Admin\Document {

    public function getDataByIdAction() {

        // check for lock
        if (Element\Editlock::isLocked($this->getParam("id"), "document")) {
            $this->_helper->json(array(
                "editlock" => Element\Editlock::getByElement($this->getParam("id"), "document")
            ));
        }
        Element\Editlock::lock($this->getParam("id"), "document");

        $page = Document\Page::getById($this->getParam("id"));
        $page = $this->getLatestVersion($page);

        $page->setVersions(array_splice($page->getVersions(), 0, 1));
        $page->getScheduledTasks();
        $page->idPath = Element\Service::getIdPath($page);
        $page->userPermissions = $page->getUserPermissions();
        $page->setLocked($page->isLocked());
        $page->setParent(null);

        if($page->getContentMasterDocument()) {
            $page->contentMasterDocumentPath = $page->getContentMasterDocument()->getRealFullPath();
        }

        // get depending redirects
        $redirectList = new Redirect\Listing();
        $redirectList->setCondition("target = ?", $page->getId());
        $page->redirects = $redirectList->load();

        // unset useless data
        $page->setElements(null);
        $page->childs = null;

        // cleanup properties
        $this->minimizeProperties($page);

        if ($page->isAllowed("view")) {
            $this->_helper->json($page);
        }

        $this->_helper->json(false);
    }

    public function saveAction() {

        if ($this->getParam("id")) {
            $page = Document\Page::getById($this->getParam("id"));

            $page = $this->getLatestVersion($page);
            $page->setUserModification($this->getUser()->getId());

            if ($this->getParam("task") == "unpublish") {
                $page->setPublished(false);
            }
            if ($this->getParam("task") == "publish") {
                $page->setPublished(true);
            }

            $settings = array();
            if($this->getParam("settings")) {
                $settings = \Zend_Json::decode($this->getParam("settings"));
            }

            // check for redirects
            if($this->getUser()->isAllowed("redirects") && $this->getParam("settings")) {
                if(is_array($settings)) {
                    $redirectList = new Redirect\Listing();
                    $redirectList->setCondition("target = ?", $page->getId());
                    $existingRedirects = $redirectList->load();
                    $existingRedirectIds = array();
                    foreach ($existingRedirects as $existingRedirect) {
                        $existingRedirectIds[$existingRedirect->getId()] = $existingRedirect->getId();
                    }

                    for($i=1;$i<100;$i++) {
                        if(array_key_exists("redirect_url_".$i, $settings)) {

                            // check for existing
                            if($settings["redirect_id_".$i]) {
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
            if($this->getParam("settings") && is_array($settings)) {
                $metaData = array();
                for($i=1; $i<30; $i++) {
                    if(array_key_exists("metadata_idName_" . $i, $settings)) {
                        $metaData[] = array(
                            "idName" => $settings["metadata_idName_" . $i],
                            "idValue" => $settings["metadata_idValue_" . $i],
                            "contentName" => $settings["metadata_contentName_" . $i],
                            "contentValue" => $settings["metadata_contentValue_" . $i],
                        );
                    }
                }
                $page->setMetaData($metaData);
            }

            // only save when publish or unpublish
            if (($this->getParam("task") == "publish" && $page->isAllowed("publish")) or ($this->getParam("task") == "unpublish" && $page->isAllowed("unpublish"))) {
                $this->setValuesToDocument($page);


                try{
                    $page->save();
                    $this->saveToSession($page);
                    $this->_helper->json(array("success" => true));
                } catch (\Exception $e) {
                    \Logger::err($e);
                    $this->_helper->json(array("success" => false,"message"=>$e->getMessage()));
                }

            }
            else {
                if ($page->isAllowed("save")) {
                    $this->setValuesToDocument($page);

                    try{
                        $page->saveVersion();
                        $this->saveToSession($page);
                        $this->_helper->json(array("success" => true));
                    } catch (\Exception $e) {
                        \Logger::err($e);
                        $this->_helper->json(array("success" => false,"message"=>$e->getMessage()));
                    }

                }
            }
        }
        $this->_helper->json(false);
    }

    public function mobilePreviewAction() {

        $page = Document::getById($this->getParam("id"));

        if($page instanceof Document\Page) {
            $this->view->previewUrl = $page->getFullPath() . "?pimcore_preview=true&time=" . time();
        }
    }

    public function getListAction() {
        $list = new Document\Listing();
        $list->setCondition("type = ?", array("page"));
        $data = $list->loadIdPathList();

        $this->_helper->json(array(
            "success" => true,
            "data" => $data
        ));
    }

    public function uploadScreenshotAction() {
        if($this->getParam("data") && $this->getParam("id")) {
            $data = substr($this->getParam("data"),strpos($this->getParam("data"), ",")+1);
            $data = base64_decode($data);

            $file = PIMCORE_TEMPORARY_DIRECTORY . "/document-page-previews/document-page-screenshot-" . $this->getParam("id") . ".jpg";
            $dir = dirname($file);
            if(!is_dir($dir)) {
                File::mkdir($dir);
            }

            File::put($file, $data);
        }

        $this->_helper->json(array("success" => true));
    }

    public function generateScreenshotAction() {

        $success = false;
        if($this->getParam("id")) {

            $doc = Document::getById($this->getParam("id"));
            $url = Tool::getHostUrl() . $doc->getRealFullPath() . "?pimcore_preview=true";

            $config = \Pimcore\Config::getSystemConfig();
            if ($config->general->http_auth) {
                $username = $config->general->http_auth->username;
                $password = $config->general->http_auth->password;
                if($username && $password) {
                    $url = str_replace("://", "://" . $username .":". $password . "@", $url);
                }
            }

            $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/screenshot_tmp_" . $doc->getId() . ".png";
            $file = PIMCORE_TEMPORARY_DIRECTORY . "/document-page-previews/document-page-screenshot-" . $doc->getId() . ".jpg";

            $dir = dirname($file);
            if(!is_dir($dir)) {
                File::mkdir($dir);
            }

            try {
                if(\Pimcore\Image\HtmlToImage::convert($url, $tmpFile)) {
                    $im = \Pimcore\Image::getInstance();
                    $im->load($tmpFile);
                    $im->scaleByWidth(400);
                    $im->save($file, "jpeg", 85);

                    unlink($tmpFile);

                    $success = true;
                }
            } catch (\Exception $e) {
                \Logger::error($e);
            }
        }

        $this->_helper->json(array("success" => $success));
    }

    public function checkPrettyUrlAction() {
        $docId = $this->getParam("id");
        $path = trim($this->getParam("path"));
        $path = rtrim($path, "/");

        $success = true;

        // must start with /
        if(strpos($path, "/") !== 0) {
            $success = false;
        }

        if(strlen($path) < 2) {
            $success = false;
        }

        if(!Tool::isValidPath($path)) {
            $success = false;
        }

        $list = new Document\Listing();
        $list->setCondition("(CONCAT(path, `key`) = ? OR id IN (SELECT id from documents_page WHERE prettyUrl = ?))
            AND id != ?", array(
            $path, $path, $docId
        ));

        if($list->getTotalCount() > 0) {
            $success = false;
        }

        $this->_helper->json(array(
            "success" => $success
        ));
    }

    public function clearEditableDataAction() {
        $personaId = $this->getParam("persona");
        $docId = $this->getParam("id");

        $doc = Document::getById($docId);

        foreach($doc->getElements() as $element) {
            if($personaId && $doc instanceof Document\Page) {
                if(preg_match("/^" . preg_quote($doc->getPersonaElementPrefix($personaId), "/") . "/", $element->getName())) {
                    $doc->removeElement($element->getName());
                }
            } else {
                // remove all but persona data
                if(!preg_match("/^persona_\-/", $element->getName())) {
                    $doc->removeElement($element->getName());
                }
            }
        }

        $this->saveToSession($doc);

        $this->_helper->json(array(
            "success" => true
        ));
    }

    protected function setValuesToDocument(Document $page) {

        $this->addSettingsToDocument($page);
        $this->addDataToDocument($page);
        $this->addPropertiesToDocument($page);
        $this->addSchedulerToDocument($page);
    }

}
