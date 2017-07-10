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

use Pimcore\Config;
use Pimcore\Event\AdminEvents;
use Pimcore\Logger;
use Pimcore\Model\Document;
use Pimcore\Model\Element\Service;
use Pimcore\Tool\Session;
use Pimcore\Web2Print\Processor;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Routing\Annotation\Route;

class PrintpageControllerBase extends DocumentControllerBase
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
        if (\Pimcore\Model\Element\Editlock::isLocked($request->get('id'), 'document')) {
            return $this->json([
                'editlock' => \Pimcore\Model\Element\Editlock::getByElement($request->get('id'), 'document')
            ]);
        }
        \Pimcore\Model\Element\Editlock::lock($request->get('id'), 'document');

        $page = Document\Printpage::getById($request->get('id'));
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
        $data = object2array($page);
        $event = new GenericEvent($this, [
            'data' => $data,
            'document' => $page
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::DOCUMENT_GET_PRE_SEND_DATA, $event);

        if ($page->isAllowed('view')) {
            $data = $event->getArgument('data');

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
     */
    public function saveAction(Request $request)
    {
        if ($request->get('id')) {
            $page = Document\Printpage::getById($request->get('id'));

            $page = $this->getLatestVersion($page);
            $page->setUserModification($this->getUser()->getId());

            // save to session
            $key = 'document_' . $request->get('id');

            Session::useSession(function (AttributeBagInterface $session) use ($key, $page) {
                $session->set($key, $page);
            }, 'pimcore_documents');

            if ($request->get('task') == 'unpublish') {
                $page->setPublished(false);
            }
            if ($request->get('task') == 'publish') {
                $page->setPublished(true);
            }

            // only save when publish or unpublish
            if (($request->get('task') == 'publish' && $page->isAllowed('publish')) or ($request->get('task') == 'unpublish' && $page->isAllowed('unpublish'))) {

                //check, if to cleanup existing elements of document
                $config = Config::getWeb2PrintConfig();
                if ($config->generalDocumentSaveMode == 'cleanup') {
                    $page->setElements([]);
                }

                $this->setValuesToDocument($request, $page);

                try {
                    $page->save();

                    return $this->json(['success' => true]);
                } catch (\Exception $e) {
                    Logger::err($e);

                    return $this->json(['success' => false, 'message'=>$e->getMessage()]);
                }
            } else {
                if ($page->isAllowed('save')) {
                    $this->setValuesToDocument($request, $page);

                    try {
                        $page->saveVersion();

                        return $this->json(['success' => true]);
                    } catch (\Exception $e) {
                        Logger::err($e);

                        return $this->json(['success' => false, 'message'=>$e->getMessage()]);
                    }
                }
            }
        }

        return $this->json(false);
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
     * @Route("/active-generate-process")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function activeGenerateProcessAction(Request $request)
    {
        /**
         * @var $document Document\Printpage
         */
        $document = Document\Printpage::getById(intval($request->get('id')));
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

        return $this->json([
            'activeGenerateProcess' => !empty($inProgress),
            'date' => $date,
            'message' => $document->getLastGenerateMessage(),
            'downloadAvailable' => file_exists($document->getPdfFileName()),
            'statusUpdate' => $statusUpdate
        ]);
    }

    /**
     * @Route("/pdf-download")
     *
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return BinaryFileResponse
     */
    public function pdfDownloadAction(Request $request)
    {
        $document = Document\Printpage::getById(intval($request->get('id')));
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
     * @Route("/start-pdf-generation")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function startPdfGenerationAction(Request $request)
    {
        $document = Document\Printpage::getById(intval($request->get('id')));
        if (empty($document)) {
            throw new \Exception('Document with id ' . $request->get('id') . ' not found.');
        }

        $allParams = array_merge($request->request->all(), $request->query->all());

        if (\Pimcore\Config::getSystemConfig()->general->domain) {
            $allParams['hostName'] = \Pimcore\Config::getSystemConfig()->general->domain;
        } else {
            $allParams['hostName'] = $_SERVER['HTTP_HOST'];
        }

        $allParams['protocol'] = $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
        $pdf = $document->getPdfFileName();
        if(is_file($pdf)){
            unlink($pdf);
        }

        $result = (bool)$document->generatePdf($allParams);

        $this->saveProcessingOptions($document->getId(), $allParams);

        return $this->json(['success' => $result]);
    }

    /**
     * @Route("/check-pdf-dirty")
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

        return $this->json(['pdfDirty' => $dirty]);
    }

    /**
     * @Route("/get-processing-options")
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
                'values' => $option['values']
            ];
        }

        return $this->json(['options' => $returnValue]);
    }

    /**
     * @param $documentId
     *
     * @return array|mixed
     */
    private function getStoredProcessingOptions($documentId)
    {
        $filename = PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'web2print-processingoptions-' . $documentId . '_' . $this->getUser()->getId() . '.psf';
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
        file_put_contents(PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'web2print-processingoptions-' . $documentId . '_' . $this->getUser()->getId() . '.psf', \Pimcore\Tool\Serialize::serialize($options));
    }

    /**
     * @Route("/cancel-generation")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function cancelGenerationAction(Request $request)
    {
        Processor::getInstance()->cancelGeneration(intval($request->get('id')));

        return $this->json(['success' => true]);
    }
}
