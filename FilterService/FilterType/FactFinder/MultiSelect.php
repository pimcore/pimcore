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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\FactFinder;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;

class MultiSelect extends \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\MultiSelect
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
     * @return mixed
     */
    public function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        // init
        $field = $this->getField($filterDefinition);
        $value = $params[$field];


        // set defaults
        if(empty($value) && !$params['is_reload'] && ($preSelect = $this->getPreSelect($filterDefinition)))
        {
            $value = explode(",", $preSelect);
        }
        else if(!empty($value) && in_array(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType::EMPTY_STRING, $value))
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
