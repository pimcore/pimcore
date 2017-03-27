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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;

class NumberRange extends \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\NumberRange
{
    public function prepareGroupByValues(AbstractFilterDefinitionType $filterDefinition, IProductList $productList)
    {
        $productList->prepareGroupByValues($this->getField($filterDefinition), true);
    }

    public function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        $field = $this->getField($filterDefinition);
        $value = $params[$field];

        if (empty($value)) {
            $value['from'] = $filterDefinition->getPreSelectFrom();
            $value['to'] = $filterDefinition->getPreSelectTo();
        }

        $currentFilter[$field] = $value;

        if (!empty($value)) {
            $range = [];
            if (!empty($value['from'])) {
                $range['gte'] = $value['from'];
            }
            if (!empty($value['to'])) {
                $range['lte'] = $value['from'];
            }
            $productList->addCondition(['range' => ['attributes.' . $field => $range]], $field);
        }

        return $currentFilter;
    }
}
