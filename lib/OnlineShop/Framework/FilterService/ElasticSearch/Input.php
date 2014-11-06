<?php

class OnlineShop_Framework_FilterService_ElasticSearch_Input extends OnlineShop_Framework_FilterService_Input {

    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $field = $filterDefinition->getField($filterDefinition);
        $preSelect = $filterDefinition->getPreSelect($filterDefinition);

        $value = $params[$field];

        if($value == OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } else if(empty($value) && !$params['is_reload']) {
            $value = $preSelect;
        }

        $value = trim($value);
        $currentFilter[$field] = $value;


        if(!empty($value)) {
            $value =  ".*\"" . $value .  "\".*";
            $productList->addCondition(['regexp' => ["attributes." . $field => $value]], $field);
        }
        return $currentFilter;
    }
}
