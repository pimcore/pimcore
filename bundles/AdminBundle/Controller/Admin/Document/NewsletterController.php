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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\Document;

use Exception;
use Pimcore;
use Pimcore\Document\Newsletter\AddressSourceAdapterFactoryInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Email;
use Pimcore\Model\DataObject\ClassDefinition\Data\NewsletterActive;
use Pimcore\Model\DataObject\ClassDefinition\Data\NewsletterConfirmed;
use Pimcore\Model\DataObject\ClassDefinition\Listing;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Tool;
use Pimcore\Model\Tool\CustomReport\Config;
use Pimcore\Tool\Console;
use Pimcore\Tool\Newsletter;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/newsletter")
 */
class NewsletterController extends DocumentControllerBase
{
    use Pimcore\Controller\Traits\ElementEditLockHelperTrait;

    /**
     * @Route("/save-to-session", name="pimcore_admin_document_newsletter_savetosession", methods={"POST"})
     *
     * {@inheritDoc}
     */
    public function saveToSessionAction(Request $request)
    {
        return parent::saveToSessionAction($request);
    }

    /**
     * @Route("/remove-from-session", name="pimcore_admin_document_newsletter_removefromsession", methods={"DELETE"})
     *
     * {@inheritDoc}
     */
    public function removeFromSessionAction(Request $request)
    {
        return parent::removeFromSessionAction($request);
    }

    /**
     * @Route("/change-master-document", name="pimcore_admin_document_newsletter_changemasterdocument", methods={"PUT"})
     *
     * {@inheritDoc}
     */
    public function changeMasterDocumentAction(Request $request)
    {
        return parent::changeMasterDocumentAction($request);
    }

    /**
     * @Route("/get-data-by-id", name="pimcore_admin_document_newsletter_getdatabyid", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request): JsonResponse
    {
        $email = Document\Newsletter::getById($request->get('id'));

        if (!$email) {
            throw $this->createNotFoundException('Document not found');
        }

        // check for lock
        if ($email->isAllowed('save') || $email->isAllowed('publish') || $email->isAllowed('unpublish') || $email->isAllowed('delete')) {
            if (Element\Editlock::isLocked($request->get('id'), 'document')) {
                return $this->getEditLockResponse($request->get('id'), 'document');
            }
            Element\Editlock::lock($request->get('id'), 'document');
        }

        $email = clone $email;
        $isLatestVersion = true;
        $email = $this->getLatestVersion($email, $isLatestVersion);

        $versions = Element\Service::getSafeVersionInfo($email->getVersions());
        $email->setVersions(array_splice($versions, -1, 1));
        $email->setLocked($email->isLocked());
        $email->setParent(null);

        // unset useless data
        $email->setEditables(null);
        $email->setChildren(null);

        $data = $email->getObjectVars();

        $this->addTranslationsData($email, $data);
        $this->minimizeProperties($email, $data);

        $data['url'] = $email->getUrl();
        // this used for the "this is not a published version" hint
        $data['documentFromVersion'] = !$isLatestVersion;

        $this->preSendDataActions($data, $email);

        if ($email->isAllowed('view')) {
            return $this->adminJson($data);
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @Route("/save", name="pimcore_admin_document_newsletter_save", methods={"PUT", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function saveAction(Request $request): JsonResponse
    {
        $page = Document\Newsletter::getById($request->get('id'));

        if (!$page) {
            throw $this->createNotFoundException('Document not found');
        }

        $page = $this->getLatestVersion($page);
        $page->setUserModification($this->getAdminUser()->getId());

        if ($request->get('task') === 'unpublish') {
            $page->setPublished(false);
        }

        if ($request->get('task') === 'publish') {
            $page->setPublished(true);
        }
        // only save when publish or unpublish
        if (($request->get('task') === 'publish' && $page->isAllowed('publish')) ||
            ($request->get('task') === 'unpublish' && $page->isAllowed('unpublish'))) {
            $this->setValuesToDocument($request, $page);

            $page->save();
            $this->saveToSession($page);

            $treeData = $this->getTreeNodeConfig($page);

            return $this->adminJson([
                'success' => true,
                'data' => [
                    'versionDate' => $page->getModificationDate(),
                    'versionCount' => $page->getVersionCount(),
                ],
                'treeData' => $treeData,
            ]);
        } elseif ($page->isAllowed('save')) {
            $this->setValuesToDocument($request, $page);
            $page->saveVersion();
            $this->saveToSession($page);

            return $this->adminJson(['success' => true]);
        } else {
            throw $this->createAccessDeniedHttpException();
        }
    }

    /**
     * @param Request $request
     * @param Document $page
     */
    protected function setValuesToDocument(Request $request, Document $page)
    {
        $this->addSettingsToDocument($request, $page);
        $this->addDataToDocument($request, $page);
        $this->addPropertiesToDocument($request, $page);

        // plaintext
        if ($request->get('plaintext')) {
            $plaintext = $this->decodeJson($request->get('plaintext'));
            $page->setValues($plaintext);
        }
    }

