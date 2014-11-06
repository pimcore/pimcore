<?php

class OnlineShop_Framework_FilterService_ElasticSearch_SelectRelation extends OnlineShop_Framework_FilterService_SelectRelation {

    public function prepareGroupByValues(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList) {
        $productList->prepareGroupByValues($filterDefinition->getField(), true);
    }

    protected function loadAllAvailableRelations($availableRelations, $availableRelationsArray = array()) {
        foreach($availableRelations as $rel) {
            if($rel instanceof Object_Folder) {
                $availableRelationsArray = $this->loadAllAvailableRelations($rel->getChilds(), $availableRelationsArray);
            } else {
                $availableRelationsArray[$rel->getId()] = true;
            }
        }
        return $availableRelationsArray;
    }


    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);


        $value = $params[$field];

        if(empty($value) && !$params['is_reload']) {
            $o = $preSelect;
            if(!empty($o)) {
                if(is_object($o)) {
                    $value = $o->getId();
                } else {
                    $value = $o;
                }

            }
        } else if($value == OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING) {
            $value = null;
        }

        $currentFilter[$field] = $value;


        if(!empty($value)) {
            $productList->addRelationCondition($field, $value);
        }

        return $currentFilter;
    }
}
