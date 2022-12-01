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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Model\Element;
use Pimcore\Model\Element\Recyclebin;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
class RecyclebinController extends AdminController implements KernelControllerEventInterface
{
    /**
     * @Route("/recyclebin/list", name="pimcore_admin_recyclebin_list", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function listAction(Request $request): JsonResponse
    {
        if ($request->get('xaction') == 'destroy') {
            $item = Recyclebin\Item::getById(\Pimcore\Bundle\AdminBundle\Helper\QueryParams::getRecordIdForGridRequest($request->get('data')));

            if ($item) {
                $item->delete();
            }

            return $this->adminJson(['success' => true, 'data' => []]);
        } else {
            $db = \Pimcore\Db::get();

            $list = new Recyclebin\Item\Listing();
            $list->setLimit((int) $request->get('limit', 50));
            $list->setOffset((int) $request->get('start', 0));

            $list->setOrderKey('date');
            $list->setOrder('DESC');

            $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
            if ($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            }

            $conditionFilters = [];

            if ($request->get('filterFullText')) {
                $conditionFilters[] = 'path LIKE ' . $list->quote('%'. $list->escapeLike($request->get('filterFullText')) .'%');
            }

            $filters = $request->get('filter');
            if ($filters) {
                $filters = $this->decodeJson($filters);

                foreach ($filters as $filter) {
                    $operator = '=';

                    $filterField = $filter['property'];
                    $filterOperator = $filter['operator'];

                    if ($filter['type'] == 'string') {
                        $operator = 'LIKE';
                    } elseif ($filter['type'] == 'numeric') {
                        if ($filterOperator == 'lt') {
                            $operator = '<';
                        } elseif ($filterOperator == 'gt') {
                            $operator = '>';
                        } elseif ($filterOperator == 'eq') {
                            $operator = '=';
                        }
                    } elseif ($filter['type'] == 'date') {
                        if ($filterOperator == 'lt') {
                            $operator = '<';
                        } elseif ($filterOperator == 'gt') {
                            $operator = '>';
                        } elseif ($filterOperator == 'eq') {
                            $operator = '=';
                        }
                        $filter['value'] = strtotime($filter['value']);
                    } elseif ($filter['type'] == 'list') {
                        $operator = '=';
                    } elseif ($filter['type'] == 'boolean') {
                        $operator = '=';
                        $filter['value'] = (int) $filter['value'];
                    }
                    // system field
                    $value = ($filter['value'] ?? '');
                    if ($operator == 'LIKE') {
                        $value = '%' . $value . '%';
                    }

                    $field = $db->quoteIdentifier($filterField);
                    if (($filter['field'] ?? false) == 'fullpath') {
                        $field = 'CONCAT(path,filename)';
                    }

                    if ($filter['type'] == 'date' && $operator == '=') {
                        $maxTime = $value + (86400 - 1); //specifies the top point of the range used in the condition
                        $condition = $field . ' BETWEEN ' . $db->quote($value) . ' AND ' . $db->quote($maxTime);
                        $conditionFilters[] = $condition;
                    } else {
                        $conditionFilters[] = $field . $operator . ' ' . $db->quote($value);
                    }
                }
            }

            if (!empty($conditionFilters)) {
                $condition = implode(' AND ', $conditionFilters);
                $list->setCondition($condition);
            }

            $items = $list->load();
            $data = [];
            if (is_array($items)) {
                foreach ($items as $item) {
                    $data[] = $item->getObjectVars();
                }
            }

            return $this->adminJson(['data' => $data, 'success' => true, 'total' => $list->getTotalCount()]);
        }
    }

    /**
     * @Route("/recyclebin/restore", name="pimcore_admin_recyclebin_restore", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function restoreAction(Request $request): JsonResponse
    {
        $item = Recyclebin\Item::getById((int) $request->get('id'));
        if (!$item) {
            throw $this->createNotFoundException();
        }
        $item->restore();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/recyclebin/flush", name="pimcore_admin_recyclebin_flush", methods={"DELETE"})
     *
     * @return JsonResponse
     */
    public function flushAction(): JsonResponse
    {
        $bin = new Element\Recyclebin();
        $bin->flush();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/recyclebin/add", name="pimcore_admin_recyclebin_add", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAction(Request $request): JsonResponse
    {
        try {
            $element = Element\Service::getElementById($request->get('type'), $request->get('id'));

            if ($element) {
                $list = $element::getList(['unpublished' => true]);
                $list->setCondition((($request->get('type') === 'object') ? 'o_' : '') . 'path LIKE ' . $list->quote($list->escapeLike($element->getRealFullPath()) . '/%'));
                $children = $list->getTotalCount();

                if ($children <= 100) {
                    Recyclebin\Item::create($element, $this->getAdminUser());
                }
            }
        } catch (\Exception $e) {
            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
        }

        return $this->adminJson(['success' => true]);
    }

    public function onKernelControllerEvent(ControllerEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // recyclebin actions might take some time (save & restore)
        $timeout = 600; // 10 minutes
        @ini_set('max_execution_time', (string) $timeout);
        set_time_limit($timeout);

        // check permissions
        $this->checkActionPermission($event, 'recyclebin', ['addAction']);
    }
}
