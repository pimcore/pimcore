<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Christian
 * Date: 14.09.11
 * Time: 20:53
 * To change this template use File | Settings | File Templates.
 */
 
class OnlineShop_Framework_FilterService {

    protected $config;

    public function __construct($config, Zend_View $view) {
        $this->config = $config;
        $this->view = $view;
    }

    /**
     * @param $name
     * @return OnlineShop_Framework_FilterService_IFilterService
     */
    public function getFilterDefinitionClass($name) {
        if($this->config->$name) {
            return new $this->config->$name->class($this->view, $this->config->$name->script);
        } else {
            return $name; //throw new OnlineShop_Framework_Exception_UnsupportedException($name . " not as filter type configured.");
        }
    }


    public function initFilterService(OnlineShop_Framework_AbstractFilterDefinition $filterObject, OnlineShop_Framework_ProductList $productList, $params = array()) {
        $currentFilter = array();

        foreach($filterObject->getFilters() as $filter) {

            /**
             * @var $filter OnlineShop_Framework_AbstractFilterDefinitionType
             */
            $currentFilter = $this->addCondition($filter, $productList, $currentFilter, $params);
        }


        foreach($filterObject->getConditions() as $condition) {

            /**
             * @var $condition OnlineShop_Framework_AbstractFilterDefinitionType
             */
            $this->addCondition($condition, $productList, $currentFilter, array(), true);
        }

        return $currentFilter;

    }
    
    public function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter) {
        return $this->getFilterDefinitionClass($filterDefinition->getType())->getFilterFrontend($filterDefinition, $productList, $currentFilter);
    }

    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        return $this->getFilterDefinitionClass($filterDefinition->getType())->addCondition($filterDefinition, $productList, $currentFilter, $params, $isPrecondition);
    }

}
