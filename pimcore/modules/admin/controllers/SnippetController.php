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

class Admin_SnippetController extends Pimcore_Controller_Action_Admin_Document {

    public function getDataByIdAction() {

        // check for lock
        if (Element_Editlock::isLocked($this->getParam("id"), "document")) {
            $this->_helper->json(array(
                "editlock" => Element_Editlock::getByElement($this->getParam("id"), "document")
            ));
        }
        Element_Editlock::lock($this->getParam("id"), "document");

        $snippet = Document_Snippet::getById($this->getParam("id"));
        $modificationDate = $snippet->getModificationDate();
        
        $snippet = $this->getLatestVersion($snippet);
        
        $snippet->getVersions();
        $snippet->getScheduledTasks();
        $snippet->idPath = Pimcore_Tool::getIdPathForElement($snippet);
        $snippet->userPermissions = $snippet->getUserPermissions();
        $snippet->setLocked($snippet->isLocked());

        if($snippet->getContentMasterDocument()) {
            $snippet->contentMasterDocumentPath = $snippet->getContentMasterDocument()->getRealFullPath();
        }

        $this->minimizeProperties($snippet);

        // unset useless data
        $snippet->setElements(null);

        if ($snippet->isAllowed("view")) {
            $this->_helper->json($snippet);
        }

        $this->_helper->json(false);
    }

    public function saveAction() {
        if ($this->getParam("id")) {
            $snippet = Document_Snippet::getById($this->getParam("id"));
            $snippet = $this->getLatestVersion($snippet);

            $snippet->setUserModification($this->getUser()->getId());

            // save to session
            $key = "document_" . $this->getParam("id");
            $session = new Zend_Session_Namespace("pimcore_documents");
            $session->$key = $snippet;


            if ($this->getParam("task") == "unpublish") {
                $snippet->setPublished(false);
            }
            if ($this->getParam("task") == "publish") {
                $snippet->setPublished(true);
            }


            if (($this->getParam("task") == "publish" && $snippet->isAllowed("publish")) or ($this->getParam("task") == "unpublish" && $snippet->isAllowed("unpublish"))) {
                $this->setValuesToDocument($snippet);

                try {
                    $snippet->save();
                    $this->_helper->json(array("success" => true));
                } catch (Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }


            }
            else {
                if ($snippet->isAllowed("save")) {
                    $this->setValuesToDocument($snippet);

                    try {
                        $snippet->saveVersion();
                        $this->_helper->json(array("success" => true));
                    } catch (Exception $e) {
                        $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                    }


                }
            }
        }

        $this->_helper->json(false);
    }

    protected function setValuesToDocument(Document $snippet) {

        $this->addSettingsToDocument($snippet);
        $this->addDataToDocument($snippet);
        $this->addSchedulerToDocument($snippet);
        $this->addPropertiesToDocument($snippet);
    }

}
