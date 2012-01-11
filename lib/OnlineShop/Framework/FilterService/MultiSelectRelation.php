<?php

class OnlineShop_Framework_FilterService_MultiSelectRelation implements OnlineShop_Framework_FilterService_IFilterService {

    protected $script = "/sample/filters/multiselect-relation.php";

    public function __construct($view) {
        $this->view = $view;
    }

    public function getFilterFrontend(OnlineShop_Framework_ProductList $productList, $filterDefinition, $currentFilter) {

        $values = $productList->getGroupByRelationValues($filterDefinition->getField(), true);

        $objects = array();
        Logger::log("Load Objects...", Zend_Log::INFO);
        foreach($values as $v) {
            $objects[$v['value']] = Object_Abstract::getById($v['value']);
        }
        Logger::log("done.", Zend_Log::INFO);

        return $this->view->partial($this->script, array(
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$filterDefinition->getField()],
            "values" => $values,
            "objects" => $objects,
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
                $productList->addRelationCondition($filterDefinition->getField(),  "dest IN (" . implode(",", $quotedValues) . ")");
            }
        }
        return $currentFilter;
    }
}
