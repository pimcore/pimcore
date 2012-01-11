<?php

class OnlineShop_Framework_FilterService_MultiSelect implements OnlineShop_Framework_FilterService_IFilterService {

    protected $script = "/sample/filters/multiselect.php";

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
            $quotedValues = array();
            foreach($value as $v) {
                if(!empty($v)) {
                    $quotedValues[] = $productList->quote($v);
                }
            }
            if(!empty($quotedValues)) {
                $productList->addCondition($filterDefinition->getField() . " IN (" . implode(",", $quotedValues) . ")");
            }
        }
        return $currentFilter;
    }
}
