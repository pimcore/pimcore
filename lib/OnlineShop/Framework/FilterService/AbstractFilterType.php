<?php

abstract class OnlineShop_Framework_FilterService_AbstractFilterType {

    const EMPTY_STRING = '$$EMPTY$$';

    protected $view;
    protected $script;
    protected $config;
    /**
     * @param $view view to render the filter frontend into
     * @param $script script for rendering the filter frontend
     * @param $config Zend_Config for more settings (optional)
     */
    public function __construct($view, $script,$config=null) {
        $this->view = $view;
        $this->script = $script;
        $this->config = $config;
    }



    protected function getField(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition) {
        $field = $filterDefinition->getField();
        if($field instanceof Object_Data_IndexFieldSelection) {
            return $field->getField();
        }
        return $field;
    }

    protected function getPreSelect(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition) {
        $field = $filterDefinition->getField();
        if($field instanceof Object_Data_IndexFieldSelection) {
            return $field->getPreSelect();
        } else if(method_exists($filterDefinition, "getPreSelect")) {
            return $filterDefinition->getPreSelect();
        }
        return null;
    }


    /**
     * @abstract
     * @param OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition
     * @param OnlineShop_Framework_ProductList $productList
     * @param $currentFilter
     * @return string
     */
    public abstract function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter);

    /**
     * @abstract
     * @param OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition
     * @param OnlineShop_Framework_ProductList $productList
     * @param $currentFilter
     * @param $params
     * @param bool $isPrecondition
     * @return array
     */
    public abstract function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter, $params, $isPrecondition = false);

}
