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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\IWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;

class MultiSelectFromMultiSelect extends \Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\SelectFromMultiSelect
{
    /**
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param IProductList                  $productList
     * @param                                                   $currentFilter
     *
     * @return string
     */
    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter)
    {
        $field = $this->getField($filterDefinition);
        $rawValues = $productList->getGroupByValues($field, true, !$filterDefinition->getUseAndCondition());

        $values = [];
        foreach ($rawValues as $v) {
            $explode = explode(IWorker::MULTISELECT_DELIMITER, $v['value']);
            foreach ($explode as $e) {
                if (!empty($e)) {
                    if ($values[$e]) {
                        $values[$e]['count'] += $v['count'];
                    } else {
                        $values[$e] = ['value' => $e, 'count' => $v['count']];
                    }
                }
            }
        }

        return $this->render($this->getTemplate($filterDefinition), [
            'hideFilter' => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            'label' => $filterDefinition->getLabel(),
            'currentValue' => $currentFilter[$field],
            'values' => array_values($values),
            'fieldname' => $field,
            'metaData' => $filterDefinition->getMetaData(),
            'resultCount' => $productList->count()
        ]);
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
                $value = explode(',', $preSelect);
            }

            foreach ($value as $key => $v) {
                if (!$v) {
                    unset($value[$key]);
                }
            }
        } elseif (!empty($value) && in_array(AbstractFilterType::EMPTY_STRING, $value)) {
            $value = null;
        }

        //  $value = trim($value);

        $currentFilter[$field] = $value;

        if (!empty($value)) {
            $quotedValues = [];
            foreach ($value as $v) {
                $v =   '%' . IWorker::MULTISELECT_DELIMITER  . $v .  IWorker::MULTISELECT_DELIMITER . '%' ;
                $quotedValues[] = $field . ' like '.$productList->quote($v);
            }

            if ($filterDefinition->getUseAndCondition()) {
                $quotedValues = implode(' and ', $quotedValues);
            } else {
                $quotedValues = implode(' or ', $quotedValues);
            }
            $quotedValues = '('.$quotedValues.')';

            if (!empty($quotedValues)) {
                if ($isPrecondition) {
                    $productList->addCondition($quotedValues, 'PRECONDITION_' . $field);
                } else {
                    $productList->addCondition($quotedValues, $field);
                }
            }
        }

        return $currentFilter;
    }
}
