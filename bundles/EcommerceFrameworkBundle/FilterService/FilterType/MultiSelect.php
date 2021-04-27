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
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Pimcore\Db;
use Pimcore\Model\DataObject\Fieldcollection\Data\FilterMultiSelect;

class MultiSelect extends AbstractFilterType
{
    public function getFilterValues(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, array $currentFilter): array
    {
        $field = $this->getField($filterDefinition);

        if (!$filterDefinition instanceof FilterMultiSelect) {
            throw new InvalidConfigException('invalid configuration');
        }

        $useAndCondition = $filterDefinition->getUseAndCondition();

        return [
            'hideFilter' => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            'label' => $filterDefinition->getLabel(),
            'currentValue' => $currentFilter[$field],
            'values' => $productList->getGroupByValues($field, true, !$useAndCondition),
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

        if (!empty($value)) {
            if (!is_array($value)) {
                $value = [$value];
            }
        }

        if (empty($value) && !$isReload) {
            if (!empty($preSelect) || $preSelect == '0') {
                $value = explode(',', $preSelect);
            }
        } elseif (!empty($value) && in_array(AbstractFilterType::EMPTY_STRING, $value)) {
            $value = null;
        }

        $currentFilter[$field] = $value;

        if (!empty($value)) {
            $quotedValues = [];
            $db = Db::get();
            foreach ($value as $v) {
                if (!empty($v)) {
                    $quotedValues[] = $db->quote($v);
                }
            }
            if (!empty($quotedValues)) {
                if (!$filterDefinition instanceof FilterMultiSelect) {
                    throw new InvalidConfigException('invalid configuration');
                }

                if ($filterDefinition->getUseAndCondition()) {
                    foreach ($quotedValues as $value) {
                        if ($isPrecondition) {
                            $productList->addCondition($field . ' = ' . $value, 'PRECONDITION_' . $field);
                        } else {
                            $productList->addCondition($field . ' = ' . $value, $field);
                        }
                    }
                } else {
                    if ($isPrecondition) {
                        $productList->addCondition($field . ' IN (' . implode(',', $quotedValues) . ')', 'PRECONDITION_' . $field);
                    } else {
                        $productList->addCondition($field . ' IN (' . implode(',', $quotedValues) . ')', $field);
                    }
                }
            }
        }

        return $currentFilter;
    }
}
