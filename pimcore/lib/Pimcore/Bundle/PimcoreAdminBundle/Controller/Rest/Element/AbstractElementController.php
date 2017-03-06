<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Rest\Element;

use Pimcore\Bundle\PimcoreAdminBundle\Controller\Rest\AbstractRestController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractElementController extends AbstractRestController
{
    /**
     * @param Request $request
     * @param string  $type
     *
     * @return JsonResponse
     */
    protected function inquire(Request $request, $type)
    {
        $table      = $type . 's';
        $permission = $type . 's'; // objects

        $this->checkPermission($permission);

        $condense = $request->get('condense');

        if ($request->getMethod() === 'POST') {
            $data   = $request->getContent();
            $idList = explode(',', $data);
        } elseif ($request->get('ids')) {
            $idList = explode(',', $request->get('ids'));
        } else {
            $idList = [];
        }

        if ($request->get('id')) {
            $idList[] = $request->get('id');
        }

        if (count($idList) === 0) {
            return $this->createErrorResponse('Missing list of IDs');
        }

        $idList = array_filter($idList, function($id) {
            return (int)$id;
        });

        $resultData = [];
        foreach ($idList as $id) {
            $resultData[$id] = 0;
        }

        if ($type === 'object') {
            $col = 'o_id';
        } else {
            $col = 'id';
        }

        $connection = $this->get('database_connection');
        $qb = $connection->createQueryBuilder();
        $qb
            ->select($col)
            ->from($table)
            ->where($qb->expr()->in($col, $idList));

        $result = $qb->execute()->fetchAll();

        foreach ($result as $item) {
            $id = $item[$col];

            if ($condense) {
                unset($resultData[$id]);
            } else {
                $resultData[$id] = 1;
            }
        }

        return $this->createSuccessResponse($resultData);
    }
}
