<?php

class OnlineShop_Framework_FilterService_ElasticSearch_SelectFromMultiSelect extends OnlineShop_Framework_FilterService_SelectFromMultiSelect {

    public function prepareGroupByValues(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList) {
        $productList->prepareGroupByValues($filterDefinition->getField(), true);
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
            $value =  ".*\"" . OnlineShop_Framework_IndexService_Tenant_IWorker::MULTISELECT_DELIMITER  . $value .  OnlineShop_Framework_IndexService_Tenant_IWorker::MULTISELECT_DELIMITER . "\".*";
            $productList->addCondition(['regexp' => ["attributes." . $field => $value]], $field);
        }
        return $currentFilter;
    }
}
