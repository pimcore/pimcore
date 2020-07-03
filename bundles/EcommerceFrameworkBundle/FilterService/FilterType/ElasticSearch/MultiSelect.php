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

use Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Pimcore\Model\DataObject\Fieldcollection\Data\FilterMultiSelect;

class MultiSelect extends \Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\MultiSelect
{
    public function prepareGroupByValues(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList)
    {
        $field = $this->getField($filterDefinition);
        $productList->prepareGroupByValues($field, true, !$filterDefinition->getUseAndCondition());
    }

    /**
     * @param FilterMultiSelect $filterDefinition
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
            foreach ($value as $v) {
                if (!empty($v)) {
                    $quotedValues[] = $v;
                }
            }

            $attributeConfig = $productList->getTenantConfig()->getAttributeConfig()[$field];
            if ($attributeConfig['type'] == 'boolean') {
                foreach ($quotedValues as $k => $v) {
                    $quotedValues[$k] = (bool)$v;
                }
            }

            if (!empty($quotedValues)) {
                if ($filterDefinition->getUseAndCondition()) {
                    foreach ($quotedValues as $value) {
                        $productList->addCondition($value, $field);
                    }
                } else {
                    $productList->addCondition(['terms' => ['attributes.' . $field => $quotedValues]], $field);
                }
            }
        }

        return $currentFilter;
    }
}
