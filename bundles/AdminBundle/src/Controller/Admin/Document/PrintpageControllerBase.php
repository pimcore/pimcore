<?php

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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\Document;

use Pimcore\Config;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\Schedule\Task;
use Pimcore\Web2Print\Processor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
abstract class PrintpageControllerBase extends DocumentControllerBase
{
    /**
     * @Route("/get-data-by-id", name="getdatabyid", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getDataByIdAction(Request $request): JsonResponse
    {
        $page = Document\PrintAbstract::getById((int)$request->get('id'));

        if (!$page) {
            throw $this->createNotFoundException('Document not found');
        }

        if (($lock = $this->checkForLock($page)) instanceof JsonResponse) {
            return $lock;
        }

        $page = clone $page;
        $draftVersion = null;
        $page = $this->getLatestVersion($page, $draftVersion);

        $page->getVersions();

        // unset useless data
        $page->setEditables(null);
        $page->setChildren(null);

        $data = $page->getObjectVars();
        $data['locked'] = $page->isLocked();

        $this->addTranslationsData($page, $data);
        $this->minimizeProperties($page, $data);

        $data['url'] = $page->getUrl();
        $data['scheduledTasks'] = array_map(
            static function (Task $task) {
                return $task->getObjectVars();
            },
            $page->getScheduledTasks()
        );

        if ($page->getContentMasterDocument()) {
            $data['contentMasterDocumentPath'] = $page->getContentMasterDocument()->getRealFullPath();
        }

        return $this->preSendDataActions($data, $page, $draftVersion);
    }

    /**
     * @Route("/save", name="save", methods={"PUT", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws ValidationException
     */
    public function saveAction(Request $request): JsonResponse
    {
        $page = Document\PrintAbstract::getById((int) $request->get('id'));
        if (!$page) {
            throw $this->createNotFoundException('Document not found');
        }

        $page = $this->getLatestVersion($page);

        Document\Service::saveElementToSession($page);

        if ($request->get('task') !== self::TASK_SAVE) {
            //check, if to cleanup existing elements of document
            $config = Config::getWeb2PrintConfig();
            if ($config->get('generalDocumentSaveMode') == 'cleanup') {
                $page->setEditables([]);
            }
        }

        list($task, $page, $version) = $this->saveDocument($page, $request);

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
        } else {
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
    }

    /**
     * @param Request $request
     * @param Document $page
     */
    protected function setValuesToDocument(Request $request, Document $page): void
    {
        $this->addSettingsToDocument($request, $page);
        $this->addDataToDocument($request, $page);
        $this->addPropertiesToDocument($request, $page);
    }

    /**
     * @Route("/active-generate-process", name="activegenerateprocess", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function activeGenerateProcessAction(Request $request): JsonResponse
    {
        $document = Document\PrintAbstract::getById((int)$request->get('id'));

        if (!$document) {
            throw $this->createNotFoundException('Document with id ' . $request->get('id') . ' not found.');
        }

        $date = $document->getLastGeneratedDate();
        if ($date) {
            $date = $date->format('Y-m-d H:i');
        }

        $inProgress = $document->getInProgress();

        $statusUpdate = [];
        if ($inProgress) {
            $statusUpdate = Processor::getInstance()->getStatusUpdate($document->getId());
        }

        return $this->adminJson([
            'activeGenerateProcess' => !empty($inProgress),
            'date' => $date,
            'message' => $document->getLastGenerateMessage(),
            'downloadAvailable' => file_exists($document->getPdfFileName()),
            'statusUpdate' => $statusUpdate,
        ]);
    }

    /**
     * @Route("/pdf-download", name="pdfdownload", methods={"GET"})
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     *
     * @throws \Exception
     */
    public function pdfDownloadAction(Request $request): BinaryFileResponse
    {
        $document = Document\PrintAbstract::getById((int)$request->get('id'));

        if (!$document) {
            throw $this->createNotFoundException('Document with id ' . $request->get('id') . ' not found.');
        }

        if (file_exists($document->getPdfFileName())) {
            $response = new BinaryFileResponse($document->getPdfFileName());
            $response->headers->set('Content-Type', 'application/pdf');
            if ($request->get('download')) {
                $response->setContentDisposition('attachment', $document->getKey() . '.pdf');
            }

            return $response;
        } else {
            throw $this->createNotFoundException('File does not exist');
        }
    }

    /**
     * @Route("/start-pdf-generation", name="startpdfgeneration", methods={"POST"})
     *
     * @param Request $request
     * @param Config $config
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function startPdfGenerationAction(Request $request, Config $config): JsonResponse
    {
        $allParams = json_decode($request->getContent(), true);

        $document = Document\PrintAbstract::getById($allParams['id']);

        if (!$document) {
            throw $this->createNotFoundException('Document with id ' . $allParams['id'] . ' not found.');
        }

        if (empty($allParams['hostName'] = $config['general']['domain'])) {
            $allParams['hostName'] = $_SERVER['HTTP_HOST'];
        }

        $https = $_SERVER['HTTPS'] ?? 'off';
        $allParams['protocol'] = $https === 'on' ? 'https' : 'http';
        $pdf = $document->getPdfFileName();
        if (is_file($pdf)) {
            unlink($pdf);
        }

        $result = $document->generatePdf($allParams);

        $this->saveProcessingOptions($document->getId(), $allParams);

        return $this->adminJson(['success' => $result]);
    }

    /**
     * @Route("/check-pdf-dirty", name="checkpdfdirty", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function checkPdfDirtyAction(Request $request): JsonResponse
    {
        $printDocument = Document\PrintAbstract::getById((int) $request->get('id'));

        $dirty = true;
        if ($printDocument) {
            $dirty = $printDocument->pdfIsDirty();
        }

        return $this->adminJson(['pdfDirty' => $dirty]);
    }

    /**
     * @Route("/get-processing-options", name="getprocessingoptions", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getProcessingOptionsAction(Request $request): JsonResponse
    {
        $options = Processor::getInstance()->getProcessingOptions();

        $returnValue = [];

        $storedValues = $this->getStoredProcessingOptions($request->get('id'));

        foreach ($options as $option) {
            $value = $option['default'];
            if ($storedValues && array_key_exists($option['name'], $storedValues)) {
                $value = $storedValues[$option['name']];
            }

            $returnValue[] = [
                'name' => $option['name'],
                'label' => $option['name'],
                'value' => $value,
                'type' => $option['type'],
                'values' => isset($option['values']) ? $option['values'] : null,
            ];
        }

        return $this->adminJson(['options' => $returnValue]);
    }

    /**
     * @param int $documentId
     *
     * @return array|mixed
     */
    private function getStoredProcessingOptions($documentId): mixed
    {
        $filename = PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'web2print-processingoptions-' . $documentId . '_' . $this->getAdminUser()->getId() . '.psf';
        if (file_exists($filename)) {
            return \Pimcore\Tool\Serialize::unserialize(file_get_contents($filename));
        } else {
            return [];
        }
    }

    /**
     * @param int $documentId
     * @param array $options
     */
    private function saveProcessingOptions(int $documentId, array $options)
    {
        file_put_contents(PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'web2print-processingoptions-' . $documentId . '_' . $this->getAdminUser()->getId() . '.psf', \Pimcore\Tool\Serialize::serialize($options));
    }

    /**
     * @Route("/cancel-generation", name="cancelgeneration", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function cancelGenerationAction(Request $request): JsonResponse
    {
        Processor::getInstance()->cancelGeneration((int)$request->get('id'));

        return $this->adminJson(['success' => true]);
    }
}
