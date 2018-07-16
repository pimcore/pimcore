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
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Model\Document;
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
     * @Route("/list")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function redirectsAction(Request $request)
    {
        if ($request->get('data')) {
            $this->checkPermission('redirects');

            if ($request->get('xaction') == 'destroy') {
                $data = $this->decodeJson($request->get('data'));

                $id = $data['id'] ?? null;
                if ($id) {
                    $redirect = Redirect::getById($id);
                    $redirect->delete();
                }

                return $this->adminJson(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $data = $this->decodeJson($request->get('data'));

                // save redirect
                $redirect = Redirect::getById($data['id']);

                if ($data['target']) {
                    if ($doc = Document::getByPath($data['target'])) {
                        $data['target'] = $doc->getId();
                    }
                }

                if (!$data['regex'] && $data['source']) {
                    $data['source'] = str_replace('+', ' ', $data['source']);
                }

                $redirect->setValues($data);

                $redirect->save();

                $redirectTarget = $redirect->getTarget();
                if (is_numeric($redirectTarget)) {
                    if ($doc = Document::getById(intval($redirectTarget))) {
                        $redirect->setTarget($doc->getRealFullPath());
                    }
                }

                return $this->adminJson(['data' => $redirect, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                $data = $this->decodeJson($request->get('data'));
                unset($data['id']);

                // save route
                $redirect = new Redirect();

                if ($data['target']) {
                    if ($doc = Document::getByPath($data['target'])) {
                        $data['target'] = $doc->getId();
                    }
                }

                if (!$data['regex'] && $data['source']) {
                    $data['source'] = str_replace('+', ' ', $data['source']);
                }

                $redirect->setValues($data);

                $redirect->save();

                $redirectTarget = $redirect->getTarget();
                if (is_numeric($redirectTarget)) {
                    if ($doc = Document::getById(intval($redirectTarget))) {
                        $redirect->setTarget($doc->getRealFullPath());
                    }
                }

                return $this->adminJson(['data' => $redirect, 'success' => true]);
            }
        } else {
            // get list of routes

            $list = new Redirect\Listing();
            $list->setLimit($request->get('limit'));
            $list->setOffset($request->get('start'));

            $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
            if ($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            }

            if ($filterValue = $request->get('filter')) {
                if (is_numeric($filterValue)) {
                    $list->setCondition('id = ?', [$filterValue]);
                } else {
                    $list->setCondition('`source` LIKE ' . $list->quote('%' . $filterValue . '%') . ' OR `target` LIKE ' . $list->quote('%' . $filterValue . '%'));
                }
            }

            $list->load();

            $redirects = [];
            foreach ($list->getRedirects() as $redirect) {
                if ($link = $redirect->getTarget()) {
                    if (is_numeric($link)) {
                        if ($doc = Document::getById(intval($link))) {
                            $redirect->setTarget($doc->getRealFullPath());
                        }
                    }
                }

                $redirects[] = $redirect;
            }

            return $this->adminJson(['data' => $redirects, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->adminJson(false);
    }

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
