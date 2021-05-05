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
use Pimcore\Model\DataObject\Fieldcollection\Data\FilterCategoryMultiselect;
use Pimcore\Model\Element\ElementInterface;

class MultiSelectCategory extends AbstractFilterType
{
    public function getFilterValues(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, array $currentFilter): array
    {
        $rawValues = $productList->getGroupByValues($filterDefinition->getField(), true);
        $values = [];

        /** @var array<string, boolean> $availableRelations */
        $availableRelations = [];
        if (!$filterDefinition instanceof FilterCategoryMultiselect) {
            throw new InvalidConfigException('invalid configuration');
        }

        if ($filterDefinition->getAvailableCategories()) {
            /** @var ElementInterface $rel */
            foreach ($filterDefinition->getAvailableCategories() as $rel) {
                $availableRelations[(string) $rel->getId()] = true;
            }
        }

        foreach ($rawValues as $v) {
            $explode = explode(',', $v['value']);
            foreach ($explode as $e) {
                if (!empty($e) && (empty($availableRelations) || $availableRelations[$e] === true)) {
                    if (!empty($values[$e])) {
                        $count = $values[$e]['count'] + $v['count'];
                    } else {
                        $count = $v['count'];
                    }
                    $values[$e] = ['value' => $e, 'count' => $count];
                }
            }
        }

        return [
            'hideFilter' => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            'label' => $filterDefinition->getLabel(),
            'currentValue' => $currentFilter[$filterDefinition->getField()],
            'values' => array_values($values),
            'fieldname' => $filterDefinition->getField(),
            'metaData' => $filterDefinition->getMetaData(),
            'resultCount' => $productList->count(),
        ];
    }

    public function addCondition(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, $currentFilter, $params, $isPrecondition = false)
    {
        $value = $params[$filterDefinition->getField()] ?? null;
        $isReload = $params['is_reload'] ?? null;

        if ($value == AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } elseif (empty($value) && !$isReload) {
            $preSelect = false;
            if (method_exists($filterDefinition, 'getPreSelect')) {
                $preSelect = $filterDefinition->getPreSelect();
            }

            $value = $preSelect;
        }

        $currentFilter[$filterDefinition->getField()] = $value;

        $conditions = [];
        if (!empty($value)) {
            $db = Db::get();
            foreach ($value as $category) {
                if (is_object($category)) {
                    $category = $category->getId();
                }

                $category = '%,' . trim($category) . ',%';

                $conditions[] = $filterDefinition->getField() . ' LIKE ' . $db->quote($category);
            }
        }

        if (count($conditions)) {
            $useAndCondition = false;
            if (method_exists($filterDefinition, 'getUseAndCondition')) {
                $useAndCondition = $filterDefinition->getUseAndCondition();
            }

            if ($useAndCondition) {
                $conditions = implode(' AND ', $conditions);
            } else {
                $conditions = '(' . implode(' OR ', $conditions) . ')';
            }

            if ($isPrecondition) {
                $productList->addCondition($conditions, 'PRECONDITION_' . $filterDefinition->getField());
            } else {
                $productList->addCondition($conditions, $filterDefinition->getField());
            }
        }

        return $currentFilter;
    }
}
