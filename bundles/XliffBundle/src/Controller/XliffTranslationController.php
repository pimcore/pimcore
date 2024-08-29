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

namespace Pimcore\Bundle\XliffBundle\Controller;

use Exception;
use Pimcore\Bundle\XliffBundle\ExportService\Exporter\ExporterInterface;
use Pimcore\Bundle\XliffBundle\ExportService\ExportServiceInterface;
use Pimcore\Bundle\XliffBundle\ImportDataExtractor\ImportDataExtractorInterface;
use Pimcore\Bundle\XliffBundle\ImporterService\ImporterServiceInterface;
use Pimcore\Bundle\XliffBundle\TranslationItemCollection\TranslationItemCollection;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Logger;
use Pimcore\Model\Element;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/translation")
 *
 */
class XliffTranslationController extends UserAwareController
{
    use JsonHelperTrait;

    /**
     * @Route("/xliff-export", name="pimcore_bundle_xliff_translation_xliffexport", methods={"POST"})
     *
     * @throws Exception
     */
    public function xliffExportAction(Request $request, ExportServiceInterface $exportService): JsonResponse
    {
        $this->checkPermission('xliff_import_export');

        $id = $request->get('id');
        $data = $this->decodeJson($request->get('data'));
        $source = $request->get('source');
        $target = $request->get('target');

        $translationItems = new TranslationItemCollection();

        foreach ($data as $el) {
            $element = Element\Service::getElementById($el['type'], $el['id']);
            $translationItems->addPimcoreElement($element);
        }

        $exportService->exportTranslationItems($translationItems, $source, [$target], $id);

        return $this->jsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @Route("/xliff-export-download", name="pimcore_bundle_xliff_translation_exportdownload", methods={"GET"})
     *
     *
     */
    public function xliffExportDownloadAction(Request $request, ExporterInterface $translationExporter, ExportServiceInterface $exportService): BinaryFileResponse
    {
        $this->checkPermission('xliff_import_export');

        $id = $request->get('id');
        $exportFile = $exportService->getTranslationExporter()->getExportFilePath($id);

        $response = new BinaryFileResponse($exportFile);
        $response->headers->set('Content-Type', $translationExporter->getContentType());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($exportFile));
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @Route("/xliff-import-upload", name="pimcore_bundle_xliff_translation_xliffimportupload", methods={"POST"})
     *
     * @throws Exception
     */
    public function xliffImportUploadAction(Request $request, ImportDataExtractorInterface $importDataExtractor): JsonResponse
    {
        $this->checkPermission('xliff_import_export');

        $jobs = [];
        $id = uniqid();
        $importFile = $importDataExtractor->getImportFilePath($id);
        copy($_FILES['file']['tmp_name'], $importFile);

        $steps = $importDataExtractor->countSteps($id);

        for ($i = 0; $i < $steps; $i++) {
            $jobs[] = [[
                'url' => $this->generateUrl('pimcore_bundle_xliff_translation_xliffimportelement'),
                'method' => 'POST',
                'params' => [
                    'id' => $id,
                    'step' => $i,
                ],
            ]];
        }

        $response = $this->jsonResponse([
            'success' => true,
            'jobs' => $jobs,
            'id' => $id,
        ]);
        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/xliff-import-element", name="pimcore_bundle_xliff_translation_xliffimportelement", methods={"POST"})
     *
     * @throws Exception
     */
    public function xliffImportElementAction(Request $request, ImportDataExtractorInterface $importDataExtractor, ImporterServiceInterface $importerService): JsonResponse
    {
        $this->checkPermission('xliff_import_export');

        $id = $request->get('id');
        $step = (int) $request->get('step');

        try {
            $attributeSet = $importDataExtractor->extractElement($id, $step);
            if ($attributeSet) {
                $importerService->import($attributeSet);
            } else {
                Logger::warning(sprintf('Could not resolve element %s', $id));
            }
        } catch (Exception $e) {
            Logger::err($e->getMessage());

            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->jsonResponse([
            'success' => true,
        ]);
    }
}
