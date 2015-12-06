<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
        $productList->setVariantMode(OnlineShop_Framework_IProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT);

        $filterService = \OnlineShop\Framework\Factory::getInstance()->getFilterService($this->view);


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
        $productList->setVariantMode(OnlineShop_Framework_IProductList::VARIANT_MODE_INCLUDE_PARENT_OBJECT);

        $filterService = \OnlineShop\Framework\Factory::getInstance()->getFilterService($this->view);


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
