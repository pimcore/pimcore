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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/printpage")
 */
class PrintpageController extends PrintpageControllerBase
{
    /**
     * @Route("/save-to-session", name="pimcore_admin_document_printpage_savetosession", methods={"POST"})
     *
     * {@inheritDoc}
     */
    public function saveToSessionAction(Request $request)
    {
        return parent::saveToSessionAction($request);
    }

    /**
     * @Route("/remove-from-session", name="pimcore_admin_document_printpage_removefromsession", methods={"DELETE"})
     *
     * {@inheritDoc}
     */
    public function removeFromSessionAction(Request $request)
    {
        return parent::removeFromSessionAction($request);
    }

    /**
     * @Route("/change-master-document", name="pimcore_admin_document_printpage_changemasterdocument", methods={"PUT"})
     *
     * {@inheritDoc}
     */
    public function changeMasterDocumentAction(Request $request)
    {
        return parent::changeMasterDocumentAction($request);
    }

    /**
     * @Route("/get-data-by-id", name="pimcore_admin_document_printpage_getdatabyid", methods={"GET"})
     *
     * {@inheritDoc}
     */
    public function getDataByIdAction(Request $request)
    {
        return parent::getDataByIdAction($request);
    }

    /**
     * @Route("/save", name="pimcore_admin_document_printpage_save", methods={"PUT", "POST"})
     *
     * {@inheritDoc}
     */
    public function saveAction(Request $request)
    {
        return parent::saveAction($request);
    }

    /**
     * @Route("/active-generate-process", name="pimcore_admin_document_printpage_activegenerateprocess", methods={"POST"})
     *
     * {@inheritDoc}
     */
    public function activeGenerateProcessAction(Request $request)
    {
        return parent::activeGenerateProcessAction($request);
    }

    /**
     * @Route("/pdf-download", name="pimcore_admin_document_printpage_pdfdownload", methods={"GET"})
     *
     * {@inheritDoc}
     */
    public function pdfDownloadAction(Request $request)
    {
        return parent::pdfDownloadAction($request);
    }

    /**
     * @Route("/start-pdf-generation", name="pimcore_admin_document_printpage_startpdfgeneration", methods={"POST"})
     *
     * {@inheritDoc}
     */
    public function startPdfGenerationAction(Request $request, Config $config)
    {
        return parent::startPdfGenerationAction($request, $config);
    }

    /**
     * @Route("/check-pdf-dirty", name="pimcore_admin_document_printpage_checkpdfdirty", methods={"GET"})
     *
     * {@inheritDoc}
     */
    public function checkPdfDirtyAction(Request $request)
    {
        return parent::checkPdfDirtyAction($request);
    }

    /**
     * @Route("/get-processing-options", name="pimcore_admin_document_printpage_getprocessingoptions", methods={"GET"})
     *
     * {@inheritDoc}
     */
    public function getProcessingOptionsAction(Request $request)
    {
        return parent::getProcessingOptionsAction($request);
    }

    /**
     * @Route("/cancel-generation", name="pimcore_admin_document_printpage_cancelgeneration", methods={"DELETE"})
     *
     * {@inheritDoc}
     */
    public function cancelGenerationAction(Request $request)
    {
        return parent::cancelGenerationAction($request);
    }
}
