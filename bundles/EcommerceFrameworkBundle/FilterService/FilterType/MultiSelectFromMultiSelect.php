<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\WorkerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Pimcore\Db;
use Pimcore\Model\DataObject\Fieldcollection\Data\FilterMultiSelectFromMultiSelect;

class MultiSelectFromMultiSelect extends SelectFromMultiSelect
{
    public function getFilterValues(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, array $currentFilter): array
    {
        $field = $this->getField($filterDefinition);

        $useAndCondition = false;

        if (!$filterDefinition instanceof FilterMultiSelectFromMultiSelect) {
            throw new InvalidConfigException('invalid configuration');
        }

        $useAndCondition = $filterDefinition->getUseAndCondition();

        $rawValues = $productList->getGroupByValues($field, true, !$useAndCondition);

        $values = [];
        foreach ($rawValues as $v) {
            $explode = explode(WorkerInterface::MULTISELECT_DELIMITER, $v['value']);
            foreach ($explode as $e) {
                if (!empty($e)) {
                    if (!empty($values[$e])) {
                        $values[$e]['count'] += $v['count'];
                    } else {
                        $values[$e] = ['value' => $e, 'count' => $v['count']];
                    }
                }
            }
        }

        return [
            'hideFilter' => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            'label' => $filterDefinition->getLabel(),
            'currentValue' => $currentFilter[$field],
            'values' => array_values($values),
            'fieldname' => $field,
            'metaData' => $filterDefinition->getMetaData(),
            'resultCount' => $productList->count(),
        ];
    }

    /**
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param ProductListInterface $productList
     * @param array $currentFilter
     * @param array $params
     * @param bool $isPrecondition
     *
     * @return string[]
     */
    public function addCondition(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, $currentFilter, $params, $isPrecondition = false)
    {
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);

        $value = $params[$field] ?? null;
        $isReload = $params['is_reload'] ?? null;

        if (empty($value) && !$isReload) {
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
            $db = Db::get();
            foreach ($value as $v) {
                $v = '%' . WorkerInterface::MULTISELECT_DELIMITER  . $v .  WorkerInterface::MULTISELECT_DELIMITER . '%' ;
                $quotedValues[] = $field . ' like '.$db->quote($v);
            }

            $useAndCondition = false;
            if (method_exists($filterDefinition, 'getUseAndCondition')) {
                $useAndCondition = $filterDefinition->getUseAndCondition();
            }

            if ($useAndCondition) {
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
