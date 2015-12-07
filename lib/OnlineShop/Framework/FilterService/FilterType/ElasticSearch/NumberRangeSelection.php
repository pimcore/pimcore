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

namespace OnlineShop\Framework\FilterService\FilterType\ElasticSearch;

class NumberRangeSelection extends \OnlineShop\Framework\FilterService\FilterType\NumberRangeSelection {

    public function prepareGroupByValues(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList) {
        $productList->prepareGroupByValues($this->getField($filterDefinition), true);
    }

    public function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $field = $filterDefinition->getField();
        $rawValue = $params[$field];

        if(!empty($rawValue) && $rawValue != \OnlineShop\Framework\FilterService\FilterType\AbstractFilterType::EMPTY_STRING) {
            $values = explode("-", $rawValue);
            $value['from'] = trim($values[0]);
            $value['to'] = trim($values[1]);
        } else if($rawValue == \OnlineShop\Framework\FilterService\FilterType\AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } else {
            $value['from'] = $filterDefinition->getPreSelectFrom();
            $value['to'] = $filterDefinition->getPreSelectTo();
        }

        $currentFilter[$field] = $value;


        if(!empty($value)) {
            $range = [];
            if(!empty($value['from'])) {
                $range['gte'] = $value['from'];
            }
            if(!empty($value['to'])) {
                $range['lte'] = $value['from'];
            }
            $productList->addCondition(['range' => ['attributes.' . $field => $range]], $field);
        }
        return $currentFilter;
    }
}
