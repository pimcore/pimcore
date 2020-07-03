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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRange;

class NumberRange extends \Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\NumberRange
{
    public function prepareGroupByValues(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList)
    {
        $productList->prepareGroupByValues($this->getField($filterDefinition), true);
    }

    /**
     * @param FilterNumberRange $filterDefinition
     * @param ProductListInterface $productList
     * @param array $currentFilter
     * @param array $params
     * @param bool $isPrecondition
     *
     * @return array
     */
    public function addCondition(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, $currentFilter, $params, $isPrecondition = false)
    {
        $field = $this->getField($filterDefinition);
        $value = $params[$field] ?? null;

        if (empty($value)) {
            $value['from'] = $filterDefinition->getPreSelectFrom();
            $value['to'] = $filterDefinition->getPreSelectTo();
        }

        $currentFilter[$field] = $value;

        if (!empty($value) && ($value['from'] !== null || $value['to'] !== null) && ($value['from'] !== '' || $value['to'] !== '')) {
            $range = [];
            if (strlen($value['from']) > 0) {
                $range['gte'] = $value['from'];
            }
            if (strlen($value['to']) > 0) {
                $range['lte'] = $value['to'];
            }
            $productList->addCondition(['range' => ['attributes.' . $field => $range]], $field);
        }

        return $currentFilter;
    }
}
