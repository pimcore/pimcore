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
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;

class MultiSelectCategory extends AbstractFilterType
{
    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter)
    {
        $rawValues = $productList->getGroupByValues($filterDefinition->getField(), true);
        $values = [];

        $availableRelations = [];
        if ($filterDefinition->getAvailableCategories()) {
            foreach ($filterDefinition->getAvailableCategories() as $rel) {
                $availableRelations[$rel->getId()] = true;
            }
        }

        foreach ($rawValues as $v) {
            $explode = explode(',', $v['value']);
            foreach ($explode as $e) {
                if (!empty($e) && (empty($availableRelations) || $availableRelations[$e] === true)) {
                    if ($values[$e]) {
                        $count = $values[$e]['count'] + $v['count'];
                    } else {
                        $count = $v['count'];
                    }
                    $values[$e] = ['value' => $e, 'count' => $count];
                }
            }
        }

        return $this->render($this->getTemplate($filterDefinition), [
            'hideFilter' => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            'label' => $filterDefinition->getLabel(),
            'currentValue' => $currentFilter[$filterDefinition->getField()],
            'values' => array_values($values),
            'fieldname' => $filterDefinition->getField(),
            'metaData' => $filterDefinition->getMetaData(),
            'resultCount' => $productList->count()
        ]);
    }

    public function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        $value = $params[$filterDefinition->getField()];

        if ($value == AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } elseif (empty($value) && !$params['is_reload']) {
            $value = $filterDefinition->getPreSelect();
        }

        $currentFilter[$filterDefinition->getField()] = $value;

        $conditions = [];
        if (!empty($value)) {
            foreach ($value as $category) {
                if (is_object($category)) {
                    $category = $category->getId();
                }

                $category = '%,' . trim($category) . ',%';

                $conditions[] = $filterDefinition->getField() . ' LIKE ' . $productList->quote($category);
            }
        }

        if (sizeof($conditions)) {
            if ($filterDefinition->getUseAndCondition()) {
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
