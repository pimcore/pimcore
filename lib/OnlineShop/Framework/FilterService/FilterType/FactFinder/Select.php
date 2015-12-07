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

namespace OnlineShop\Framework\FilterService\FilterType\FactFinder;

class Select extends \OnlineShop\Framework\FilterService\FilterType\Select
{
    /**
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition
     * @param \OnlineShop\Framework\IndexService\ProductList\IProductList                 $productList
     */
    public function prepareGroupByValues(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList)
    {

    }


    /**
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition
     * @param \OnlineShop\Framework\IndexService\ProductList\IProductList                 $productList
     * @param array                                             $currentFilter
     * @param array                                             $params
     * @param bool                                              $isPrecondition
     *
     * @return array
     */
    public function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        // init
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);
        $value = $params[$field];


        // set defaults
        if($value == \OnlineShop\Framework\FilterService\FilterType\AbstractFilterType::EMPTY_STRING){
            $value = null;
        } else if(empty($value) && !$params['is_reload']) {
            $value = $preSelect;
        }


        $value = trim($value);
        $currentFilter[$field] = $value;


        // add condition
        if(!empty($value))
        {
            $productList->addCondition(trim($value), $field);
        }

        return $currentFilter;
    }
}
