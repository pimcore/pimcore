<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


/**
 * Class OnlineShop_Framework_FilterService_Helper
 *
 * Helper Class for setting up a product list utilizing the filter service
 * based on a filter definition and set filter parameters
 */
class OnlineShop_Framework_FilterService_Helper
{
    /**
     * @param \Pimcore\Model\Object\FilterDefinition $filterDefinition
     * @param \OnlineShop\Framework\IndexService\ProductList\IProductList $productList
     * @param $params
     * @param Zend_View $view
     * @param OnlineShop_Framework_FilterService $filterService
     * @param $loadFullPage
     * @param bool $excludeLimitOfFirstpage
     */
    public static function setupProductList(\Pimcore\Model\Object\FilterDefinition $filterDefinition,
                                            \OnlineShop\Framework\IndexService\ProductList\IProductList $productList,
                                            $params, \Zend_View $view,
                                            OnlineShop_Framework_FilterService $filterService,
                                            $loadFullPage, $excludeLimitOfFirstpage = false) {

        $orderByOptions = array();
        $orderKeysAsc = explode(",", $filterDefinition->getOrderByAsc());
        if(!empty($orderKeysAsc)) {
            foreach($orderKeysAsc as $orderByEntry) {
                if(!empty($orderByEntry)) {
                    $orderByOptions[$orderByEntry]["asc"] = true;
                }
            }
        }

        $orderKeysDesc = explode(",", $filterDefinition->getOrderByDesc());
        if(!empty($orderKeysDesc)) {
            foreach($orderKeysDesc as $orderByEntry) {
                if(!empty($orderByEntry)) {
                    $orderByOptions[$orderByEntry]["desc"] = true;
                }
            }
        }


        $offset = 0;

        $pageLimit = intval($params["perPage"]);
        if (!$pageLimit) {
            $pageLimit = $filterDefinition->getPageLimit();
        }
        if(!$pageLimit) {
            $pageLimit = 50;
        }
        $limitOnFirstLoad = $filterDefinition->getLimitOnFirstLoad();
        if(!$limitOnFirstLoad) {
            $limitOnFirstLoad = 6;
        }

        if($params["page"]) {
            $view->currentPage = intval($params["page"]);
            $offset = $pageLimit * ($params["page"]-1);
        }
        if($filterDefinition->getAjaxReload()) {
            if($loadFullPage && !$excludeLimitOfFirstpage) {
                $productList->setLimit($pageLimit);
            } else if($loadFullPage && $excludeLimitOfFirstpage) {
                $offset += $limitOnFirstLoad;
                $productList->setLimit($pageLimit - $limitOnFirstLoad);
            } else {
                $productList->setLimit($limitOnFirstLoad);
            }
        } else {
            $productList->setLimit($pageLimit);
        }
        $productList->setOffset($offset);

        $view->pageLimit = $pageLimit;



        $orderBy = $params["orderBy"];
        $orderBy = explode("#", $orderBy);
        $orderByField = $orderBy[0];
        $orderByDirection = $orderBy[1];

        if(array_key_exists($orderByField, $orderByOptions)) {
            $view->currentOrderBy = htmlentities($params["orderBy"]);

            $productList->setOrderKey($orderByField);
            $productList->setOrder($orderByDirection);
        } else {
            $orderByCollection = $filterDefinition->getDefaultOrderBy();
            $orderByList = array();
            if($orderByCollection) {
                foreach($orderByCollection as $orderBy) {
                    if($orderBy->getField()) {
                        $orderByList[] = array($orderBy->getField(), $orderBy->getDirection());
                    }
                }
            }
            $productList->setOrderKey($orderByList);
            $productList->setOrder("ASC");
        }

        if($filterService) {
            $view->currentFilter = $filterService->initFilterService($filterDefinition, $productList, $params);
        }


        $view->orderByOptions = $orderByOptions;

    }

    /**
     * @param $page
     * @return string
     */
    public static function createPagingQuerystring($page) {
        $params = $_REQUEST;
        $params['page'] = $page;
        unset($params['fullpage']);

        $string = "?";
        foreach($params as $k => $p) {
            if(is_array($p)) {
                foreach($p as $subKey => $subValue) {
                    $string .= $k . "[" . $subKey . "]" . "=" . urlencode($subValue) . "&";
                }
            } else {
                $string .= $k . "=" . urlencode($p) . "&";
            }
        }
        return $string;
    }


    /**
     * @param $conditions
     * @return \OnlineShop\Framework\Model\AbstractCategory
     */
    public static function getFirstFilteredCategory($conditions) {
        if(!empty($conditions)) {
            foreach($conditions as $c) {
                if($c instanceof \Pimcore\Model\Object\Fieldcollection\Data\FilterCategory) {
                    return $c->getPreSelect();
                }
            }
        }
    }

}