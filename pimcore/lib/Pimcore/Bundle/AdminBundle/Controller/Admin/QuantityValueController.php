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
 * @category   Pimcore
 * @package    Object|Class
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Model\Object\QuantityValue\Unit;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class QuantityValueController extends AdminController
{
    /**
     * @Route("/quantity-value/unit-proxy")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function unitProxyAction(Request $request)
    {
        if ($request->get('data')) {
            if ($request->get('xaction') == 'destroy') {
                $data = json_decode($request->get('data'), true);
                $id = $data['id'];
                $unit = \Pimcore\Model\Object\QuantityValue\Unit::getById($id);
                if (!empty($unit)) {
                    $unit->delete();

                    return $this->json(['data' => [], 'success' => true]);
                } else {
                    throw new \Exception('Unit with id ' . $id . ' not found.');
                }
            } elseif ($request->get('xaction') == 'update') {
                $data = json_decode($request->get('data'), true);
                $unit = Unit::getById($data['id']);
                if (!empty($unit)) {
                    $unit->setValues($data);
                    $unit->save();

                    return $this->json(['data' => get_object_vars($unit), 'success' => true]);
                } else {
                    throw new \Exception('Unit with id ' . $data['id'] . ' not found.');
                }
            } elseif ($request->get('xaction') == 'create') {
                $data = json_decode($request->get('data'), true);
                unset($data['id']);
                $unit = new Unit();
                $unit->setValues($data);
                $unit->save();

                return $this->json(['data' => get_object_vars($unit), 'success' => true]);
            }
        } else {
            $list = new Unit\Listing();

            $orderKey = 'abbreviation';
            $order = 'asc';

            $allParams = array_merge($request->request->all(), $request->query->all());
            $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($allParams);
            if ($sortingSettings['orderKey']) {
                $orderKey = $sortingSettings['orderKey'];
            }
            if ($sortingSettings['order']) {
                $order  = $sortingSettings['order'];
            }

            $list->setOrder($order);
            $list->setOrderKey($orderKey);

            $list->setLimit($request->get('limit'));
            $list->setOffset($request->get('start'));

            $condition = '1 = 1';
            if ($request->get('filter')) {
                $filterString = $request->get('filter');
                $filters = json_decode($filterString);
                $db = \Pimcore\Db::get();
                foreach ($filters as $f) {
                    if ($f->type == 'string') {
                        $condition .= ' AND ' . $db->quoteIdentifier($f->property) . ' LIKE ' . $db->quote('%' . $f->value . '%');
                    } elseif ($f->type == 'numeric') {
                        $operator = $this->getOperator($f->comparison);
                        $condition .= ' AND ' . $db->quoteIdentifier($f->property) . ' ' . $operator . ' ' . $db->quote($f->value);
                    }
                }
                $list->setCondition($condition);
            }
            $list->load();

            $units = [];
            foreach ($list->getUnits() as $u) {
                $units[] = get_object_vars($u);
            }

            return $this->json(['data' => $units, 'success' => true, 'total' => $list->getTotalCount()]);
        }
    }

    /**
     * @param $comparison
     *
     * @return mixed
     */
    private function getOperator($comparison)
    {
        $mapper = [
            'lt' => '<',
            'gt' => '>',
            'eq' => '='
        ];

        return $mapper[$comparison];
    }

    /**
     * @Route("/quantity-value/unit-list")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function unitListAction(Request $request)
    {
        $list = new Unit\Listing();
        $list->setOrderKey('abbreviation');
        $list->setOrder('ASC');
        if ($request->get('filter')) {
            $array = explode(',', $request->get('filter'));
            $quotedArray = [];
            $db = \Pimcore\Db::get();
            foreach ($array as $a) {
                $quotedArray[] = $db->quote($a);
            }
            $string = implode(',', $quotedArray);
            $list->setCondition('id IN (' . $string . ')');
        }

        $units = $list->getUnits();

        return $this->json(['data' => $units, 'success' => true, 'total' => $list->getTotalCount()]);
    }
}
