<?php

class OnlineShop_AjaxServiceController extends Website_Controller_Action {

    public function reloadProductsAction() {
        /**
         * @var $filterDefinition Object_FilterDefinition
         */
        $filterDefinition = Object_FilterDefinition::getById(intval($this->_getParam("filterdef")));
        $this->view->filterDefinitionObject = $filterDefinition;

        $productList = new OnlineShop_Framework_ProductList();
        $productList->setVariantMode(OnlineShop_Framework_ProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT);

        $filterService = OnlineShop_Framework_Factory::getInstance()->getFilterService($this->view);


        $orderByOptions = array();
        $this->view->orderByOptions = $orderByOptions;
        if($filterDefinition) {

            // set up product list
            OnlineShop_Framework_FilterService_Helper::setupProductList($filterDefinition, $productList, $this->_getAllParams(), $this->view, $filterService, true, true);
            // end set up product list

        }

        $this->view->productList = $productList;
        $this->view->filterService = $filterService;
    }

    public function gridAction() {

        if($this->_getParam("pimcore_editmode") == "true") {
            $this->editmode = true;
            $this->view->editmode = true;
        }

        /**
         * @var $filterDefinition Object_FilterDefinition
         */
        $filterDefinition = $this->_getParam("filterdefinition");
        if(!$filterDefinition instanceof Object_FilterDefinition) {
            $filterDefinition = Object_FilterDefinition::getById(intval($this->_getParam("filterdef")));
            if(!$filterDefinition) {
                throw new Exception("Filter Definition not found!");
            }
        }
        $this->view->filterDefinitionObject = $filterDefinition;

        $productList = new OnlineShop_Framework_ProductList();
        $productList->setVariantMode(OnlineShop_Framework_ProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT);

        $filterService = OnlineShop_Framework_Factory::getInstance()->getFilterService($this->view);


        $orderByOptions = array();
        $this->view->orderByOptions = $orderByOptions;
        OnlineShop_Framework_FilterService_Helper::setupProductList($filterDefinition, $productList, $this->_getAllParams(), $this->view, $filterService, $this->_getParam("fullpage"));

        $this->view->productList = $productList;
        $this->view->filterService = $filterService;


        //Getting seo text - if not specified in the template, then get them from the category if existent
        $grid_seo_headline = $this->view->input("grid_seo_headline")->getValue();

        if(empty($grid_seo_headline)) {
            $category = OnlineShop_Framework_FilterService_Helper::getFirstFilteredCategory($this->view->filterDefinitionObject->getConditions());
            if($category) {
                $grid_seo_headline = $category->getSeoname();
            }
        }
        $grid_seo_text = $this->view->wysiwyg("grid_seo_text")->getValue();
        if(empty($grid_seo_text)) {
            $category = OnlineShop_Framework_FilterService_Helper::getFirstFilteredCategory($this->view->filterDefinitionObject->getConditions());
            if($category) {
                $grid_seo_text = $category->getSeotext();
            }
        }

        $this->view->grid_seo_headline = $grid_seo_headline;
        $this->view->grid_seo_text = $grid_seo_text;

    }

}
