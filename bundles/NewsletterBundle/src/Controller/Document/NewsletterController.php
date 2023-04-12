<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\NewsletterBundle\Controller\Document;

use Exception;
use Pimcore;
use Pimcore\Bundle\AdminBundle\Controller\Admin\Document\DocumentControllerBase;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\NewsletterBundle\Document\Newsletter\AddressSourceAdapterFactoryInterface;
use Pimcore\Bundle\NewsletterBundle\Model\DataObject\ClassDefinition\Data\NewsletterActive;
use Pimcore\Bundle\NewsletterBundle\Model\DataObject\ClassDefinition\Data\NewsletterConfirmed;
use Pimcore\Bundle\NewsletterBundle\Model\Document\Newsletter;
use Pimcore\Bundle\NewsletterBundle\Tool\Newsletter as NewsletterTool;
use Pimcore\Model\DataObject\ClassDefinition\Data\Email;
use Pimcore\Model\DataObject\ClassDefinition\Listing;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Tool\TmpStore;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/newsletter", name="pimcore_bundle_newsletter_document_newsletter_")
 *
 * @internal
 */
class NewsletterController extends DocumentControllerBase
{
    /**
     * @Route("/get-data-by-id", name="getdatabyid", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function getDataByIdAction(Request $request): JsonResponse
    {
        $this->checkPermission('newsletters');

        $email = Newsletter::getById((int)$request->get('id'));

        if (!$email) {
            throw $this->createNotFoundException('Document not found');
        }

        if (($lock = $this->checkForLock($email, $request->getSession()->getId())) instanceof JsonResponse) {
            return $lock;
        }

        $email = clone $email;
        $draftVersion = null;
        $email = $this->getLatestVersion($email, $draftVersion);

        $versions = Element\Service::getSafeVersionInfo($email->getVersions());
        $email->setVersions(array_splice($versions, -1, 1));
        $email->setParent(null);

        // unset useless data
        $email->setEditables(null);
        $email->setChildren(null);

        $data = $email->getObjectVars();
        $data['locked'] = $email->isLocked();

        $this->addTranslationsData($email, $data);
        $this->minimizeProperties($email, $data);
        $this->populateUsersNames($email, $data);

        $data['url'] = $email->getUrl();

        return $this->preSendDataActions($data, $email, $draftVersion);
    }

    /**
     * @Route("/save", name="save", methods={"PUT", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function saveAction(Request $request): JsonResponse
    {
        $this->checkPermission('newsletters');

        $page = Newsletter::getById((int) $request->get('id'));
        if (!$page) {
            throw $this->createNotFoundException('Document not found');
        }

        list($task, $page, $version) = $this->saveDocument($page, $request);
        $this->saveToSession($page, $request->getSession());

        if ($task === self::TASK_PUBLISH || $task === self::TASK_UNPUBLISH) {
            $treeData = $this->getTreeNodeConfig($page);

            return $this->adminJson([
                'success' => true,
                'data' => [
                    'versionDate' => $page->getModificationDate(),
                    'versionCount' => $page->getVersionCount(),
                ],
                'treeData' => $treeData,
            ]);
        }

        $draftData = [];
        if ($version) {
            $draftData = [
                'id' => $version->getId(),
                'modificationDate' => $version->getDate(),
                'isAutoSave' => $version->isAutoSave(),
            ];
        }

        return $this->adminJson(['success' => true, 'draft' => $draftData]);
    }

    protected function setValuesToDocument(Request $request, Document $document): void
    {
        $this->addSettingsToDocument($request, $document);
        $this->addDataToDocument($request, $document);
        $this->addPropertiesToDocument($request, $document);

        // plaintext
        if ($request->get('plaintext')) {
            $plaintext = $this->decodeJson($request->get('plaintext'));
            $document->setValues($plaintext);
        }
    }

    /**
     * @Route("/checksql", name="checksql", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function checksqlAction(Request $request): JsonResponse
    {
        $this->checkPermission('newsletters');

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
     * @Route("/get-available-classes", name="getavailableclasses", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function getAvailableClassesAction(): JsonResponse
    {
        $this->checkPermission('newsletters');

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
     * @Route("/get-available-reports", name="getavailablereports", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableReportsAction(Request $request): JsonResponse
    {
        $this->checkPermission('newsletters');

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
     * @Route("/get-send-status", name="getsendstatus", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getSendStatusAction(Request $request): JsonResponse
    {
        $this->checkPermission('newsletters');

        $document = Newsletter::getById((int) $request->get('id'));
        if (!$document) {
            throw $this->createNotFoundException('Newsletter not found');
        }
        $data = TmpStore::get($document->getTmpStoreId());

        return $this->adminJson([
            'data' => $data ? $data->getData() : null,
            'success' => true,
        ]);
    }

    /**
     * @Route("/stop-send", name="stopsend", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function stopSendAction(Request $request): JsonResponse
    {
        $this->checkPermission('newsletters');

        $document = Newsletter::getById((int) $request->get('id'));
        if (!$document) {
            throw $this->createNotFoundException('Newsletter not found');
        }
        TmpStore::delete($document->getTmpStoreId());

        return $this->adminJson([
            'success' => true,
        ]);
    }

    /**
     * @Route("/send", name="send", methods={"POST"})
     *
     * @param Request $request
     * @param MessageBusInterface $messengerBusPimcoreCore
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function sendAction(Request $request, MessageBusInterface $messengerBusPimcoreCore): JsonResponse
    {
        $this->checkPermission('newsletters');

        $document = Newsletter::getById((int) $request->get('id'));
        if (!$document) {
            throw $this->createNotFoundException('Newsletter not found');
        }

        if (TmpStore::get($document->getTmpStoreId())) {
            throw new RuntimeException('Newsletter sending already in progress, need to finish first.');
        }

        TmpStore::add($document->getTmpStoreId(), [
            'documentId' => $document->getId(),
            'addressSourceAdapterName' => $request->get('addressAdapterName'),
            'adapterParams' => json_decode($request->get('adapterParams'), true),
            'inProgress' => false,
            'progress' => 0,
        ], 'newsletter');

        $messengerBusPimcoreCore->dispatch(
            new Pimcore\Bundle\NewsletterBundle\Messenger\SendNewsletterMessage($document->getTmpStoreId(), \Pimcore\Tool::getHostUrl())
        );

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/calculate", name="calculate", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function calculateAction(Request $request): JsonResponse
    {
        $this->checkPermission('newsletters');

        $addressSourceAdapterName = $request->get('addressAdapterName');
        $adapterParams = json_decode($request->get('adapterParams'), true);
        $serviceLocator = \Pimcore::getContainer()->get('pimcore_newsletter.address_source_adapter.factories');

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
     * @Route("/send-test", name="sendtest", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function sendTestAction(Request $request): JsonResponse
    {
        $this->checkPermission('newsletters');

        $document = Newsletter::getById((int) $request->get('id'));
        if (!$document) {
            throw $this->createNotFoundException('Newsletter not found');
        }
        $addressSourceAdapterName = $request->get('addressAdapterName');
        $adapterParams = json_decode($request->get('adapterParams'), true);
        $testMailAddress = $request->get('testMailAddress');

        if (empty($testMailAddress)) {
            return $this->adminJson([
                'success' => false,
                'message' => 'Please provide a valid email address to send test newsletter',
            ]);
        }

        $serviceLocator = \Pimcore::getContainer()->get('pimcore_newsletter.address_source_adapter.factories');

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

        try {
            $mail = NewsletterTool::prepareMail($document);
            NewsletterTool::sendNewsletterDocumentBasedMail($mail, $sendingContainer);
        } catch (\Exception $e) {
            return $this->adminJson([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->adminJson(['success' => true]);
    }
}
