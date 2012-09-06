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

class Admin_PageController extends Pimcore_Controller_Action_Admin_Document {

    public function getDataByIdAction() {

        // check for lock
        if (Element_Editlock::isLocked($this->_getParam("id"), "document")) {
            $this->_helper->json(array(
                "editlock" => Element_Editlock::getByElement($this->_getParam("id"), "document")
            ));
        }
        Element_Editlock::lock($this->_getParam("id"), "document");

        $page = Document_Page::getById($this->_getParam("id"));
        $page = $this->getLatestVersion($page);
        
        $page->getVersions();
        $page->getScheduledTasks();
        $page->idPath = Pimcore_Tool::getIdPathForElement($page);
        $page->userPermissions = $page->getUserPermissions();
        $page->setLocked($page->isLocked());

        if($page->getContentMasterDocument()) {
            $page->contentMasterDocumentPath = $page->getContentMasterDocument()->getRealFullPath();
        }

        // get depending redirects
        $redirectList = new Redirect_List();
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

        if ($this->_getParam("id")) {
            $page = Document_Page::getById($this->_getParam("id"));
            
            $page = $this->getLatestVersion($page);
            $page->setUserModification($this->getUser()->getId());

            // save to session
            $key = "document_" . $this->_getParam("id");
            $session = new Zend_Session_Namespace("pimcore_documents");
            $session->$key = $page;

            if ($this->_getParam("task") == "unpublish") {
                $page->setPublished(false);
            }
            if ($this->_getParam("task") == "publish") {
                $page->setPublished(true);
            }

            // check for redirects
            if($this->getUser()->isAllowed("redirects") && $this->_getParam("settings")) {
                $settings = Zend_Json::decode($this->_getParam("settings"));

                if(is_array($settings)) {
                    $redirectList = new Redirect_List();
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

            // only save when publish or unpublish
            if (($this->_getParam("task") == "publish" && $page->isAllowed("publish")) or ($this->_getParam("task") == "unpublish" && $page->isAllowed("unpublish"))) {
                $this->setValuesToDocument($page);


                try{
                    $page->save();
                    $this->_helper->json(array("success" => true));
                } catch (Exception $e) {
                    Logger::err($e);
                    $this->_helper->json(array("success" => false,"message"=>$e->getMessage()));
                }

            }
            else {
                if ($page->isAllowed("save")) {
                    $this->setValuesToDocument($page);
                    

                    try{
                    $page->saveVersion();
                        $this->_helper->json(array("success" => true));
                    } catch (Exception $e) {
                        Logger::err($e);
                        $this->_helper->json(array("success" => false,"message"=>$e->getMessage()));
                    }

                }
            }
        }
        $this->_helper->json(false);


    }

    public function mobilePreviewAction() {

        $page = Document::getById($this->_getParam("id"));

        if($page instanceof Document_Page) {
            $this->view->previewUrl = $page->getFullPath() . "?pimcore_preview=true&time=" . time();
        }
    }

    public function targetingListAction() {

        $targets = array();
        $list = new Tool_Targeting_List();

        if($this->_getParam("documentId")) {
            $list->setCondition("documentId = ?", $this->_getParam("documentId"));
        } else {
            $list->setCondition("documentId IS NULL OR documentId = ''");
        }

        foreach($list->load() as $target) {
            $targets[] = array(
                "id" => $target->getId(),
                "text" => $target->getName()
            );
        }

        $this->_helper->json($targets);
    }

    public function targetingAddAction() {

        $target = new Tool_Targeting();
        $target->setName($this->_getParam("name"));

        if($this->_getParam("documentId")) {
            $target->setDocumentId($this->_getParam("documentId"));
        }

        $target->save();


        $this->_helper->json(array("success" => true, "id" => $target->getId()));
    }

    public function targetingDeleteAction() {

        $success = false;

        $target = Tool_Targeting::getById($this->_getParam("id"));
        if($target) {
            $target->delete();
            $success = true;
        }

        $this->_helper->json(array("success" => $success));
    }

    public function targetingGetAction() {

        $target = Tool_Targeting::getById($this->_getParam("id"));
        $redirectUrl = $target->getActions()->getRedirectUrl();
        if(is_numeric($redirectUrl)) {
            $doc = Document::getById($redirectUrl);
            if($doc instanceof Document) {
                $target->getActions()->redirectUrl = $doc->getFullPath();
            }
        }

        $this->_helper->json($target);
    }

    public function targetingSaveAction() {

        $data = Zend_Json::decode($this->getParam("data"));

        $target = Tool_Targeting::getById($this->_getParam("id"));
        $target->setValues($data["settings"]);

        $target->setConditions($data["conditions"]);

        $actions = new Tool_Targeting_Actions();
        $actions->setRedirectEnabled($data["actions"]["redirect.enabled"]);
        $actions->setRedirectUrl($data["actions"]["redirect.url"]);
        $actions->setRedirectCode($data["actions"]["redirect.code"]);
        $actions->setEventEnabled($data["actions"]["event.enabled"]);
        $actions->setEventKey($data["actions"]["event.key"]);
        $actions->setEventValue($data["actions"]["event.value"]);
        $actions->setCodesnippetEnabled($data["actions"]["codesnippet.enabled"]);
        $actions->setCodesnippetCode($data["actions"]["codesnippet.code"]);
        $actions->setCodesnippetSelector($data["actions"]["codesnippet.selector"]);
        $actions->setCodesnippetPosition($data["actions"]["codesnippet.position"]);
        $target->setActions($actions);

        $target->save();

        $this->_helper->json(array("success" => true));
    }

    public function targetingCreateVariantAction () {

        $targeting = Tool_Targeting::getById($this->getParam("tragetingId"));
        $page = Document::getById($this->getParam("documentId"));
        $docService = new Document_Service($this->getUser());
        $variant = $docService->copyAsChild($page,$page,true);
        $variant->setKey(Element_Service::getSaveCopyName("document", $page->getKey() . "_targeting_" . Pimcore_File::getValidFilename($targeting->getName()), $page));
        $variant->save();

        $this->_helper->json(array("id" => $variant->getId(), "path" => $variant->getFullPath()));
    }

    protected function setValuesToDocument(Document $page) {

        $this->addSettingsToDocument($page);
        $this->addDataToDocument($page);
        $this->addPropertiesToDocument($page);
        $this->addSchedulerToDocument($page);
    }

}
