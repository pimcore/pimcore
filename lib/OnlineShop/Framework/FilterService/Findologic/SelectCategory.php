<?php
/**
 * Created by PhpStorm.
 * User: srainer
 * Date: 10.07.2015
 * Time: 08:45
 */


class OnlineShop_Framework_FilterService_Findologic_SelectCategory extends OnlineShop_Framework_FilterService_SelectCategory {

    const FIELDNAME = 'cat';

    public function prepareGroupByValues(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList) {
        //$productList->prepareGroupBySystemValues($filterDefinition->getField(), true);
    }

    public function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter) {
        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }

        $rawValues = $productList->getGroupByValues(self::FIELDNAME, true);
        $values = array();

        $availableRelations = array();
        if($filterDefinition->getAvailableCategories()) {
            foreach($filterDefinition->getAvailableCategories() as $rel) {
                $availableRelations[$rel->getId()] = true;
            }
        }

        foreach($rawValues as $v) {
            $values[$v['label']] = array('value' => $v['label'], "count" => $v['count']);
        }

        return $this->view->partial($script, array(
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$filterDefinition->getField()],
            "values" => array_values($values),
            "fieldname" => self::FIELDNAME
        ));
    }

    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $value = $params[$filterDefinition->getField()];

        if($value == OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } else if(empty($value) && !$params['is_reload']) {
            $value = $filterDefinition->getPreSelect();
            if(is_object($value)) {
                $value = $value->getId();
            }
        }

        $currentFilter[$filterDefinition->getField()] = $value;

        if(!empty($value)) {
            $value = trim($value);
            if(OnlineShop_Framework_AbstractCategory::getById($value)) {
                $productList->setCategory(OnlineShop_Framework_AbstractCategory::getById($value));
            }
        }
        return $currentFilter;
    }
}
