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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\WorkerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Pimcore\Db;

class SelectFromMultiSelect extends AbstractFilterType
{
    public function getFilterValues(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, array $currentFilter): array
    {
        $field = $this->getField($filterDefinition);
        $rawValues = $productList->getGroupByValues($field, true);

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

    public function addCondition(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, $currentFilter, $params, $isPrecondition = false)
    {
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);

        $value = $params[$field] ?? null;
        $isReload = $params['is_reload'] ?? null;

        if ($value == AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } elseif (empty($value) && !$isReload) {
            $value = $preSelect;
        }

        $value = trim($value);

        $currentFilter[$field] = $value;
        $db = Db::get();

        if (!empty($value)) {
            $value = '%' . WorkerInterface::MULTISELECT_DELIMITER  . $value .  WorkerInterface::MULTISELECT_DELIMITER . '%';
            if ($isPrecondition) {
                $productList->addCondition($field . ' LIKE ' . $db->quote($value), 'PRECONDITION_' . $field);
            } else {
                $productList->addCondition($field . ' LIKE ' . $db->quote($value), $field);
            }
        }

        return $currentFilter;
    }
}