    /**
     * @Route("/checksql", name="pimcore_admin_document_newsletter_checksql", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function checksqlAction(Request $request): JsonResponse
    {
        $count = 0;
        $success = false;

        try {
            $className = '\\Pimcore\\Model\\DataObject\\' . ucfirst($request->get('class')) . '\\Listing';
            /** @var Pimcore\Model\DataObject\Listing $list */
            $list = new $className();

            $conditions = ['(newsletterActive = 1 AND newsletterConfirmed = 1)'];
            if ($request->get('objectFilterSQL')) {
                $conditions[] = $request->get('objectFilterSQL');
            }
            $list->setCondition(implode(' AND ', $conditions));

            $count = $list->getTotalCount();
            $success = true;
        } catch (Exception $e) {
        }

        return $this->adminJson([
            'count' => $count,
            'success' => $success,
        ]);
    }

    /**
     * @Route("/get-available-classes", name="pimcore_admin_document_newsletter_getavailableclasses", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function getAvailableClassesAction(): JsonResponse
    {
        $classList = new Listing();

        $availableClasses = [];
        foreach ($classList->load() as $class) {
            $fieldCount = 0;
            foreach ($class->getFieldDefinitions() as $fd) {
                if ($fd instanceof NewsletterActive ||
                    $fd instanceof NewsletterConfirmed ||
                    $fd instanceof Email) {
                    $fieldCount++;
                }
            }

            if ($fieldCount >= 3) {
                $availableClasses[] = ['name' => $class->getName()];
            }
        }

        return $this->adminJson(['data' => $availableClasses]);
    }

    /**
     * @Route("/get-available-reports", name="pimcore_admin_document_newsletter_getavailablereports", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableReportsAction(Request $request): JsonResponse
    {
        $task = $request->get('task');

        if ($task === 'list') {
            $reportList = Config::getReportsList();

            $availableReports = [];
            foreach ($reportList as $report) {
                $availableReports[] = ['id' => $report['id'], 'text' => $report['text']];
            }

            return $this->adminJson(['data' => $availableReports]);
        }

        if ($task === 'fieldNames') {
            $reportId = $request->get('reportId');
            $report = Config::getByName($reportId);
            $columnConfiguration = $report !== null ? $report->getColumnConfiguration() : [];

            $availableColumns = [];
            foreach ($columnConfiguration as $column) {
                if ($column['display']) {
                    $availableColumns[] = ['name' => $column['name']];
                }
            }

            return $this->adminJson(['data' => $availableColumns]);
        }

        return $this->adminJson(['success' => false]);
    }

    /**
     * @Route("/get-send-status", name="pimcore_admin_document_newsletter_getsendstatus", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getSendStatusAction(Request $request): JsonResponse
    {
        /** @var Document\Newsletter $document */
        $document = Document\Newsletter::getById($request->get('id'));
        $data = Tool\TmpStore::get($document->getTmpStoreId());

        return $this->adminJson([
            'data' => $data ? $data->getData() : null,
            'success' => true,
        ]);
    }

    /**
     * @Route("/stop-send", name="pimcore_admin_document_newsletter_stopsend", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function stopSendAction(Request $request): JsonResponse
    {
        /** @var Document\Newsletter $document */
        $document = Document\Newsletter::getById($request->get('id'));
        Tool\TmpStore::delete($document->getTmpStoreId());

        return $this->adminJson([
            'success' => true,
        ]);
    }

    /**
     * @Route("/send", name="pimcore_admin_document_newsletter_send", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function sendAction(Request $request): JsonResponse
    {
        /** @var Document\Newsletter $document */
        $document = Document\Newsletter::getById($request->get('id'));

        if (Tool\TmpStore::get($document->getTmpStoreId())) {
            throw new RuntimeException('Newsletter sending already in progress, need to finish first.');
        }

        /** @var Document\Newsletter $document */
        $document = Document\Newsletter::getById($request->get('id'));

        Tool\TmpStore::add($document->getTmpStoreId(), [
            'documentId' => $document->getId(),
            'addressSourceAdapterName' => $request->get('addressAdapterName'),
            'adapterParams' => json_decode($request->get('adapterParams'), true),
            'inProgress' => false,
            'progress' => 0,
        ], 'newsletter');

        Console::runPhpScriptInBackground(
            realpath(PIMCORE_PROJECT_ROOT . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console'),
            'internal:newsletter-document-send ' . escapeshellarg($document->getTmpStoreId()) . ' ' . escapeshellarg(\Pimcore\Tool::getHostUrl()),
            PIMCORE_LOG_DIRECTORY . DIRECTORY_SEPARATOR . 'newsletter-sending-output.log'
        );

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/calculate", name="pimcore_admin_document_newsletter_calculate", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function calculateAction(Request $request): JsonResponse
    {
        $addressSourceAdapterName = $request->get('addressAdapterName');
        $adapterParams = json_decode($request->get('adapterParams'), true);

        $serviceLocator = $this->get('pimcore.newsletter.address_source_adapter.factories');

        if (!$serviceLocator->has($addressSourceAdapterName)) {
            $msg = sprintf(
                'Cannot send newsletters because Address Source Adapter with identifier %s could not be found',
                $addressSourceAdapterName
            );

            return $this->adminJson(['success' => false, 'count' => '0', 'message' => $msg]);
        }

        /** @var AddressSourceAdapterFactoryInterface $addressAdapterFactory */
        $addressAdapterFactory = $serviceLocator->get($addressSourceAdapterName);
        $addressAdapter = $addressAdapterFactory->create($adapterParams);

        return $this->adminJson(['success' => true, 'count' => $addressAdapter->getTotalRecordCount()]);
    }

    /**
     * @Route("/send-test", name="pimcore_admin_document_newsletter_sendtest", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function sendTestAction(Request $request): JsonResponse
    {
        $document = Document\Newsletter::getById($request->get('id'));
        $addressSourceAdapterName = $request->get('addressAdapterName');
        $adapterParams = json_decode($request->get('adapterParams'), true);
        $testMailAddress = $request->get('testMailAddress');

        if (empty($testMailAddress)) {
            return $this->adminJson([
                'success' => false,
                'message' => 'Please provide a valid email address to send test newsletter',
            ]);
        }

        $serviceLocator = $this->get('pimcore.newsletter.address_source_adapter.factories');

        if (!$serviceLocator->has($addressSourceAdapterName)) {
            return $this->adminJson([
                'success' => false,
                'error' => sprintf(
                    'Cannot send newsletters because Address Source Adapter with identifier %s could not be found',
                    $addressSourceAdapterName
                ),
            ]);
        }

        /** @var AddressSourceAdapterFactoryInterface $addressAdapterFactory */
        $addressAdapterFactory = $serviceLocator->get($addressSourceAdapterName);
        $addressAdapter = $addressAdapterFactory->create($adapterParams);

        $sendingContainer = $addressAdapter->getParamsForTestSending($testMailAddress);

        $mail = Newsletter::prepareMail($document);
        Newsletter::sendNewsletterDocumentBasedMail($mail, $sendingContainer);

        return $this->adminJson(['success' => true]);
    }
}
