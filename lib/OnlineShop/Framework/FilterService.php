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


/**
 * Class OnlineShop_Framework_FilterService
 */
class OnlineShop_Framework_FilterService {

    protected $config;

    /**
     * @param $config OnlineShop Configuration
     * @param Zend_View $view View in which the filters are rendered
     */
    public function __construct($config, \Zend_View $view) {
        $this->config = $config;
        $this->view = $view;
    }

    /**
     * Returns instance of FilterType or
     * just the name if the name is not defined in the OnlineShop configuration
     *
     * @param $name
     * @return OnlineShop_Framework_FilterService_AbstractFilterType | string
     */
    public function getFilterDefinitionClass($name) {
        if($this->config->$name) {
            return new $this->config->$name->class($this->view, $this->config->$name->script,$this->config->$name);
        } else {
            return $name; //throw new \OnlineShop\Framework\Exception\UnsupportedException($name . " not as filter type configured.");
        }
    }

    /**
     * @return OnlineShop_Framework_FilterService_FilterGroupHelper
     * @throws Exception
     */
    public function getFilterGroupHelper() {
        if(!$this->filterGroupHelper) {
            $classname = (string)$this->config->helper;
            if(!class_exists($classname)) {
                Logger::warn("FilterGroupHelper " . $classname . " does not exist, using default implementation.");
                $classname = "OnlineShop_Framework_FilterService_FilterGroupHelper";
            }

            $this->filterGroupHelper = new $classname();
        }
        return $this->filterGroupHelper;
    }


    /**
     * Initializes the FilterService, adds all conditions to the ProductList and returns an array of the currently set filters
     *
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinition $filterObject filter definition object to use
     * @param \OnlineShop\Framework\IndexService\ProductList\IProductList $productList product list to use and add conditions to
     * @param array $params request params with eventually set filter conditions
     * @return array returns set filters
     */
    public function initFilterService(\OnlineShop\Framework\Model\AbstractFilterDefinition $filterObject, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $params = array()) {
        $currentFilter = array();

        if ($filterObject->getFilters()) {
            foreach($filterObject->getFilters() as $filter) {

                /**
                 * @var $filter \OnlineShop\Framework\Model\AbstractFilterDefinitionType
                 */
                $currentFilter = $this->addCondition($filter, $productList, $currentFilter, $params);

                //prepare group by filters
                $this->getFilterDefinitionClass($filter->getType())->prepareGroupByValues($filter, $productList);
            }
        }

        if ($filterObject->getConditions()) {
            foreach($filterObject->getConditions() as $condition) {

                /**
                 * @var $condition \OnlineShop\Framework\Model\AbstractFilterDefinitionType
                 */
                $this->addCondition($condition, $productList, $currentFilter, array(), true);
            }
        }

        return $currentFilter;

    }

    /**
     * Returns filter frontend script for given filter type (delegates )
     *
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition filter definition to get frontend script for
     * @param \OnlineShop\Framework\IndexService\ProductList\IProductList $productList current product list (with all set filters) to get available options and counts
     * @param $currentFilter array current filter for this filter definition
     * @return string view snippet
     */
    public function getFilterFrontend(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter) {

        $frontend = $this->getFilterDefinitionClass($filterDefinition->getType())->getFilterFrontend($filterDefinition, $productList, $currentFilter);

        return $frontend;
    }

    /**
     * Adds condition - delegates it to the OnlineShop_Framework_FilterService_AbstractFilterType instance
     *
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition
     * @param \OnlineShop\Framework\IndexService\ProductList\IProductList $productList
     * @param $currentFilter
     * @param $params
     * @param bool $isPrecondition
     * @return array updated currentFilter array
     */
    public function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        return $this->getFilterDefinitionClass($filterDefinition->getType())->addCondition($filterDefinition, $productList, $currentFilter, $params, $isPrecondition);
    }

}
