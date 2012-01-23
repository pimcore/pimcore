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

    protected function setValuesToDocument(Document $page) {

        $this->addSettingsToDocument($page);
        $this->addDataToDocument($page);
        $this->addPropertiesToDocument($page);
        $this->addSchedulerToDocument($page);
    }

}
