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


class OnlineShop_Framework_FilterService_FactFinder_MultiSelect extends OnlineShop_Framework_FilterService_MultiSelect
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
     * @return mixed
     */
    public function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        // init
        $field = $this->getField($filterDefinition);
        $value = $params[$field];


        // set defaults
        if(empty($value) && !$params['is_reload'] && ($preSelect = $this->getPreSelect($filterDefinition)))
        {
            $value = explode(",", $preSelect);
        }
        else if(!empty($value) && in_array(OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING, $value))
        {
            $value = null;
        }

        $currentFilter[$field] = $value;



        if(!empty($value))
        {
            $quotedValues = array();

            foreach($value as $v)
            {
                if(!empty($v))
                {
                    $quotedValues[] = $v;
                }
            }


            if(!empty($quotedValues))
            {
                if($filterDefinition->getUseAndCondition())
                {
                    foreach ($quotedValues as $value)
                    {
                        $productList->addCondition($value, $field);
                    }
                }
                else
                {
                    $productList->addCondition($quotedValues, $field);
                }
            }
        }

        return $currentFilter;
    }
}
