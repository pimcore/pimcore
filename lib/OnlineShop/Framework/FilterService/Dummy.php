<?php

class OnlineShop_Framework_FilterService_Dummy implements OnlineShop_Framework_FilterService_IFilterService {

    public function __construct($view) {
        $this->view = $view;
    }

    public function getFilterFrontend(OnlineShop_Framework_ProductList $productList, $filterDefinition, $currentFilter) {
        return $filterDefinition->getField();
    }

    public function addCondition(OnlineShop_Framework_ProductList $productList, $filterDefinition, $currentFilter, $params) {
        return $currentFilter;
    }
}
