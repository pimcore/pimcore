<?php

class OnlineShop_Framework_FilterService_Findologic_Select extends OnlineShop_Framework_FilterService_Select {

    public function prepareGroupByValues(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList) {
        //$productList->prepareGroupByValues($this->getField($filterDefinition), true);
    }

    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);

        $value = $params[$field];

        if($value == OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } else if(empty($value) && !$params['is_reload']) {
            $value = $preSelect;
        }

        $value = trim($value);
        $currentFilter[$field] = $value;


        if(!empty($value)) {
            $productList->addCondition([$value], $field);
        }
        return $currentFilter;
    }
}
