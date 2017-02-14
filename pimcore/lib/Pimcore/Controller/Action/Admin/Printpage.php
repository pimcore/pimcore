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

namespace Pimcore\Controller\Action\Admin;

use Pimcore\Config;
use \Pimcore\Model\Document;
use Pimcore\Model\Element\Service;
use Pimcore\Web2Print\Processor;
use Pimcore\Logger;

class Printpage extends \Pimcore\Controller\Action\Admin\Document
{
    public function getDataByIdAction()
    {

        // check for lock
        if (\Pimcore\Model\Element\Editlock::isLocked($this->getParam("id"), "document")) {
            $this->_helper->json([
                "editlock" => \Pimcore\Model\Element\Editlock::getByElement($this->getParam("id"), "document")
            ]);
        }
        \Pimcore\Model\Element\Editlock::lock($this->getParam("id"), "document");

        $page = Document\Printpage::getById($this->getParam("id"));
        $page = $this->getLatestVersion($page);

        $page->getVersions();
        $page->getScheduledTasks();
        $page->idPath = Service::getIdPath($page);
        $page->userPermissions = $page->getUserPermissions();
        $page->setLocked($page->isLocked());

        if ($page->getContentMasterDocument()) {
            $page->contentMasterDocumentPath = $page->getContentMasterDocument()->getRealFullPath();
        }

        $this->addTranslationsData($page);

        // unset useless data
        $page->setElements(null);
        $page->childs = null;

        // cleanup properties
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
        if ($this->getParam("id")) {
            $page = Document\Printpage::getById($this->getParam("id"));

            $page = $this->getLatestVersion($page);
            $page->setUserModification($this->getUser()->getId());

            // save to session
            $key = "document_" . $this->getParam("id");
            $session = new \Zend_Session_Namespace("pimcore_documents");
            $session->$key = $page;

            if ($this->getParam("task") == "unpublish") {
                $page->setPublished(false);
            }
            if ($this->getParam("task") == "publish") {
                $page->setPublished(true);
            }

            // only save when publish or unpublish
            if (($this->getParam("task") == "publish" && $page->isAllowed("publish")) or ($this->getParam("task") == "unpublish" && $page->isAllowed("unpublish"))) {

                //check, if to cleanup existing elements of document
                $config = Config::getWeb2PrintConfig();
                if ($config->generalDocumentSaveMode == "cleanup") {
                    $page->setElements([]);
                }

                $this->setValuesToDocument($page);


                try {
                    $page->save();
                    $this->_helper->json(["success" => true]);
                } catch (\Exception $e) {
                    Logger::err($e);
                    $this->_helper->json(["success" => false, "message"=>$e->getMessage()]);
                }
            } else {
                if ($page->isAllowed("save")) {
                    $this->setValuesToDocument($page);


                    try {
                        $page->saveVersion();
                        $this->_helper->json(["success" => true]);
                    } catch (\Exception $e) {
                        Logger::err($e);
                        $this->_helper->json(["success" => false, "message"=>$e->getMessage()]);
                    }
                }
            }
        }
        $this->_helper->json(false);
    }

    /**
     * @param Document\PrintAbstract $page
     */
    protected function setValuesToDocument(Document\PrintAbstract $page)
    {
        $this->addSettingsToDocument($page);
        $this->addDataToDocument($page);
        $this->addPropertiesToDocument($page);
    }

    public function activeGenerateProcessAction()
    {
        /**
         * @var $document Document\Printpage
         */
        $document = Document\Printpage::getById(intval($this->getParam("id")));
        if (empty($document)) {
            throw new \Exception("Document with id " . $this->getParam("id") . " not found.");
        }

        $date = $document->getLastGeneratedDate();
        if ($date) {
            $date = $date->get(\Zend_Date::DATETIME_SHORT);
        }

        $inProgress = $document->getInProgress();

        $statusUpdate = [];
        if ($inProgress) {
            $statusUpdate = Processor::getInstance()->getStatusUpdate($document->getId());
        }

        $this->_helper->json([
            "activeGenerateProcess" => !empty($inProgress),
            "date" => $date,
            "message" => $document->getLastGenerateMessage(),
            "downloadAvailable" => file_exists($document->getPdfFileName()),
            "statusUpdate" => $statusUpdate
        ]);
    }

    public function pdfDownloadAction()
    {
        $document = Document\Printpage::getById(intval($this->getParam("id")));
        if (empty($document)) {
            throw new \Exception("Document with id " . $this->getParam("id") . " not found.");
        }

        if (file_exists($document->getPdfFileName())) {
            if ($this->getParam("download")) {
                header("Content-Type: application/pdf");
                header("Content-Disposition: attachment; filename=" . $document->getKey() . '.pdf'); while (@ob_end_flush()) ;
                flush();

                readfile($document->getPdfFileName());
                exit;
            } else {
                header("Content-Type: application/pdf"); while (@ob_end_flush()) ;
                flush();

                readfile($document->getPdfFileName());
                exit;
            }
        } else {
            throw new \Exception("File does not exist");
        }
    }

    public function startPdfGenerationAction()
    {
        $document = Document\Printpage::getById(intval($this->getParam("id")));
        if (empty($document)) {
            throw new \Exception("Document with id " . $this->getParam("id") . " not found.");
        }

        $document->generatePdf($this->getAllParams());

        $this->saveProcessingOptions($document->getId(), $this->getAllParams());

        $this->_helper->json(["success" => true]);
    }


    public function checkPdfDirtyAction()
    {
        $printDocument = Document\PrintAbstract::getById($this->getParam("id"));

        $dirty = true;
        if ($printDocument) {
            $dirty = $printDocument->pdfIsDirty();
        }
        $this->_helper->json(["pdfDirty" => $dirty]);
    }


    public function getProcessingOptionsAction()
    {
        $options = Processor::getInstance()->getProcessingOptions();

        $returnValue = [];

        $storedValues = $this->getStoredProcessingOptions($this->getParam("id"));

        foreach ($options as $option) {
            $value = $option['default'];
            if ($storedValues && array_key_exists($option['name'], $storedValues)) {
                $value = $storedValues[$option['name']];
            }

            $returnValue[] = [
                "name" => $option['name'],
                "label" => $option['name'],
                "value" => $value,
                "type" => $option['type'],
                "values" => $option['values']
            ];
        }


        $this->_helper->json(["options" => $returnValue]);
    }

    /**
     * @param $documentId
     * @return array|mixed
     */
    private function getStoredProcessingOptions($documentId)
    {
        $filename = PIMCORE_TEMPORARY_DIRECTORY . DIRECTORY_SEPARATOR . "web2print-processingoptions-" . $documentId . "_" . $this->getUser()->getId() . ".psf";
        if (file_exists($filename)) {
            return \Pimcore\Tool\Serialize::unserialize(file_get_contents($filename));
        } else {
            return [];
        }
    }

    /**
     * @param $documentId
     * @param $options
     */
    private function saveProcessingOptions($documentId, $options)
    {
        file_put_contents(PIMCORE_TEMPORARY_DIRECTORY . DIRECTORY_SEPARATOR . "web2print-processingoptions-" . $documentId . "_" . $this->getUser()->getId() . ".psf", \Pimcore\Tool\Serialize::serialize($options));
    }


    public function cancelGenerationAction()
    {
        Processor::getInstance()->cancelGeneration(intval($this->getParam("id")));
        $this->_helper->json(["success" => true]);
    }
}
