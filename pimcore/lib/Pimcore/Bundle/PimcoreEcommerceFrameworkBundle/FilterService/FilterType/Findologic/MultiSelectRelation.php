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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\Findologic;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Pimcore\Logger;

class MultiSelectRelation extends \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\MultiSelectRelation
{
    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter)
    {
        //return "";
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
                        'value' => $id
                        , 'label' => $id
                        , 'count' => null
                        , 'parameter' => null
                    ]);
                }
            }
        }


        $objects = [];
        Logger::info("Load Objects...");
        $availableRelations = [];
        if ($filterDefinition->getAvailableRelations()) {
            $availableRelations = $this->loadAllAvailableRelations($filterDefinition->getAvailableRelations());
        }

        foreach ($values as $v) {
            if (empty($availableRelations) || $availableRelations[$v['value']] === true) {
                $objects[$v['value']] = \Pimcore\Model\Object\AbstractObject::getById($v['value']);
            }
        }

        // sort result
        $values = $this->sortResult($filterDefinition, $values);

        Logger::info("done.");

        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }

        return $this->render($script, [
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$field],
            "values" => $values,
            "objects" => $objects,
            "fieldname" => $field,
            "resultCount" => $productList->count()
        ]);
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
                $objects = explode(",", $objects);
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
        } elseif (!empty($value) && in_array(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType::EMPTY_STRING, $value)) {
            foreach ($value as $k => $v) {
                if ($v == \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType::EMPTY_STRING) {
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
