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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Pimcore\Config\Config;
use Pimcore\Logger;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class FilterService
 * @package OnlineShop\Framework\FilterService
 */
class FilterService
{
    protected $config;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var EngineInterface
     */
    protected $renderer;

    /**
     * @param $config Config OnlineShop Configuration
     */
    public function __construct($config, TranslatorInterface $translator, EngineInterface $renderer)
    {
        $this->config = $config;
        $this->translator = $translator;
        $this->renderer = $renderer;
    }

    /**
     * Returns instance of FilterType or
     * just the name if the name is not defined in the OnlineShop configuration
     *
     * @param $name
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType | string
     */
    public function getFilterDefinitionClass($name)
    {
        if ($this->config->$name) {
            return new $this->config->$name->class($this->config->$name->script, $this->config->$name, $this->translator, $this->renderer);
        } else {
            return $name; //throw new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\UnsupportedException($name . " not as filter type configured.");
        }
    }

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterGroupHelper
     * @throws \Exception
     */
    public function getFilterGroupHelper()
    {
        if (!$this->filterGroupHelper) {
            $classname = (string)$this->config->helper;
            if (!class_exists($classname)) {
                Logger::warn("FilterGroupHelper " . $classname . " does not exist, using default implementation.");
                $classname = '\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterGroupHelper';
            }

            $this->filterGroupHelper = new $classname();
        }

        return $this->filterGroupHelper;
    }


    /**
     * Initializes the FilterService, adds all conditions to the ProductList and returns an array of the currently set filters
     *
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinition $filterObject filter definition object to use
     * @param IProductList $productList product list to use and add conditions to
     * @param array $params request params with eventually set filter conditions
     * @return array returns set filters
     */
    public function initFilterService(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinition $filterObject, IProductList $productList, $params = [])
    {
        $currentFilter = [];

        if ($filterObject->getFilters()) {
            foreach ($filterObject->getFilters() as $filter) {

                /**
                 * @var $filter AbstractFilterDefinitionType
                 */
                $currentFilter = $this->addCondition($filter, $productList, $currentFilter, $params);

                //prepare group by filters
                $this->getFilterDefinitionClass($filter->getType())->prepareGroupByValues($filter, $productList);
            }
        }

        if ($filterObject->getConditions()) {
            foreach ($filterObject->getConditions() as $condition) {

                /**
                 * @var $condition AbstractFilterDefinitionType
                 */
                $this->addCondition($condition, $productList, $currentFilter, [], true);
            }
        }

        return $currentFilter;
    }

    /**
     * Returns filter frontend script for given filter type (delegates )
     *
     * @param AbstractFilterDefinitionType $filterDefinition filter definition to get frontend script for
     * @param IProductList $productList current product list (with all set filters) to get available options and counts
     * @param $currentFilter array current filter for this filter definition
     * @return string view snippet
     */
    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter)
    {
        $frontend = $this->getFilterDefinitionClass($filterDefinition->getType())->getFilterFrontend($filterDefinition, $productList, $currentFilter);

        return $frontend;
    }

    /**
     * Adds condition - delegates it to the \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType instance
     *
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param IProductList $productList
     * @param $currentFilter
     * @param $params
     * @param bool $isPrecondition
     * @return array updated currentFilter array
     */
    public function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        return $this->getFilterDefinitionClass($filterDefinition->getType())->addCondition($filterDefinition, $productList, $currentFilter, $params, $isPrecondition);
    }
}
