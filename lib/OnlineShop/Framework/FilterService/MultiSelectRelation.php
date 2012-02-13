<?php

class OnlineShop_Framework_FilterService_MultiSelectRelation extends OnlineShop_Framework_FilterService_AbstractFilterType {

    public function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter) {

        $values = $productList->getGroupByRelationValues($filterDefinition->getField(), true);

        $objects = array();
        Logger::log("Load Objects...", Zend_Log::INFO);
        foreach($values as $v) {
            $objects[$v['value']] = Object_Abstract::getById($v['value']);
        }
        Logger::log("done.", Zend_Log::INFO);

        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }
        return $this->view->partial($script, array(
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$filterDefinition->getField()],
            "values" => $values,
            "objects" => $objects,
            "fieldname" => $filterDefinition->getField()
        ));
    }

    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $value = $params[$filterDefinition->getField()];

        if(empty($value)) {
            $objects = $filterDefinition->getPreSelect();
            $value = array();
            foreach($objects as $o) {
                $value[] = $o->getId();
            }

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
                    $productList->addRelationCondition("PRECONDITION_" . $filterDefinition->getField(),  "dest IN (" . implode(",", $quotedValues) . ")");
                } else {
                    $productList->addRelationCondition($filterDefinition->getField(),  "dest IN (" . implode(",", $quotedValues) . ")");
                }
            }
        }
        return $currentFilter;
    }
}
