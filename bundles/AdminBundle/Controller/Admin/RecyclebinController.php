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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminAbstractController;
use Pimcore\Controller\KernelControllerEventInterface;
use Pimcore\Model\Element;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\Recyclebin;
use Pimcore\Model\Element\Service;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
class RecyclebinController extends AdminAbstractController implements KernelControllerEventInterface
{
    /**
     * @Route("/recyclebin/list", name="pimcore_admin_recyclebin_list", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function listAction(Request $request)
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
            $list->setLimit($request->get('limit'));
            $list->setOffset($request->get('start'));

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

            if (!$this->getAdminUser()->isAdmin()) {
                $conditionFilters[] = $this->getPermittedPaths();
            }

            if (!empty($conditionFilters)) {
                $condition = implode(' AND ', $conditionFilters);
                $list->setCondition($condition);
            }


            $items = $list->load();
            $data = [];
            if (is_array($items)) {

                /** @var Recyclebin\Item $item */
                foreach ($items as $item) {
                    $dataRow = $item->getObjectVars();
                    $dataRow['permissions'] = $item->getClosestExistingParent()->getUserPermissions();
                    $data[] = $dataRow;
                }
            }

            return $this->adminJson(['data' => $data, 'success' => true, 'total' => $list->getTotalCount()]);
        }
    }
    protected function getPermittedPaths($types = ['asset', 'document', 'object'])
    {
        $user = $this->getAdminUser();
        $db = \Pimcore\Db::get();

        $allowedTypes = [];

        foreach ($types as $type) {
            if ($user->isAllowed($type . 's')) { //the permissions are just plural
                $elementPaths = Service::findForbiddenPaths($type, $user);

                $forbiddenPathSql = [];
                $allowedPathSql = [];
                foreach ($elementPaths['forbidden'] as $forbiddenPath => $allowedPaths) {
                    $exceptions = '';
                    $folderSuffix = '';
                    if ($allowedPaths) {
                        $exceptionsConcat = implode("%' OR path LIKE '", $allowedPaths);
                        $exceptions = " OR (path LIKE '" . $exceptionsConcat . "%')";
                        $folderSuffix = '/'; //if allowed children are found, the current folder is listable but its content is still blocked, can easily done by adding a trailing slash
                    }
                    $forbiddenPathSql[] = ' (path NOT LIKE ' . $db->quote($forbiddenPath . $folderSuffix . '%') . $exceptions . ') ';
                }
                foreach ($elementPaths['allowed'] as $allowedPaths) {
                    $allowedPathSql[] = ' path LIKE ' . $db->quote($allowedPaths  . '%');
                }

                // this is to avoid query error when implode is empty.
                // the result would be like `(type = type AND ((path1 OR path2) AND (not_path3 AND not_path4)))`
                $forbiddenAndAllowedSql = '(type = \'' . $type . '\'';

                if ($allowedPathSql || $forbiddenPathSql) {
                    $forbiddenAndAllowedSql .= ' AND (';
                    $forbiddenAndAllowedSql .= $allowedPathSql ? '( ' . implode(' OR ', $allowedPathSql) . ' )' : '';

                    if ($forbiddenPathSql) {
                        //if $allowedPathSql "implosion" is present, we need `AND` in between
                        $forbiddenAndAllowedSql .= $allowedPathSql ? ' AND ' : '';
                        $forbiddenAndAllowedSql .= implode(' AND ', $forbiddenPathSql);
                    }
                    $forbiddenAndAllowedSql .= ' )';
                }

                $forbiddenAndAllowedSql.= ' )';

                $allowedTypes[] = $forbiddenAndAllowedSql;
            }
        }

        //if allowedTypes is still empty after getting the workspaces, it means that there are no any main permissions set
        // by setting a `false` condition in the query makes sure that nothing would be displayed.
        if (!$allowedTypes) {
            $allowedTypes = ['false'];
        }

        return '('.implode(' OR ', $allowedTypes) .')';
    }

    /**
     * @Route("/recyclebin/restore", name="pimcore_admin_recyclebin_restore", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function restoreAction(Request $request)
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
    public function flushAction()
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
    public function addAction(Request $request)
    {
        try {
            $element = Service::getElementById($request->get('type'), $request->get('id'));

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

    /**
     * @param ControllerEvent $event
     */
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
