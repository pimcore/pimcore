<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Pimcore\Db;

class NumberRange extends AbstractFilterType
{
    /** @inheritDoc */
    public function getFilterValues(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, array $currentFilter): array
    {
        return [
              'hideFilter' => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
              'label' => $filterDefinition->getLabel(),
              'currentValue' => $currentFilter[$this->getField($filterDefinition)],
              'values' => $productList->getGroupByValues($this->getField($filterDefinition), true),
              'definition' => $filterDefinition,
              'fieldname' => $this->getField($filterDefinition),
              'metaData' => $filterDefinition->getMetaData(),
              'resultCount' => $productList->count(),
         ];
    }

    public function addCondition(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, array $currentFilter, array $params, bool $isPrecondition = false): array
    {
        $field = $this->getField($filterDefinition);
        $value = $params[$field] ?? null;

        if (empty($value)) {
            $value['from'] = method_exists($filterDefinition, 'getPreSelectFrom') ? $filterDefinition->getPreSelectFrom() : null;
            $value['to'] = method_exists($filterDefinition, 'getPreSelectTo') ? $filterDefinition->getPreSelectTo() : null;
        }

        $currentFilter[$field] = $value;

        $db = Db::get();

        if (!empty($value['from'])) {
            if ($isPrecondition) {
                $productList->addCondition($this->getField($filterDefinition) . ' >= ' . $db->quote($value['from']), 'PRECONDITION_' . $this->getField($filterDefinition));
            } elseif ($value['from'] != AbstractFilterType::EMPTY_STRING) {
                $productList->addCondition($this->getField($filterDefinition) . ' >= ' . $db->quote($value['from']), $this->getField($filterDefinition));
            }
        }
        if (!empty($value['to'])) {
            if ($isPrecondition) {
                $productList->addCondition($this->getField($filterDefinition) . ' <= ' . $db->quote($value['to']), 'PRECONDITION_' . $this->getField($filterDefinition));
            } elseif ($value['to'] != AbstractFilterType::EMPTY_STRING) {
                $productList->addCondition($this->getField($filterDefinition) . ' <= ' . $db->quote($value['to']), $this->getField($filterDefinition));
            }
        }

        return $currentFilter;
    }
}
