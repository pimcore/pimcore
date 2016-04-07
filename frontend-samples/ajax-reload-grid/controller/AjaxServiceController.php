<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


class OnlineShop_AjaxServiceController extends Website_Controller_Action {

    public function reloadProductsAction() {
        /**
         * @var $filterDefinition \Pimcore\Model\Object\Fieldcollection
         */
        $filterDefinition = \Pimcore\Model\Object\Fieldcollection::getById(intval($this->_getParam("filterdef")));
        $this->view->filterDefinitionObject = $filterDefinition;

        $indexService = \OnlineShop\Framework\Factory::getInstance()->getIndexService();
        $productList = $indexService->getProductListForCurrentTenant();
        $productList->setVariantMode(\OnlineShop\Framework\IndexService\ProductList\IProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT);

        $filterService = \OnlineShop\Framework\Factory::getInstance()->getFilterService($this->view);


        $orderByOptions = array();
        $this->view->orderByOptions = $orderByOptions;
        if($filterDefinition) {

            // set up product list
            \OnlineShop\Framework\FilterService\Helper::setupProductList($filterDefinition, $productList, $this->_getAllParams(), $this->view, $filterService, true, true);
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
         * @var $filterDefinition \Pimcore\Model\Object\FilterDefinition
         */
        $filterDefinition = $this->_getParam("filterdefinition");
        if(!$filterDefinition instanceof \Pimcore\Model\Object\FilterDefinition) {
            $filterDefinition = \Pimcore\Model\Object\FilterDefinition::getById(intval($this->_getParam("filterdef")));
            if(!$filterDefinition) {
                throw new Exception("Filter Definition not found!");
            }
        }
        $this->view->filterDefinitionObject = $filterDefinition;

        $indexService = \OnlineShop\Framework\Factory::getInstance()->getIndexService();
        $productList = $indexService->getProductListForCurrentTenant();
        $productList->setVariantMode(\OnlineShop\Framework\IndexService\ProductList\IProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT);

        $filterService = \OnlineShop\Framework\Factory::getInstance()->getFilterService($this->view);


        $orderByOptions = array();
        $this->view->orderByOptions = $orderByOptions;
        \OnlineShop\Framework\FilterService\Helper::setupProductList($filterDefinition, $productList, $this->_getAllParams(), $this->view, $filterService, $this->_getParam("fullpage"));

        $this->view->productList = $productList;
        $this->view->filterService = $filterService;


        //Getting seo text - if not specified in the template, then get them from the category if existent
        $grid_seo_headline = $this->view->input("grid_seo_headline")->getValue();

        if(empty($grid_seo_headline)) {
            $category = \OnlineShop\Framework\FilterService\Helper::getFirstFilteredCategory($this->view->filterDefinitionObject->getConditions());
            if($category) {
                $grid_seo_headline = $category->getSeoname();
            }
        }
        $grid_seo_text = $this->view->wysiwyg("grid_seo_text")->getValue();
        if(empty($grid_seo_text)) {
            $category = \OnlineShop\Framework\FilterService\Helper::getFirstFilteredCategory($this->view->filterDefinitionObject->getConditions());
            if($category) {
                $grid_seo_text = $category->getSeotext();
            }
        }

        $this->view->grid_seo_headline = $grid_seo_headline;
        $this->view->grid_seo_text = $grid_seo_text;

    }

}
