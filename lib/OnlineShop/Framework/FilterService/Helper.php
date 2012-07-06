<?php
/**
 * Created by JetBrains PhpStorm.
 * User: cfasching
 * Date: 03.05.12
 * Time: 09:01
 * To change this template use File | Settings | File Templates.
 */
class OnlineShop_Framework_FilterService_Helper
{

    public static function setupProductList(Object_FilterDefinition $filterDefinition, $productList, $params, $view, $filterService, $loadFullPage, $excludeLimitOfFirstpage = false) {
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

        $pageLimit = $params["perPage"];
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
                    $orderByList[] = array($orderBy->getField(), $orderBy->getDirection());
                }
            }
            $productList->setOrderKey($orderByList);
            $productList->setOrder("ASC");
        }

        if($filterService) {
            $view->currentFilter = $filterService->initFilterService($view->filterDefinitionObject, $productList, $params);
        }



        $view->orderByOptions = $orderByOptions;

    }

    public static function createPagingQuerystring($page) {
        $params = $_REQUEST;
        $params['page'] = $page;
        unset($params['fullpage']);

        $string = "?";
        foreach($params as $k => $p) {
            $string .= $k . "=" . $p . "&";
        }
        return $string;
    }


    public static function getFirstFilteredCategory($conditions) {
        if(!empty($conditions)) {
            foreach($conditions as $c) {
                if($c instanceof Object_Fieldcollection_Data_FilterCategory) {
                    return $c->getPreSelect();
                }
            }
        }
    }


}
