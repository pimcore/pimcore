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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch;

class MultiSelectFromMultiSelect extends \OnlineShop\Framework\FilterService\FilterType\MultiSelectFromMultiSelect
{

    public function prepareGroupByValues(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList) {
        $field = $this->getField($filterDefinition);
        $productList->prepareGroupByValues($field, true, !$filterDefinition->getUseAndCondition());
    }


    /**
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition
     * @param \OnlineShop\Framework\IndexService\ProductList\IProductList                  $productList
     * @param array                                             $currentFilter
     * @param                                                   $params
     * @param bool                                              $isPrecondition
     *
     * @return string[]
     */
    public function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);

        $value = $params[$field];


        if(empty($value) && !$params['is_reload']) {
            if(is_array($preSelect)) {
                $value = $preSelect;
            } else {
                $value = explode(",", $preSelect);
            }

            foreach($value as $key => $v) {
                if(!$v) {
                    unset($value[$key]);
                }
            }
        } else if(!empty($value) && in_array(\OnlineShop\Framework\FilterService\FilterType\AbstractFilterType::EMPTY_STRING, $value)) {
            $value = null;
        }

        $currentFilter[$field] = $value;

        if(!empty($value)) {
            if($filterDefinition->getUseAndCondition()) {
                foreach($value as $entry) {
                    $productList->addCondition(['term' => ["attributes." . $field => $entry]], $field);
                }
            } else {
                $boolArray = [];
                foreach($value as $entry) {
                    $boolArray[] = ['term' => ["attributes." . $field => $entry]];
                }

                $productList->addCondition(['bool' => ['should' => $boolArray, 'minimum_should_match' => 1]], $field);
            }
        }
        return $currentFilter;
    }

}