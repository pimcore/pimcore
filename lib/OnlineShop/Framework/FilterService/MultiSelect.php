<?php

class OnlineShop_Framework_FilterService_MultiSelect extends OnlineShop_Framework_FilterService_AbstractFilterType {

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

        if(empty($value)) {
            $value = explode(",", $filterDefinition->getPreSelect());
        } else if(in_array(OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING, $value)) {
            $value = null;
        }

        $currentFilter[$filterDefinition->getField()] = $value;

        if(!empty($value)) {
            $quotedValues = array();
            foreach($value as $v) {
                if(!empty($v)) {
                    $quotedValues[] = $productList->quote($v);
                }
            }
            if(!empty($quotedValues)) {
                if($isPrecondition) {
                    $productList->addCondition($filterDefinition->getField() . " IN (" . implode(",", $quotedValues) . ")", "PRECONDITION_" . $filterDefinition->getField());
                } else {
                    $productList->addCondition($filterDefinition->getField() . " IN (" . implode(",", $quotedValues) . ")", $filterDefinition->getField());
                }

            }
        }
        return $currentFilter;
    }
}
