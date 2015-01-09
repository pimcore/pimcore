<?php

class OnlineShop_Framework_FilterService_ElasticSearch_NumberRange extends OnlineShop_Framework_FilterService_NumberRange {

    public function prepareGroupByValues(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList) {
        $productList->prepareGroupByValues($this->getField($filterDefinition), true);
    }

    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $field = $this->getField($filterDefinition);
        $value = $params[$field];

        if(empty($value)) {
            $value['from'] = $filterDefinition->getPreSelectFrom();
            $value['to'] = $filterDefinition->getPreSelectTo();
        }

        $currentFilter[$field] = $value;

        if(!empty($value)) {
            $range = [];
            if(!empty($value['from'])) {
                $range['gte'] = $value['from'];
            }
            if(!empty($value['to'])) {
                $range['lte'] = $value['from'];
            }
            $productList->addCondition(['range' => [$field => $range]], $field);
        }
        return $currentFilter;
    }
}
