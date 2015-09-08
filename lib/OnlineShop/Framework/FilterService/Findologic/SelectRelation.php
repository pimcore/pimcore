<?php

class OnlineShop_Framework_FilterService_Findologic_SelectRelation extends OnlineShop_Framework_FilterService_SelectRelation {

    public function prepareGroupByValues(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList) {
        //$productList->prepareGroupByValues($this->getField($filterDefinition), true);
    }

    public function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter) {
        $field = $this->getField($filterDefinition);


        $values = $productList->getGroupByRelationValues($field, true);

        $objects = array();
        Logger::log("Load Objects...", Zend_Log::INFO);

        $availableRelations = array();
        if($filterDefinition->getAvailableRelations()) {
            $availableRelations = $this->loadAllAvailableRelations($filterDefinition->getAvailableRelations());
        }

        foreach($values as $v) {
            if(empty($availableRelations) || $availableRelations[$v['label']] === true) {
                $objects[$v['label']] = \Pimcore\Model\Object\AbstractObject::getById($v['label']);
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
            "currentValue" => $currentFilter[$field],
            "values" => $values,
            "objects" => $objects,
            "fieldname" => $field
        ));
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
            $productList->addCondition([$value], $field);
        }
        return $currentFilter;
    }
}
