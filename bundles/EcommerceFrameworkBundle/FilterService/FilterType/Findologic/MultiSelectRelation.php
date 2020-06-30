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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\Findologic;

use Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\ProductListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Pimcore\Logger;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Fieldcollection\Data\FilterMultiRelation;

class MultiSelectRelation extends \Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\MultiSelectRelation
{
    /**
     * @param FilterMultiRelation $filterDefinition
     * @param ProductListInterface $productList
     * @param array $currentFilter
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, ProductListInterface $productList, $currentFilter)
    {
        $field = $this->getField($filterDefinition);
        $values = $productList->getGroupByValues($field, true, !$filterDefinition->getUseAndCondition());

        // add current filter. workaround for findologic behavior
        if (array_key_exists($field, $currentFilter) && $currentFilter[$field] != null) {
            foreach ($currentFilter[$field] as $id) {
                $add = true;
                foreach ($values as $v) {
                    if ($v['value'] == $id) {
                        $add = false;
                        break;
                    }
                }

                if ($add) {
                    array_unshift($values, [
                        'value' => $id, 'label' => $id, 'count' => null, 'parameter' => null,
                    ]);
                }
            }
        }

        $objects = [];
        Logger::info('Load Objects...');
        $availableRelations = [];
        if ($filterDefinition->getAvailableRelations()) {
            $availableRelations = $this->loadAllAvailableRelations($filterDefinition->getAvailableRelations());
        }

        foreach ($values as $v) {
            if (empty($availableRelations) || $availableRelations[$v['value']] === true) {
                $objects[$v['value']] = AbstractObject::getById($v['value']);
            }
        }

        // sort result
        $values = $this->sortResult($filterDefinition, $values);

        Logger::info('done.');

        return $this->render($this->getTemplate($filterDefinition), [
            'hideFilter' => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            'label' => $filterDefinition->getLabel(),
            'currentValue' => $currentFilter[$field],
            'values' => $values,
            'objects' => $objects,
            'fieldname' => $field,
            'resultCount' => $productList->count(),
        ]);
    }

    /**
     * @param FilterMultiRelation $filterDefinition
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

        if (empty($value) && !$isReload) {
            $objects = $preSelect;
            $value = [];

            if (!is_array($objects)) {
                $objects = explode(',', $objects);
            }

            if (is_array($objects)) {
                foreach ($objects as $o) {
                    if (is_object($o)) {
                        $value[] = $o->getId();
                    } else {
                        $value[] = $o;
                    }
                }
            }
        } elseif (!empty($value) && in_array(AbstractFilterType::EMPTY_STRING, $value)) {
            foreach ($value as $k => $v) {
                if ($v == AbstractFilterType::EMPTY_STRING) {
                    unset($value[$k]);
                }
            }
        }

        $currentFilter[$field] = $value;

        if (!empty($value)) {
            $productList->addRelationCondition($field, $value);
        }

        return $currentFilter;
    }
}
