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

use Pimcore\Model\Element;
use Pimcore\Model\Document;

class Admin_SnippetController extends \Pimcore\Controller\Action\Admin\Document {

    public function getDataByIdAction() {

        // check for lock
        if (Element\Editlock::isLocked($this->getParam("id"), "document")) {
            $this->_helper->json(array(
                "editlock" => Element\Editlock::getByElement($this->getParam("id"), "document")
            ));
        }
        Element\Editlock::lock($this->getParam("id"), "document");

        $snippet = Document\Snippet::getById($this->getParam("id"));
        $modificationDate = $snippet->getModificationDate();
        
        $snippet = $this->getLatestVersion($snippet);

        $snippet->setVersions(array_splice($snippet->getVersions(), 0, 1));
        $snippet->getScheduledTasks();
        $snippet->idPath = Element\Service::getIdPath($snippet);
        $snippet->userPermissions = $snippet->getUserPermissions();
        $snippet->setLocked($snippet->isLocked());
        $snippet->setParent(null);

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
            $snippet = Document\Snippet::getById($this->getParam("id"));
            $snippet = $this->getLatestVersion($snippet);

            $snippet->setUserModification($this->getUser()->getId());

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
                    $this->saveToSession($snippet);
                    $this->_helper->json(array("success" => true));
                } catch (\Exception $e) {
                    $this->_helper->json(array("success" => false, "message" => $e->getMessage()));
                }


            }
            else {
                if ($snippet->isAllowed("save")) {
                    $this->setValuesToDocument($snippet);

                    try {
                        $snippet->saveVersion();
                        $this->saveToSession($snippet);
                        $this->_helper->json(array("success" => true));
                    } catch (\Exception $e) {
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
