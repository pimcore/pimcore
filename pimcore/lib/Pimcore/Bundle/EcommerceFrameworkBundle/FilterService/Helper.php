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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Templating\Model\ViewModel;

/**
 * Class \Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\Helper
 *
 * Helper Class for setting up a product list utilizing the filter service
 * based on a filter definition and set filter parameters
 */
class Helper
{
    /**
     * @param \Pimcore\Model\Object\FilterDefinition $filterDefinition
     * @param IProductList $productList
     * @param $params
     * @param ViewModel $view
     * @param FilterService $filterService
     * @param $loadFullPage
     * @param bool $excludeLimitOfFirstpage
     */
    public static function setupProductList(\Pimcore\Model\Object\FilterDefinition $filterDefinition,
                                            IProductList $productList,
                                            $params, $viewModel,
                                            FilterService $filterService,
                                            $loadFullPage, $excludeLimitOfFirstpage = false)
    {
        $orderByOptions = [];
        $orderKeysAsc = explode(',', $filterDefinition->getOrderByAsc());
        if (!empty($orderKeysAsc)) {
            foreach ($orderKeysAsc as $orderByEntry) {
                if (!empty($orderByEntry)) {
                    $orderByOptions[$orderByEntry]['asc'] = true;
                }
            }
        }

        $orderKeysDesc = explode(',', $filterDefinition->getOrderByDesc());
        if (!empty($orderKeysDesc)) {
            foreach ($orderKeysDesc as $orderByEntry) {
                if (!empty($orderByEntry)) {
                    $orderByOptions[$orderByEntry]['desc'] = true;
                }
            }
        }

        $offset = 0;

        $pageLimit = isset($params['perPage']) ? intval($params['perPage']) : null;
        if (!$pageLimit) {
            $pageLimit = $filterDefinition->getPageLimit();
        }
        if (!$pageLimit) {
            $pageLimit = 50;
        }
        $limitOnFirstLoad = $filterDefinition->getLimitOnFirstLoad();
        if (!$limitOnFirstLoad) {
            $limitOnFirstLoad = 6;
        }

        if (isset($params['page'])) {
            $viewModel->currentPage = intval($params['page']);
            $offset = $pageLimit * ($params['page'] - 1);
        }
        if ($filterDefinition->getAjaxReload()) {
            if ($loadFullPage && !$excludeLimitOfFirstpage) {
                $productList->setLimit($pageLimit);
            } elseif ($loadFullPage && $excludeLimitOfFirstpage) {
                $offset += $limitOnFirstLoad;
                $productList->setLimit($pageLimit - $limitOnFirstLoad);
            } else {
                $productList->setLimit($limitOnFirstLoad);
            }
        } else {
            $productList->setLimit($pageLimit);
        }
        $productList->setOffset($offset);

        $viewModel->pageLimit = $pageLimit;

        $orderByField     = null;
        $orderByDirection = null;

        if (isset($params['orderBy'])) {
            $orderBy = explode('#', $params['orderBy']);
            $orderByField = $orderBy[0];

            if (count($orderBy) > 1) {
                $orderByDirection = $orderBy[1];
            }
        }

        if (array_key_exists($orderByField, $orderByOptions)) {
            $viewModel->currentOrderBy = htmlentities($params['orderBy']);

            $productList->setOrderKey($orderByField);

            if ($orderByDirection) {
                $productList->setOrder($orderByDirection);
            }
        } else {
            $orderByCollection = $filterDefinition->getDefaultOrderBy();
            $orderByList = [];
            if ($orderByCollection) {
                foreach ($orderByCollection as $orderBy) {
                    if ($orderBy->getField()) {
                        $orderByList[] = [$orderBy->getField(), $orderBy->getDirection()];
                    }
                }

                $viewModel->currentOrderBy = implode('#', reset($orderByList));
            }
            $productList->setOrderKey($orderByList);
            $productList->setOrder('ASC');
        }

        if ($filterService) {
            $viewModel->currentFilter = $filterService->initFilterService($filterDefinition, $productList, $params);
        }

        $viewModel->orderByOptions = $orderByOptions;
    }

    /**
     * @param $page
     *
     * @return string
     */
    public static function createPagingQuerystring($page)
    {
        $params = $_REQUEST;
        $params['page'] = $page;
        unset($params['fullpage']);

        $string = '?';
        foreach ($params as $k => $p) {
            if (is_array($p)) {
                foreach ($p as $subKey => $subValue) {
                    $string .= $k . '[' . $subKey . ']' . '=' . urlencode($subValue) . '&';
                }
            } else {
                $string .= $k . '=' . urlencode($p) . '&';
            }
        }

        return $string;
    }

    /**
     * @param $conditions
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory
     */
    public static function getFirstFilteredCategory($conditions)
    {
        if (!empty($conditions)) {
            foreach ($conditions as $c) {
                if ($c instanceof \Pimcore\Model\Object\Fieldcollection\Data\FilterCategory) {
                    return $c->getPreSelect();
                }
            }
        }
    }
}
