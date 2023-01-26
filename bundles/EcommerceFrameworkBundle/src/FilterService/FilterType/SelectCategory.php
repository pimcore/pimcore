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
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\FilterCategory;
use Pimcore\Model\Element\ElementInterface;

class SelectCategory extends AbstractFilterType
{
    /**
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param ProductListInterface $productList
     * @param array $currentFilter
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getFilterValues(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, array $currentFilter): array
    {
        $rawValues = $productList->getGroupByValues($filterDefinition->getField(), true);
        $values = [];

        $availableRelations = [];
        if (method_exists($filterDefinition, 'getAvailableCategories') && $filterDefinition->getAvailableCategories()) {
            /** @var Concrete $rel */
            foreach ($filterDefinition->getAvailableCategories() as $rel) {
                $availableRelations[$rel->getId()] = true;
            }
        }

        foreach ($rawValues as $v) {
            if ($v['value']) {
                $explode = array_map('intval', explode(',', $v['value']));
                foreach ($explode as $e) {
                    if (empty($availableRelations) || ($availableRelations[$e] ?? false)) {
                        if (!empty($values[$e])) {
                            $count = $values[$e]['count'] + $v['count'];
                        } else {
                            $count = $v['count'];
                        }
                        $values[$e] = ['value' => $e, 'count' => $count];
                    }
                }
            }
        }

        $request = \Pimcore::getContainer()->get('request_stack')->getCurrentRequest();

        return [
            'hideFilter' => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            'label' => $filterDefinition->getLabel(),
            'currentValue' => $currentFilter[$filterDefinition->getField()],
            'values' => array_values($values),
            'indexedValues' => $values,
            'fieldname' => $filterDefinition->getField(),
            'metaData' => $filterDefinition->getMetaData(),
            'rootCategory' => method_exists($filterDefinition, 'getRootCategory') ? $filterDefinition->getRootCategory() : null,
            'document' => $request->get('contentDocument'),
            'resultCount' => $productList->count(),
        ];
    }

    /**
     * @param FilterCategory $filterDefinition
     * @param ProductListInterface $productList
     * @param array $currentFilter
     * @param array $params
     * @param bool $isPrecondition
     *
     * @return array
     */
    public function addCondition(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, array $currentFilter, array $params, bool $isPrecondition = false): array
    {
        $value = $params[$filterDefinition->getField()] ?? null;
        $isReload = $params['is_reload'] ?? null;

        if ($value == AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } elseif (empty($value) && !$isReload && method_exists($filterDefinition, 'getPreSelect')) {
            $value = $filterDefinition->getPreSelect();
            if ($value instanceof ElementInterface) {
                $value = $value->getId();
            }
        }

        $currentFilter[$filterDefinition->getField()] = $value;

        if (!empty($value)) {
            $value = '%,' . trim((string)$value) . ',%';

            $db = Db::get();

            if ($isPrecondition) {
                $productList->addCondition($filterDefinition->getField() . ' LIKE ' . $db->quote($value), 'PRECONDITION_' . $filterDefinition->getField());
            } else {
                $productList->addCondition($filterDefinition->getField() . ' LIKE ' . $db->quote($value), $filterDefinition->getField());
            }
        }

        return $currentFilter;
    }
}
