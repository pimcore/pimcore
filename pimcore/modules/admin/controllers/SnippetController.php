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

use Pimcore\Model\Element;
use Pimcore\Model\Document;
use Pimcore\Logger;

class Admin_SnippetController extends \Pimcore\Controller\Action\Admin\Document
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

        $snippet = Document\Snippet::getById($this->getParam("id"));
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
        $returnValueContainer = new \Pimcore\Model\Tool\Admin\EventDataContainer(object2array($snippet));
        \Pimcore::getEventManager()->trigger("admin.document.get.preSendData", $this, [
            "document" => $snippet,
            "returnValueContainer" => $returnValueContainer
        ]);

        if ($snippet->isAllowed("view")) {
            $this->_helper->json($returnValueContainer->getData());
        }

        $this->_helper->json(false);
    }

    public function saveAction()
    {
        try {
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
                        $this->_helper->json(["success" => true]);
                    } catch (\Exception $e) {
                        if (\Pimcore\Tool\Admin::isExtJS6() && $e instanceof Element\ValidationException) {
                            throw $e;
                        }
                        $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                    }
                } else {
                    if ($snippet->isAllowed("save")) {
                        $this->setValuesToDocument($snippet);

                        try {
                            $snippet->saveVersion();
                            $this->saveToSession($snippet);
                            $this->_helper->json(["success" => true]);
                        } catch (\Exception $e) {
                            $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
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

    /**
     * @param Document $snippet
     */
    protected function setValuesToDocument(Document $snippet)
    {
        $this->addSettingsToDocument($snippet);
        $this->addDataToDocument($snippet);
        $this->addSchedulerToDocument($snippet);
        $this->addPropertiesToDocument($snippet);
    }
}
