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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\FactFinder;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;

class Select extends \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\Select
{
    /**
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param IProductList                 $productList
     */
    public function prepareGroupByValues(AbstractFilterDefinitionType $filterDefinition, IProductList $productList)
    {
    }


    /**
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param IProductList                 $productList
     * @param array                                             $currentFilter
     * @param array                                             $params
     * @param bool                                              $isPrecondition
     *
     * @return array
     */
    public function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        // init
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);
        $value = $params[$field];


        // set defaults
        if ($value == \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } elseif (empty($value) && !$params['is_reload']) {
            $value = $preSelect;
        }


        $value = trim($value);
        $currentFilter[$field] = $value;


        // add condition
        if (!empty($value)) {
            $productList->addCondition(trim($value), $field);
        }

        return $currentFilter;
    }
}
