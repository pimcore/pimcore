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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Pimcore\Model\DataObject\Fieldcollection\Data\FilterNumberRange;

class NumberRange extends AbstractFilterType
{
    /**
     * @param FilterNumberRange $filterDefinition
     * @param ProductListInterface $productList
     * @param array $currentFilter
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, $currentFilter)
    {
        return $this->render($this->getTemplate($filterDefinition), [
              'hideFilter' => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
              'label' => $filterDefinition->getLabel(),
              'currentValue' => $currentFilter[$this->getField($filterDefinition)],
              'values' => $productList->getGroupByValues($this->getField($filterDefinition), true),
              'definition' => $filterDefinition,
              'fieldname' => $this->getField($filterDefinition),
              'metaData' => $filterDefinition->getMetaData(),
              'resultCount' => $productList->count(),
         ]);
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

        if (!empty($value)) {
            if (!empty($value['from'])) {
                if ($isPrecondition) {
                    $productList->addCondition($this->getField($filterDefinition) . ' >= ' . $productList->quote($value['from']), 'PRECONDITION_' . $this->getField($filterDefinition));
                } elseif ($value['from'] != AbstractFilterType::EMPTY_STRING) {
                    $productList->addCondition($this->getField($filterDefinition) . ' >= ' . $productList->quote($value['from']), $this->getField($filterDefinition));
                }
            }
            if (!empty($value['to'])) {
                if ($isPrecondition) {
                    $productList->addCondition($this->getField($filterDefinition) . ' <= ' . $productList->quote($value['to']), 'PRECONDITION_' . $this->getField($filterDefinition));
                } elseif ($value['to'] != AbstractFilterType::EMPTY_STRING) {
                    $productList->addCondition($this->getField($filterDefinition) . ' <= ' . $productList->quote($value['to']), $this->getField($filterDefinition));
                }
            }
        }

        return $currentFilter;
    }
}
