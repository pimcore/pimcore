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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Event\AdminEvents;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Tool;
use Pimcore\Model\Tool\Newsletter;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/newsletter")
 */
class NewsletterController extends DocumentControllerBase
{
    /**
     * @Route("/get-data-by-id")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request)
    {
        // check for lock
        if (Element\Editlock::isLocked($request->get('id'), 'document')) {
            return $this->json([
                'editlock' => Element\Editlock::getByElement($request->get('id'), 'document')
            ]);
        }
        Element\Editlock::lock($request->get('id'), 'document');

        $email = Document\Newsletter::getById($request->get('id'));
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
        $data = object2array($email);
        $event = new GenericEvent($this, [
            'data' => $data,
            'document' => $email
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_GET_PRE_SEND_DATA, $event);
        $data = $event->getArgument('data');

        if ($email->isAllowed('view')) {
            return $this->json($data);
        }

        return $this->json(false);
    }

    /**
     * @Route("/save")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request)
    {
        try {
            if ($request->get('id')) {
                $page = Document\Newsletter::getById($request->get('id'));

                $page = $this->getLatestVersion($page);
                $page->setUserModification($this->getUser()->getId());

                if ($request->get('task') == 'unpublish') {
                    $page->setPublished(false);
                }
                if ($request->get('task') == 'publish') {
                    $page->setPublished(true);
                }
                // only save when publish or unpublish
                if (($request->get('task') == 'publish' && $page->isAllowed('publish')) or ($request->get('task') == 'unpublish' && $page->isAllowed('unpublish'))) {
                    $this->setValuesToDocument($request, $page);

                    try {
                        $page->save();
                        $this->saveToSession($page);

                        return $this->json(['success' => true]);
                    } catch (\Exception $e) {
                        Logger::err($e);

                        return $this->json(['success' => false, 'message' => $e->getMessage()]);
                    }
                } else {
                    if ($page->isAllowed('save')) {
                        $this->setValuesToDocument($request, $page);

                        try {
                            $page->saveVersion();
                            $this->saveToSession($page);

                            return $this->json(['success' => true]);
                        } catch (\Exception $e) {
                            if ($e instanceof Element\ValidationException) {
                                throw $e;
                            }

                            Logger::err($e);

                            return $this->json(['success' => false, 'message' => $e->getMessage()]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::log($e);
            if ($e instanceof Element\ValidationException) {
                return $this->json(['success' => false, 'type' => 'ValidationException', 'message' => $e->getMessage(), 'stack' => $e->getTraceAsString(), 'code' => $e->getCode()]);
            }
            throw $e;
        }

        return $this->json(false);
    }

    /**
     * @param Document $page
     */
    protected function setValuesToDocument(Request $request, Document $page)
    {
        $this->addSettingsToDocument($request, $page);
        $this->addDataToDocument($request, $page);
        $this->addPropertiesToDocument($request, $page);
    }

    /**
     * @Route("/checksql")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function checksqlAction(Request $request)
    {
        $count = 0;
        $success = false;
        try {
            $className = '\\Pimcore\\Model\\Object\\' . ucfirst($request->get('class')) . '\\Listing';
            $list = new $className();

            $conditions = ['(newsletterActive = 1 AND newsletterConfirmed = 1)'];
            if ($request->get('objectFilterSQL')) {
                $conditions[] = $request->get('objectFilterSQL');
            }
            $list->setCondition(implode(' AND ', $conditions));

            $count = $list->getTotalCount();
            $success = true;
        } catch (\Exception $e) {
        }

        return $this->json([
            'count' => $count,
            'success' => $success
        ]);
    }

    /**
     * @Route("/get-available-classes")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableClassesAction(Request $request)
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

        return $this->json(['data' => $availableClasses]);
    }

    /**
     * @Route("/get-available-reports")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableReportsAction(Request $request)
    {
        $task = $request->get('task');

        if ($task === 'list') {
            $reportList = \Pimcore\Model\Tool\CustomReport\Config::getReportsList();

            $availableReports = [];
            foreach ($reportList as $report) {
                $availableReports[] = ['id' => $report['id'], 'text' => $report['text']];
            }

            return $this->json(['data' => $availableReports]);
        } elseif ($task === 'fieldNames') {
            $reportId = $request->get('reportId');
            $report = \Pimcore\Model\Tool\CustomReport\Config::getByName($reportId);
            $columnConfiguration = $report->getColumnConfiguration();

            $availableColumns = [];
            foreach ($columnConfiguration as $column) {
                if ($column['display']) {
                    $availableColumns[] = ['name' => $column['name']];
                }
            }

            return $this->json(['data' => $availableColumns]);
        }
    }

    /**
     * @Route("/get-send-status")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getSendStatusAction(Request $request)
    {
        $document = Document\Newsletter::getById($request->get('id'));
        $data = Tool\TmpStore::get($document->getTmpStoreId());

        return $this->json([
            'data' => $data ? $data->getData() : null,
            'success' => true
        ]);
    }

    /**
     * @Route("/stop-send")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function stopSendAction(Request $request)
    {
        $document = Document\Newsletter::getById($request->get('id'));
        Tool\TmpStore::delete($document->getTmpStoreId());

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @Route("/send")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function sendAction(Request $request)
    {
        $document = Document\Newsletter::getById($request->get('id'));

        if (Tool\TmpStore::get($document->getTmpStoreId())) {
            throw new \Exception('newsletter sending already in progress, need to finish first.');
        }

        $document = Document\Newsletter::getById($request->get('id'));

        Tool\TmpStore::add($document->getTmpStoreId(), [
            'documentId' => $document->getId(),
            'addressSourceAdapterName' => $request->get('addressAdapterName'),
            'adapterParams' => json_decode($request->get('adapterParams'), true),
            'inProgress' => false,
            'progress' => 0
        ], 'newsletter');

        \Pimcore\Tool\Console::runPhpScriptInBackground(realpath(PIMCORE_PROJECT_ROOT . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console'), 'internal:newsletter-document-send ' . escapeshellarg($document->getTmpStoreId()) . ' ' . escapeshellarg(\Pimcore\Tool::getHostUrl()), PIMCORE_LOG_DIRECTORY . DIRECTORY_SEPARATOR . 'newsletter-sending-output.log');

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/send-test")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function sendTestAction(Request $request)
    {
        $document = Document\Newsletter::getById($request->get('id'));
        $addressSourceAdapterName = $request->get('addressAdapterName');
        $adapterParams = json_decode($request->get('adapterParams'), true);

        $adapterClass = '\\Pimcore\\Document\\Newsletter\\AddressSourceAdapter\\' . ucfirst($addressSourceAdapterName);

        /**
         * @var $addressAdapter \Pimcore\Document\Newsletter\AddressSourceAdapterInterface
         */
        $addressAdapter = new $adapterClass($adapterParams);

        $sendingContainer = $addressAdapter->getParamsForTestSending($request->get('testMailAddress'));

        $mail = \Pimcore\Tool\Newsletter::prepareMail($document);
        \Pimcore\Tool\Newsletter::sendNewsletterDocumentBasedMail($mail, $sendingContainer);

        return $this->json(['success' => true]);
    }
}
