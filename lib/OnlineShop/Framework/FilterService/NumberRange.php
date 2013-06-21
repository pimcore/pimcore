<?php

class OnlineShop_Framework_FilterService_NumberRange extends OnlineShop_Framework_FilterService_AbstractFilterType {

    public function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter) {
        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }
        return $this->view->partial($script, array(
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$filterDefinition->getField()],
            "values" => $productList->getGroupByValues($filterDefinition->getField(), true),
            "definition" => $filterDefinition,
            "fieldname" => $filterDefinition->getField()
        ));
    }

    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $value = $params[$filterDefinition->getField()];

        if(empty($value)) {
            $value['from'] = $filterDefinition->getPreSelectFrom();
            $value['to'] = $filterDefinition->getPreSelectTo();
        }

        $currentFilter[$filterDefinition->getField()] = $value;

        if(!empty($value)) {
            if(!empty($value['from'])) {

                if($isPrecondition) {
                    $productList->addCondition($filterDefinition->getField() . " >= " . $productList->quote($value['from']), "PRECONDITION_" . $filterDefinition->getField());
                } else {
                    $productList->addCondition($filterDefinition->getField() . " >= " . $productList->quote($value['from']), $filterDefinition->getField());
                }

            }
            if(!empty($value['to'])) {

                if($isPrecondition) {
                    $productList->addCondition($filterDefinition->getField() . " <= " . $productList->quote($value['to']), "PRECONDITION_" . $filterDefinition->getField());
                } else {
                    $productList->addCondition($filterDefinition->getField() . " <= " . $productList->quote($value['to']), $filterDefinition->getField());
                }

            }
        }
        return $currentFilter;
    }
}
