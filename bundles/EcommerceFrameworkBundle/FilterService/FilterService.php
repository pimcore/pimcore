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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService;

use Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\Exception\FilterTypeNotFoundException;
use Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinition;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;

class FilterService
{
    /**
     * @var FilterGroupHelper
     */
    protected $filterGroupHelper;

    /**
     * @var AbstractFilterType[]
     */
    protected $filterTypes = [];

    /**
     * @param FilterGroupHelper $filterGroupHelper
     * @param AbstractFilterType[] $filterTypes
     */
    public function __construct(FilterGroupHelper $filterGroupHelper, array $filterTypes)
    {
        $this->filterGroupHelper = $filterGroupHelper;

        foreach ($filterTypes as $name => $filterType) {
            $this->registerFilterType($name, $filterType);
        }
    }

    protected function registerFilterType(string $name, AbstractFilterType $filterType)
    {
        $this->filterTypes[$name] = $filterType;
    }

    public function getFilterType(string $name): AbstractFilterType
    {
        if (!isset($this->filterTypes[$name])) {
            throw new FilterTypeNotFoundException(sprintf('Filter type "%s" is not registered', $name));
        }

        return $this->filterTypes[$name];
    }

    public function getFilterGroupHelper(): FilterGroupHelper
    {
        return $this->filterGroupHelper;
    }

    /**
     * Initializes the FilterService, adds all conditions to the ProductList and returns an array of the currently set
     * filters
     *
     * @param AbstractFilterDefinition $filterObject filter definition object to use
     * @param ProductListInterface $productList              product list to use and add conditions to
     * @param array $params                          request params with eventually set filter conditions
     *
     * @return array returns set filters
     */
    public function initFilterService(AbstractFilterDefinition $filterObject, ProductListInterface $productList, $params = [])
    {
        $currentFilter = [];

        if ($filterObject->getFilters()) {
            foreach ($filterObject->getFilters() as $filter) {
                /** @var AbstractFilterDefinitionType $filter */
                $currentFilter = $this->addCondition($filter, $productList, $currentFilter, $params);
            }
            //do this in a separate loop in order to make sure that all filters are set when group by values are prepared
            foreach ($filterObject->getFilters() as $filter) {
                //prepare group by filters
                $this->getFilterType($filter->getType())->prepareGroupByValues($filter, $productList);
            }
        }

        if ($filterObject->getConditions()) {
            foreach ($filterObject->getConditions() as $condition) {
                /** @var AbstractFilterDefinitionType $condition */
                $this->addCondition($condition, $productList, $currentFilter, [], true);
            }
        }

        return $currentFilter;
    }

    /**
     * Returns filter frontend script for given filter type (delegates )
     *
     * @param AbstractFilterDefinitionType $filterDefinition filter definition to get frontend script for
     * @param ProductListInterface $productList current product list (with all set filters) to get available options and counts
     * @param array $currentFilter current filter for this filter definition
     *
     * @return string view snippet
     */
    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, $currentFilter)
    {
        return $this
            ->getFilterType($filterDefinition->getType())
            ->getFilterFrontend($filterDefinition, $productList, $currentFilter);
    }

    /**
     * Adds condition - delegates it to the AbstractFilterType instance
     *
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param ProductListInterface $productList
     * @param array $currentFilter
     * @param array $params
     * @param bool $isPrecondition
     *
     * @return array updated currentFilter array
     */
    public function addCondition(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, $currentFilter, $params, $isPrecondition = false)
    {
        return $this
            ->getFilterType($filterDefinition->getType())
            ->addCondition($filterDefinition, $productList, $currentFilter, $params, $isPrecondition);
    }
}
