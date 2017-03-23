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

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;

class NumberRangeSelection extends \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\NumberRangeSelection {

    public function prepareGroupByValues(AbstractFilterDefinitionType $filterDefinition, IProductList $productList) {
        $productList->prepareGroupByValues($this->getField($filterDefinition), true);
    }

    public function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $field = $filterDefinition->getField();
        $rawValue = $params[$field];

        if(!empty($rawValue) && $rawValue != \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType::EMPTY_STRING) {
            $values = explode("-", $rawValue);
            $value['from'] = trim($values[0]);
            $value['to'] = trim($values[1]);
        } else if($rawValue == \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType::EMPTY_STRING) {
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
