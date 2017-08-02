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
use Pimcore\Logger;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\Folder;

class MultiSelectRelation extends AbstractFilterType
{
    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter)
    {
        $field = $this->getField($filterDefinition);
        $values = $productList->getGroupByRelationValues($field, true, !$filterDefinition->getUseAndCondition());

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
        Logger::info('done.');

        return $this->render($this->getTemplate($filterDefinition), [
            'hideFilter' => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            'label' => $filterDefinition->getLabel(),
            'currentValue' => $currentFilter[$field],
            'values' => $values,
            'objects' => $objects,
            'fieldname' => $field,
            'metaData' => $filterDefinition->getMetaData(),
            'resultCount' => $productList->count()
        ]);
    }

    protected function loadAllAvailableRelations($availableRelations, $availableRelationsArray = [])
    {
        foreach ($availableRelations as $rel) {
            if ($rel instanceof Folder) {
                $availableRelationsArray = $this->loadAllAvailableRelations($rel->getChilds(), $availableRelationsArray);
            } else {
                $availableRelationsArray[$rel->getId()] = true;
            }
        }

        return $availableRelationsArray;
    }

    public function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);

        $value = $params[$field];

        if (empty($value) && !$params['is_reload']) {
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
            $value = null;
        }

        $currentFilter[$field] = $value;

        if (!empty($value)) {
            $quotedValues = [];
            foreach ($value as $v) {
                if (!empty($v)) {
                    $quotedValues[] = $productList->quote($v);
                }
            }
            if (!empty($quotedValues)) {
                if ($filterDefinition->getUseAndCondition()) {
                    foreach ($quotedValues as $value) {
                        $productList->addRelationCondition($field, 'dest = ' . $value);
                    }
                } else {
                    $productList->addRelationCondition($field, 'dest IN (' . implode(',', $quotedValues) . ')');
                }
            }
        }

        return $currentFilter;
    }
}
