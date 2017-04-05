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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;

class MultiSelectFromMultiSelect extends \Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\MultiSelectFromMultiSelect
{
    public function prepareGroupByValues(AbstractFilterDefinitionType $filterDefinition, IProductList $productList)
    {
        $field = $this->getField($filterDefinition);
        $productList->prepareGroupByValues($field, true, !$filterDefinition->getUseAndCondition());
    }


    /**
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param IProductList                  $productList
     * @param array                                             $currentFilter
     * @param                                                   $params
     * @param bool                                              $isPrecondition
     *
     * @return string[]
     */
    public function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);

        $value = $params[$field];


        if (empty($value) && !$params['is_reload']) {
            if (is_array($preSelect)) {
                $value = $preSelect;
            } else {
                $value = explode(",", $preSelect);
            }

            foreach ($value as $key => $v) {
                if (!$v) {
                    unset($value[$key]);
                }
            }
        } elseif (!empty($value) && in_array(\Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType::EMPTY_STRING, $value)) {
            $value = null;
        }

        $currentFilter[$field] = $value;

        if (!empty($value)) {
            if ($filterDefinition->getUseAndCondition()) {
                foreach ($value as $entry) {
                    $productList->addCondition(['term' => ["attributes." . $field => $entry]], $field);
                }
            } else {
                $boolArray = [];
                foreach ($value as $entry) {
                    $boolArray[] = ['term' => ["attributes." . $field => $entry]];
                }

                $productList->addCondition(['bool' => ['should' => $boolArray, 'minimum_should_match' => 1]], $field);
            }
        }

        return $currentFilter;
    }
}
