<?php

namespace Pimcore\Bundle\SeoBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Db;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Annotation\Route;

class MiscController extends AdminController
{
    /**
     * @Route("/http-error-log", name="pimcore_seo_misc_httperrorlog", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function httpErrorLogAction(Request $request): JsonResponse
    {
        $this->checkPermission('http_errors');

        $db = Db::get();

        $limit = (int)$request->get('limit');
        $offset = (int)$request->get('start');
        $sortInfo = ($request->get('sort') ? json_decode($request->get('sort'), true)[0] : []);
        $sort = $sortInfo['property'] ?? null;
        $dir = $sortInfo['direction'] ?? null;
        $filter = $request->get('filter');
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

        return $this->adminJson([
            'items' => $logs,
            'total' => $total,
            'success' => true,
        ]);
    }

    /**
     * @Route("/http-error-log-detail", name="pimcore_seo_misc_httperrorlogdetail", methods={"GET"})
     *
     * @param Request $request
     * @param Profiler|null $profiler
     *
     * @return Response
     */
    public function httpErrorLogDetailAction(Request $request, ?Profiler $profiler): Response
    {
        $this->checkPermission('http_errors');

        if ($profiler) {
            $profiler->disable();
        }

        $db = Db::get();
        $data = $db->fetchAssociative('SELECT * FROM http_error_log WHERE uri = ?', [$request->get('uri')]);

        foreach ($data as $key => &$value) {
            if (in_array($key, ['parametersGet', 'parametersPost', 'serverVars', 'cookies'])) {
                $value = unserialize($value);
            }
        }

        $response = $this->render('@Seo/admin/misc/http_error_log_detail.html.twig', ['data' => $data]);

        return $response;
    }

    /**
     * @Route("/http-error-log-flush", name="pimcore_seo_misc_httperrorlogflush", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function httpErrorLogFlushAction(Request $request): JsonResponse
    {
        $this->checkPermission('http_errors');

        $db = Db::get();
        $db->executeQuery('TRUNCATE TABLE http_error_log');

        return $this->adminJson([
            'success' => true,
        ]);
    }
}
