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
use Pimcore\Model\Tool;
use Pimcore\Model\Tool\Newsletter;
use Pimcore\Logger;

class Admin_NewsletterController extends \Pimcore\Controller\Action\Admin\Document
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

        $email = Document\Newsletter::getById($this->getParam("id"));
        $email = clone $email;
        $email = $this->getLatestVersion($email);

        $versions = Element\Service::getSafeVersionInfo($email->getVersions());
        $email->setVersions(array_splice($versions, 0, 1));
        $email->idPath = Element\Service::getIdPath($email);
        $email->userPermissions = $email->getUserPermissions();
        $email->setLocked($email->isLocked());
        $email->setParent(null);

        // unset useless data
        $email->setElements(null);
        $email->childs = null;

        $this->addTranslationsData($email);
        $this->minimizeProperties($email);


        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $returnValueContainer = new \Pimcore\Model\Tool\Admin\EventDataContainer(object2array($email));
        \Pimcore::getEventManager()->trigger("admin.document.get.preSendData", $this, [
            "document" => $email,
            "returnValueContainer" => $returnValueContainer
        ]);

        if ($email->isAllowed("view")) {
            $this->_helper->json($returnValueContainer->getData());
        }

        $this->_helper->json(false);
    }

    public function saveAction()
    {
        try {
            if ($this->getParam("id")) {
                $page = Document\Newsletter::getById($this->getParam("id"));

                $page = $this->getLatestVersion($page);
                $page->setUserModification($this->getUser()->getId());

                if ($this->getParam("task") == "unpublish") {
                    $page->setPublished(false);
                }
                if ($this->getParam("task") == "publish") {
                    $page->setPublished(true);
                }
                // only save when publish or unpublish
                if (($this->getParam("task") == "publish" && $page->isAllowed("publish")) or ($this->getParam("task") == "unpublish" && $page->isAllowed("unpublish"))) {
                    $this->setValuesToDocument($page);


                    try {
                        $page->save();
                        $this->saveToSession($page);
                        $this->_helper->json(["success" => true]);
                    } catch (\Exception $e) {
                        Logger::err($e);
                        $this->_helper->json(["success" => false, "message" => $e->getMessage()]);
                    }
                } else {
                    if ($page->isAllowed("save")) {
                        $this->setValuesToDocument($page);


                        try {
                            $page->saveVersion();
                            $this->saveToSession($page);
                            $this->_helper->json(["success" => true]);
                        } catch (\Exception $e) {
                            if (Tool\Admin::isExtJS6() && $e instanceof Element\ValidationException) {
                                throw $e;
                            }

                            Logger::err($e);
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
     * @param Document $page
     */
    protected function setValuesToDocument(Document $page)
    {
        $this->addSettingsToDocument($page);
        $this->addDataToDocument($page);
        $this->addPropertiesToDocument($page);
    }


    public function checksqlAction()
    {
        $count = 0;
        $success = false;
        try {
            $className = "\\Pimcore\\Model\\Object\\" . ucfirst($this->getParam("class")) . "\\Listing";
            $list = new $className();

            $conditions = ["(newsletterActive = 1 AND newsletterConfirmed = 1)"];
            if ($this->getParam("objectFilterSQL")) {
                $conditions[] = $this->getParam("objectFilterSQL");
            }
            $list->setCondition(implode(" AND ", $conditions));

            $count = $list->getTotalCount();
            $success = true;
        } catch (\Exception $e) {
        }

        $this->_helper->json([
            "count" => $count,
            "success" => $success
        ]);
    }

    public function getAvailableClassesAction()
    {
        $classList = new \Pimcore\Model\Object\ClassDefinition\Listing();

        $availableClasses = [];
        foreach ($classList->load() as $class) {
            $fieldCount = 0;
            foreach ($class->getFieldDefinitions() as $fd) {
                if ($fd instanceof \Pimcore\Model\Object\ClassDefinition\Data\NewsletterActive ||
                    $fd instanceof \Pimcore\Model\Object\ClassDefinition\Data\NewsletterConfirmed ||
                    $fd instanceof \Pimcore\Model\Object\ClassDefinition\Data\Email) {
                    $fieldCount++;
                }
            }

            if ($fieldCount >= 3) {
                $availableClasses[] = ['name' => $class->getName()];
            }
        }

        $this->_helper->json(['data' => $availableClasses]);
    }

    public function getAvailableReportsAction()
    {
        $task = $this->getParam("task");

        if ($task === 'list') {
            $reportList = \Pimcore\Model\Tool\CustomReport\Config::getReportsList();

            $availableReports = [];
            foreach ($reportList as $report) {
                $availableReports[] = ['id' => $report['id'], 'text' => $report['text']];
            }

            $this->_helper->json(['data' => $availableReports]);
        } elseif ($task === 'fieldNames') {
            $reportId = $this->getParam("reportId");
            $report = \Pimcore\Model\Tool\CustomReport\Config::getByName($reportId);
            $columnConfiguration = $report->getColumnConfiguration();

            $availableColumns = [];
            foreach ($columnConfiguration as $column) {
                if ($column['display']) {
                    $availableColumns[] = ['name' => $column['name']];
                }
            }

            $this->_helper->json(['data' => $availableColumns]);
        }
    }

    public function getSendStatusAction()
    {
        $document = Document\Newsletter::getById($this->getParam("id"));
        $data = Tool\TmpStore::get($document->getTmpStoreId());

        $this->_helper->json([
            "data" => $data ? $data->getData() : null,
            "success" => true
        ]);
    }

    public function stopSendAction()
    {
        $document = Document\Newsletter::getById($this->getParam("id"));
        Tool\TmpStore::delete($document->getTmpStoreId());

        $this->_helper->json([
            "success" => true
        ]);
    }

    public function sendAction()
    {
        $document = Document\Newsletter::getById($this->getParam("id"));

        if (Tool\TmpStore::get($document->getTmpStoreId())) {
            throw new Exception("newsletter sending already in progress, need to finish first.");
        }

        $document = Document\Newsletter::getById($this->getParam("id"));

        Tool\TmpStore::add($document->getTmpStoreId(), [
            'documentId' => $document->getId(),
            'addressSourceAdapterName' => $this->getParam("addressAdapterName"),
            'adapterParams' => json_decode($this->getParam("adapterParams"), true),
            'inProgress' => false,
            'progress' => 0
        ], 'newsletter');

        \Pimcore\Tool\Console::runPhpScriptInBackground(realpath(PIMCORE_PATH . DIRECTORY_SEPARATOR . "cli" . DIRECTORY_SEPARATOR . "console.php"), "internal:newsletter-document-send " . escapeshellarg($document->getTmpStoreId()) . " " . escapeshellarg(\Pimcore\Tool::getHostUrl()), PIMCORE_LOG_DIRECTORY . DIRECTORY_SEPARATOR . "newsletter-sending-output.log");
        $this->_helper->json(["success" => true]);
    }


    public function sendTestAction()
    {
        $document = Document\Newsletter::getById($this->getParam("id"));
        $addressSourceAdapterName = $this->getParam("addressAdapterName");
        $adapterParams = json_decode($this->getParam("adapterParams"), true);

        $adapterClass = "\\Pimcore\\Document\\Newsletter\\AddressSourceAdapter\\" . ucfirst($addressSourceAdapterName);

        /**
         * @var $addressAdapter \Pimcore\Document\Newsletter\AddressSourceAdapterInterface
         */
        $addressAdapter = new $adapterClass($adapterParams);

        $sendingContainer = $addressAdapter->getParamsForTestSending($this->getParam("testMailAddress"));

        $mail = \Pimcore\Tool\Newsletter::prepareMail($document);
        \Pimcore\Tool\Newsletter::sendNewsletterDocumentBasedMail($mail, $sendingContainer);

        $this->_helper->json(["success" => true]);
    }
}
