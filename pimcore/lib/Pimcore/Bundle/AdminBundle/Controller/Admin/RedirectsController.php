<?php

declare(strict_types=1);

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

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Model\Redirect;
use Pimcore\Routing\Redirect\Csv;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Route("/redirects")
 */
class RedirectsController extends AdminController
{
    /**
     * @Route("/csv-export")
     * @Method("GET")
     *
     * @param Request $request
     * @param Csv $csv
     *
     * @return Response
     */
    public function csvExportAction(Request $request, Csv $csv)
    {
        $this->checkPermission('redirects');

        $list = new Redirect\Listing();
        $list->setOrderKey('id');
        $list->setOrder('ASC');
        $list->load();

        $writer = $csv->createExportWriter($list);

        $response = new Response();
        $response->headers->set('Content-Encoding', 'none');
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'redirects.csv'
        ));

        $response->setContent($writer->getContent());

        return $response;
    }

    /**
     * @Route("/csv-import")
     * @Method("POST")
     *
     * @param Request $request
     * @param Csv $csv
     *
     * @return Response
     */
    public function csvImportAction(Request $request, Csv $csv)
    {
        $this->checkPermission('redirects');

        /** @var UploadedFile $file */
        $file = $request->files->get('redirects');

        if (!$file) {
            throw new BadRequestHttpException('Missing file');
        }

        $result = $csv->import($file->getRealPath());

        return $this->adminJson([
            'success' => true,
            'data'    => $result
        ]);
    }
}
