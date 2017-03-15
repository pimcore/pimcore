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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType;

abstract class AbstractFilterType {

    const EMPTY_STRING = '$$EMPTY$$';

    protected $view;
    protected $script;
    protected $config;
    /**
     * @param $view view to render the filter frontend into
     * @param $script script for rendering the filter frontend
     * @param $config \Zend_Config for more settings (optional)
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
     * @param \OnlineShop\Framework\IndexService\ProductList\IProductList $productList
     * @param $currentFilter
     * @return string
     */
    public abstract function getFilterFrontend(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter);

    /**
     * adds necessary conditions to the product list implementation based on the currently set filter params.
     *
     * @abstract
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition
     * @param \OnlineShop\Framework\IndexService\ProductList\IProductList $productList
     * @param $currentFilter
     * @param $params
     * @param bool $isPrecondition
     * @return array
     */
    public abstract function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter, $params, $isPrecondition = false);

    /**
     * calls prepareGroupByValues of productlist if necessary
     *
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition
     * @param \OnlineShop\Framework\IndexService\ProductList\IProductList $productList
     */
    public function prepareGroupByValues(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList) {
        //by default do thing here
    }


    /**
     * sort result
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition
     * @param array                                                    $result
     *
     * @return array
     */
    protected function sortResult(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, array $result)
    {
        return $result;
    }
}
