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
    public function __construct($view, $script, $config = null) {
        $this->view = $view;
        $this->script = $script;
        $this->config = $config;
    }



    protected function getField(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition) {
        $field = $filterDefinition->getField();
        if($field instanceof \Pimcore\Model\Object\Data\IndexFieldSelection) {
            return $field->getField();
        }
        return $field;
    }

    protected function getPreSelect(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition) {
        $field = $filterDefinition->getField();
        if($field instanceof \Pimcore\Model\Object\Data\IndexFieldSelection) {
            return $field->getPreSelect();
        } else if(method_exists($filterDefinition, "getPreSelect")) {
            return $filterDefinition->getPreSelect();
        }
        return null;
    }


    /**
     * renders and returns the rendered html snippet for the current filter
     * based on settings in the filter definition and the current filter params.
     *
     * @abstract
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition
     * @param OnlineShop_Framework_IProductList $productList
     * @param $currentFilter
     * @return string
     */
    public abstract function getFilterFrontend(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter);

    /**
     * adds necessary conditions to the product list implementation based on the currently set filter params.
     *
     * @abstract
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition
     * @param OnlineShop_Framework_IProductList $productList
     * @param $currentFilter
     * @param $params
     * @param bool $isPrecondition
     * @return array
     */
    public abstract function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter, $params, $isPrecondition = false);

    /**
     * calls prepareGroupByValues of productlist if necessary
     *
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition
     * @param OnlineShop_Framework_IProductList $productList
     */
    public function prepareGroupByValues(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList) {
        //by default do thing here
    }
}
