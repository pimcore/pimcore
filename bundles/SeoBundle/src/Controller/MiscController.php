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

namespace Pimcore\Bundle\SeoBundle\Controller;

use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Db;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Annotation\Route;

class MiscController extends UserAwareController
{
    use JsonHelperTrait;

    /**
     * @Route("/http-error-log", name="pimcore_bundle_seo_misc_httperrorlog", methods={"POST"})
     *
     *
     */
    public function httpErrorLogAction(Request $request): JsonResponse
    {
        $this->checkPermission('http_errors');

        $db = Db::get();

        $limit = $request->request->getInt('limit');
        $offset = $request->request->getInt('start');
        $sortInfo = ($request->request->has('sort') ? json_decode($request->request->getString('sort'), true)[0] : []);
        $sort = $sortInfo['property'] ?? null;
        $dir = $sortInfo['direction'] ?? null;
        $filter = $request->request->getString('filter');
        if (!$limit) {
            $limit = 20;
        }
        if (!$offset) {
            $offset = 0;
        }
        if (!$sort || !in_array($sort, ['code', 'uri', 'date', 'count'])) {
            $sort = 'count';
        }
        if (!$dir || !in_array($dir, ['DESC', 'ASC'])) {
            $dir = 'DESC';
        }

        $condition = '';
        if ($filter) {
            $filter = $db->quote('%' . $filter . '%');

            $conditionParts = [];
            foreach (['uri', 'code', 'parametersGet', 'parametersPost', 'serverVars', 'cookies'] as $field) {
                $conditionParts[] = $field . ' LIKE ' . $filter;
            }
            $condition = ' WHERE ' . implode(' OR ', $conditionParts);
        }

        $logs = $db->fetchAllAssociative('SELECT code,uri,`count`,date FROM http_error_log ' . $condition . ' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit);
        $total = $db->fetchOne('SELECT count(*) FROM http_error_log ' . $condition);

        return $this->jsonResponse([
            'items' => $logs,
            'total' => $total,
            'success' => true,
        ]);
    }

    /**
     * @Route("/http-error-log-detail", name="pimcore_bundle_seo_misc_httperrorlogdetail", methods={"GET"})
     *
     *
     */
    public function httpErrorLogDetailAction(Request $request, ?Profiler $profiler): Response
    {
        $this->checkPermission('http_errors');

        if ($profiler) {
            $profiler->disable();
        }

        $db = Db::get();
        $data = $db->fetchAssociative('SELECT * FROM http_error_log WHERE uri = ?', [$request->query->getString('uri')]);

        foreach ($data as $key => &$value) {
            if (in_array($key, ['parametersGet', 'parametersPost', 'serverVars', 'cookies'])) {
                $value = unserialize($value);
            }
        }

        return $this->render('@PimcoreSeo/misc/http_error_log_detail.html.twig', ['data' => $data]);
    }

    /**
     * @Route("/http-error-log-flush", name="pimcore_bundle_seo_misc_httperrorlogflush", methods={"DELETE"})
     *
     *
     */
    public function httpErrorLogFlushAction(Request $request): JsonResponse
    {
        $this->checkPermission('http_errors');

        $db = Db::get();
        $db->executeQuery('TRUNCATE TABLE http_error_log');

        return $this->jsonResponse([
            'success' => true,
        ]);
    }
}
