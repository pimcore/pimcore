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

use Pimcore\Config;
use Pimcore\Controller\Traits\ElementEditLockHelperTrait;
use Pimcore\Event\Admin\ElementAdminStyleEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Model\Document;
use Pimcore\Model\Element\Service;
use Pimcore\Web2Print\Processor;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PrintpageControllerBase extends DocumentControllerBase
{
    use ElementEditLockHelperTrait;

    /**
     * @Route("/get-data-by-id", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request)
    {
        $page = Document\PrintAbstract::getById($request->get('id'));

        // check for lock
        if ($page->isAllowed('save') || $page->isAllowed('publish') || $page->isAllowed('unpublish') || $page->isAllowed('delete')) {
            if (\Pimcore\Model\Element\Editlock::isLocked($request->get('id'), 'document')) {
                return $this->getEditLockResponse($request->get('id'), 'document');
            }
            \Pimcore\Model\Element\Editlock::lock($request->get('id'), 'document');
        }

        $page = $this->getLatestVersion($page);

        $page->getVersions();
        $page->getScheduledTasks();
        $page->idPath = Service::getIdPath($page);
        $page->setUserPermissions($page->getUserPermissions());
        $page->setLocked($page->isLocked());
        $page->url = $page->getUrl();

        if ($page->getContentMasterDocument()) {
            $page->contentMasterDocumentPath = $page->getContentMasterDocument()->getRealFullPath();
        }

        $this->addTranslationsData($page);

        // unset useless data
        $page->setElements(null);
        $page->setChildren(null);

        // cleanup properties
        $this->minimizeProperties($page);

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $data = $page->getObjectVars();

        $data['php'] = [
            'classes' => array_merge([get_class($page)], array_values(class_parents($page))),
            'interfaces' => array_values(class_implements($page))
        ];

        $this->addAdminStyle($page, ElementAdminStyleEvent::CONTEXT_EDITOR, $data);

        $event = new GenericEvent($this, [
            'data' => $data,
            'document' => $page
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_GET_PRE_SEND_DATA, $event);

        if ($page->isAllowed('view')) {
            $data = $event->getArgument('data');

            return $this->adminJson($data);
        }

        throw $this->createAccessDeniedHttpException();
    }

    /**
     * @Route("/save", methods={"PUT", "POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function saveAction(Request $request)
    {
        if ($request->get('id')) {
            $page = Document\PrintAbstract::getById($request->get('id'));

            $page = $this->getLatestVersion($page);
            $page->setUserModification($this->getAdminUser()->getId());

            // save to session
            $key = 'document_' . $request->get('id');

            Document\Service::saveElementToSession($page);

            if ($request->get('task') == 'unpublish') {
                $page->setPublished(false);
            }
            if ($request->get('task') == 'publish') {
                $page->setPublished(true);
            }

            // only save when publish or unpublish
            if (($request->get('task') == 'publish' && $page->isAllowed('publish')) || ($request->get('task') == 'unpublish' && $page->isAllowed('unpublish'))) {

                //check, if to cleanup existing elements of document
                $config = Config::getWeb2PrintConfig();
                if ($config->generalDocumentSaveMode == 'cleanup') {
                    $page->setElements([]);
                }

                $this->setValuesToDocument($request, $page);

                $page->save();

                $this->addAdminStyle($page, ElementAdminStyleEvent::CONTEXT_EDITOR, $treeData);

                return $this->adminJson([
                    'success' => true,
                    'data' => [
                        'versionDate' => $page->getModificationDate(),
                        'versionCount' => $page->getVersionCount()
                    ],
                    'treeData' => $treeData
                ]);
            } elseif ($page->isAllowed('save')) {
                $this->setValuesToDocument($request, $page);
                $page->saveVersion();

                return $this->adminJson(['success' => true]);
            } else {
                throw $this->createAccessDeniedHttpException();
            }
        }

        throw $this->createNotFoundException();
    }

    /**
     * @param Request $request
     * @param Document\PrintAbstract $page
     */
    protected function setValuesToDocument(Request $request, Document $page)
    {
        $this->addSettingsToDocument($request, $page);
        $this->addDataToDocument($request, $page);
        $this->addPropertiesToDocument($request, $page);
    }

    /**
     * @Route("/active-generate-process", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function activeGenerateProcessAction(Request $request)
    {
        /** @var Document\Printpage $document */
        $document = Document\PrintAbstract::getById(intval($request->get('id')));
        if (empty($document)) {
            throw new \Exception('Document with id ' . $request->get('id') . ' not found.');
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
            'statusUpdate' => $statusUpdate
        ]);
    }

    /**
     * @Route("/pdf-download", methods={"GET"})
     *
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return BinaryFileResponse
     */
    public function pdfDownloadAction(Request $request)
    {
        $document = Document\PrintAbstract::getById(intval($request->get('id')));
        if (empty($document)) {
            throw new \Exception('Document with id ' . $request->get('id') . ' not found.');
        }

        if (file_exists($document->getPdfFileName())) {
            $response = new BinaryFileResponse($document->getPdfFileName());
            $response->headers->set('Content-Type', 'application/pdf');
            if ($request->get('download')) {
                $response->setContentDisposition('attachment', $document->getKey() . '.pdf');
            }

            return $response;
        } else {
            throw new \Exception('File does not exist');
        }
    }

    /**
     * @Route("/start-pdf-generation", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function startPdfGenerationAction(Request $request)
    {
        $allParams = json_decode($request->getContent(), true);

        $document = Document\PrintAbstract::getById($allParams['id']);
        if (empty($document)) {
            throw new \Exception('Document with id ' . $allParams['id'] . ' not found.');
        }

        if (\Pimcore\Config::getSystemConfig()->general->domain) {
            $allParams['hostName'] = \Pimcore\Config::getSystemConfig()->general->domain;
        } else {
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
     * @Route("/check-pdf-dirty", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function checkPdfDirtyAction(Request $request)
    {
        $printDocument = Document\PrintAbstract::getById($request->get('id'));

        $dirty = true;
        if ($printDocument) {
            $dirty = $printDocument->pdfIsDirty();
        }

        return $this->adminJson(['pdfDirty' => $dirty]);
    }

    /**
     * @Route("/get-processing-options", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getProcessingOptionsAction(Request $request)
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
                'values' => isset($option['values']) ? $option['values'] : null
            ];
        }

        return $this->adminJson(['options' => $returnValue]);
    }

    /**
     * @param int $documentId
     *
     * @return array|mixed
     */
    private function getStoredProcessingOptions($documentId)
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
    private function saveProcessingOptions($documentId, $options)
    {
        file_put_contents(PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'web2print-processingoptions-' . $documentId . '_' . $this->getAdminUser()->getId() . '.psf', \Pimcore\Tool\Serialize::serialize($options));
    }

    /**
     * @Route("/cancel-generation", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function cancelGenerationAction(Request $request)
    {
        Processor::getInstance()->cancelGeneration(intval($request->get('id')));

        return $this->adminJson(['success' => true]);
    }
}
