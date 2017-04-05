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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\FactFinder;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;

class MultiSelect extends \Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\MultiSelect
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
        if (empty($value) && !$params['is_reload'] && ($preSelect = $this->getPreSelect($filterDefinition))) {
            $value = explode(",", $preSelect);
        } elseif (!empty($value) && in_array(\Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType::EMPTY_STRING, $value)) {
            $value = null;
        }

        $currentFilter[$field] = $value;



        if (!empty($value)) {
            $quotedValues = [];

            foreach ($value as $v) {
                if (!empty($v)) {
                    $quotedValues[] = $v;
                }
            }


            if (!empty($quotedValues)) {
                if ($filterDefinition->getUseAndCondition()) {
                    foreach ($quotedValues as $value) {
                        $productList->addCondition($value, $field);
                    }
                } else {
                    $productList->addCondition($quotedValues, $field);
                }
            }
        }

        return $currentFilter;
    }
}
