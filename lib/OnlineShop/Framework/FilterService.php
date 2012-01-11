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
            return new $this->config->$name->class($this->view);
        } else {
            return $name; //throw new OnlineShop_Framework_Exception_UnsupportedException($name . " not as filter type configured.");
        }
    }

    
    public function getFilterFrontend($name, OnlineShop_Framework_ProductList $productList, $filterDefinition, $currentFilter) {
        return $this->getFilterDefinitionClass($name)->getFilterFrontend($productList, $filterDefinition, $currentFilter);
    }

    public function addCondition($name, OnlineShop_Framework_ProductList $productList, $filterDefinition, $currentFilter, $params) {
        return $this->getFilterDefinitionClass($name)->addCondition($productList, $filterDefinition, $currentFilter, $params);
    }

}
