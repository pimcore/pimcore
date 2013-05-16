<?php

class OnlineShop_Framework_FilterService_MultiSelectRelation extends OnlineShop_Framework_FilterService_AbstractFilterType {

    public function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter) {

        $values = $productList->getGroupByRelationValues($filterDefinition->getField(), true, !$filterDefinition->getUseAndCondition());

        $objects = array();
        Logger::log("Load Objects...", Zend_Log::INFO);
        $availableRelations = array();
        if($filterDefinition->getAvailableRelations()) {
            foreach($filterDefinition->getAvailableRelations() as $rel) {
                $availableRelations[$rel->getId()] = true;
            }
        }

        foreach($values as $v) {
            if(empty($availableRelations) || $availableRelations[$v['value']] === true) {
                $objects[$v['value']] = Object_Abstract::getById($v['value']);
            }
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
            if (is_array($objects)){
                foreach($objects as $o) {
                    $value[] = $o->getId();
                }
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
                if($filterDefinition->getUseAndCondition()) {
                    foreach ($quotedValues as $value) {
                        $productList->addRelationCondition($filterDefinition->getField(), "dest = " . $value);
                    }
                } else {
                    $productList->addRelationCondition($filterDefinition->getField(),  "dest IN (" . implode(",", $quotedValues) . ")");
                }
            }
        }
        return $currentFilter;
    }
}
