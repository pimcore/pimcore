<?php

class OnlineShop_Framework_FilterService_Select extends OnlineShop_Framework_FilterService_AbstractFilterType {

    public function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter) {
        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }
        return $this->view->partial($script, array(
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$filterDefinition->getField()],
            "values" => $productList->getGroupByValues($filterDefinition->getField(), true),
            "fieldname" => $filterDefinition->getField()
        ));
    }

    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $value = $params[$filterDefinition->getField()];

        if($value == OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } else if(empty($value)) {
            $value = $filterDefinition->getPreSelect();
        }

        $currentFilter[$filterDefinition->getField()] = $value;


        if(!empty($value)) {
            if($isPrecondition) {
                $productList->addCondition($filterDefinition->getField() . " = " . $productList->quote($value), "PRECONDITION_" . $filterDefinition->getField());
            } else {
                $productList->addCondition($filterDefinition->getField() . " = " . $productList->quote($value), $filterDefinition->getField());
            }

        }
        return $currentFilter;
    }
}
