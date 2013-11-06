<?php

class OnlineShop_Framework_FilterService_SelectFromMultiSelect extends OnlineShop_Framework_FilterService_AbstractFilterType {

    public function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter) {
        $field = $this->getField($filterDefinition);

        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }

        $rawValues = $productList->getGroupByValues($field, true);

        $values = array();
        foreach($rawValues as $v) {
            $explode = explode(OnlineShop_Framework_IndexService_Tenant_Worker::MULTISELECT_DELIMITER, $v['value']);
            foreach($explode as $e) {
                if(!empty($e)) {
                    if($values[$e]) {
                        $values[$e]['count'] += $v['count'];
                    } else {
                        $values[$e] = array('value' => $e, "count" => $v['count']);
                    }
                }
            }
        }

        return $this->view->partial($script, array(
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$field],
            "values" => array_values($values),
            "fieldname" => $field
        ));
    }

    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter, $params, $isPrecondition = false) {
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
            $value =  "%" . OnlineShop_Framework_IndexService_Tenant_Worker::MULTISELECT_DELIMITER  . $value .  OnlineShop_Framework_IndexService_Tenant_Worker::MULTISELECT_DELIMITER . "%";
            if($isPrecondition) {
                $productList->addCondition($field . " LIKE " . $productList->quote($value), "PRECONDITION_" . $field);
            } else {
                $productList->addCondition($field . " LIKE " . $productList->quote($value), $field);
            }

        }
        return $currentFilter;
    }
}
