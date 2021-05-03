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
use Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRangeSelection;

class NumberRangeSelection extends AbstractFilterType
{
    /**
     * @param FilterNumberRangeSelection $filterDefinition
     * @param ProductListInterface $productList
     * @param array $currentFilter
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getFilterValues(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, array $currentFilter): array
    {
        $field = $this->getField($filterDefinition);
        $ranges = $filterDefinition->getRanges();

        $groupByValues = $productList->getGroupByValues($field, true);

        $counts = [];
        foreach ($ranges->getData() as $row) {
            $counts[$row['from'] . '_' . $row['to']] = 0;
        }

        foreach ($groupByValues as $groupByValue) {
            if ($groupByValue['value'] !== null) {
                $value = (float)$groupByValue['value'];

                if (!$value) {
                    $value = 0;
                }
                foreach ($ranges->getData() as $row) {
                    if ((empty($row['from']) || ((float)$row['from'] <= $value)) && (empty($row['to']) || (float)$row['to'] > $value)) {
                        $counts[$row['from'] . '_' . $row['to']] += $groupByValue['count'];
                        break;
                    }
                }
            }
        }
        $values = [];
        foreach ($ranges->getData() as $row) {
            if ($counts[$row['from'] . '_' . $row['to']]) {
                $values[] = ['from' => $row['from'], 'to' => $row['to'], 'label' => $this->createLabel($row), 'count' => $counts[$row['from'] . '_' . $row['to']], 'unit' => $filterDefinition->getUnit()];
            }
        }

        $currentValue = '';
        if ($currentFilter[$field]['from'] || $currentFilter[$field]['to']) {
            $currentValue = implode('-', $currentFilter[$field]);
        }

        return [
            'hideFilter' => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            'label' => $filterDefinition->getLabel(),
            'currentValue' => $currentValue,
            'currentNiceValue' => $this->createLabel($currentFilter[$field]),
            'unit' => $filterDefinition->getUnit(),
            'values' => $values,
            'definition' => $filterDefinition,
            'fieldname' => $field,
            'metaData' => $filterDefinition->getMetaData(),
            'resultCount' => $productList->count(),
        ];
    }

    private function createLabel($data)
    {
        if (is_array($data)) {
            if (!empty($data['from'])) {
                if (!empty($data['to'])) {
                    return $data['from'] . ' - ' . $data['to'];
                } else {
                    return $this->translator->trans('more than') . ' ' . $data['from'];
                }
            } elseif (!empty($data['to'])) {
                return $this->translator->trans('less than') . ' ' . $data['to'];
            }
        } else {
            return '';
        }
    }

    /**
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param ProductListInterface $productList
     * @param array $currentFilter
     * @param array $params
     * @param bool $isPrecondition
     *
     * @return array
     */
    public function addCondition(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, $currentFilter, $params, $isPrecondition = false)
    {
        if (!$filterDefinition instanceof FilterNumberRangeSelection) {
            throw new InvalidConfigException('excpected a FilterNumberRangeSelection filter');
        }
        $field = $this->getField($filterDefinition);
        $rawValue = $params[$field] ?? null;

        if (!empty($rawValue) && $rawValue != AbstractFilterType::EMPTY_STRING && is_string($rawValue)) {
            $values = explode('-', $rawValue);
            $value['from'] = trim($values[0]);
            $value['to'] = trim($values[1]);
        } elseif ($rawValue == AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } else {
            $value['from'] = $filterDefinition->getPreSelectFrom();
            $value['to'] = $filterDefinition->getPreSelectTo();
        }

        $currentFilter[$field] = $value;

        $db = Db::get();

        if (!empty($value)) {
            if (!empty($value['from'])) {
                if ($isPrecondition) {
                    $productList->addCondition($field . ' >= ' . $db->quote($value['from']), 'PRECONDITION_' . $field);
                } else {
                    $productList->addCondition($field . ' >= ' . $db->quote($value['from']), $field);
                }
            }
            if (!empty($value['to'])) {
                if ($isPrecondition) {
                    $productList->addCondition($field . ' <= ' . $db->quote($value['to']), 'PRECONDITION_' . $field);
                } else {
                    $productList->addCondition($field . ' < ' . $db->quote($value['to']), $field);
                }
            }
        }

        return $currentFilter;
    }
}
