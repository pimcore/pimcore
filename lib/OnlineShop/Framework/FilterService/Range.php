<?php

class OnlineShop_Framework_FilterService_Range implements OnlineShop_Framework_FilterService_IFilterService {

    protected $script = "/sample/filters/range.php";

    public function __construct($view) {
        $this->view = $view;
    }

    public function getFilterFrontend(OnlineShop_Framework_ProductList $productList, $filterDefinition, $currentFilter) {
        return $this->view->partial($this->script, array(
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$filterDefinition->getField()],
            "values" => $productList->getGroupByValues($filterDefinition->getField(), true),
            "fieldname" => $filterDefinition->getField()
        ));
    }

    public function addCondition(OnlineShop_Framework_ProductList $productList, $filterDefinition, $currentFilter, $params) {
        $value = $params[$filterDefinition->getField()];
        $currentFilter[$filterDefinition->getField()] = $value;

        if(!empty($value)) {
            if(!empty($value['from'])) {
                $productList->addCondition($filterDefinition->getField() . " >= " . $productList->quote($value['from']));
            }
            if(!empty($value['to'])) {
                $productList->addCondition($filterDefinition->getField() . " <= " . $productList->quote($value['to']));
            }
        }
        return $currentFilter;
    }
}
