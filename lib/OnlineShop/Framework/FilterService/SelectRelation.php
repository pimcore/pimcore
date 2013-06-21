<?php

class OnlineShop_Framework_FilterService_SelectRelation extends OnlineShop_Framework_FilterService_AbstractFilterType {

    public function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter) {

        $values = $productList->getGroupByRelationValues($filterDefinition->getField(), true);

        $objects = array();
        Logger::log("Load Objects...", Zend_Log::INFO);

        $availableRelations = array();
        if($filterDefinition->getAvailableRelations()) {
            $availableRelations = $this->loadAllAvailableRelations($filterDefinition->getAvailableRelations());
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
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$filterDefinition->getField()],
            "values" => $values,
            "objects" => $objects,
            "fieldname" => $filterDefinition->getField()
        ));
    }

    private function loadAllAvailableRelations($availableRelations, $availableRelationsArray = array()) {
        foreach($availableRelations as $rel) {
            if($rel instanceof Object_Folder) {
                $availableRelationsArray = $this->loadAllAvailableRelations($rel->getChilds(), $availableRelationsArray);
            } else {
                $availableRelationsArray[$rel->getId()] = true;
            }
        }
        return $availableRelationsArray;
    }


    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $value = $params[$filterDefinition->getField()];

        if(empty($value)) {
            $o = $filterDefinition->getPreSelect();
            if(!empty($o)) {
                $value = $o->getId();
            }
        } else if($value == OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING) {
            $value = null;
        }

        $currentFilter[$filterDefinition->getField()] = $value;


        if(!empty($value)) {
//            if($isPrecondition) {
//                $productList->addRelationCondition("PRECONDITION_" . $filterDefinition->getField(),  "dest = " . $productList->quote($value));
//            } else {
                $productList->addRelationCondition($filterDefinition->getField(),  "dest = " . $productList->quote($value));
//            }

        }

        return $currentFilter;
    }
}
