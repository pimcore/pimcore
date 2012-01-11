<?php

interface OnlineShop_Framework_FilterService_IFilterService {

    public function getFilterFrontend(OnlineShop_Framework_ProductList $productList, $filterDefinition, $currentFilter);
    public function addCondition(OnlineShop_Framework_ProductList $productList, $filterDefinition, $currentFilter, $params);

}
