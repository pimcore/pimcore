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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType;

class MultiSelect extends AbstractFilterType {

    public function getFilterFrontend(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter) {
        //return "";
        $field = $this->getField($filterDefinition);

        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }
        return $this->view->partial($script, array(
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$field],
            "values" => $productList->getGroupByValues($field, true, !$filterDefinition->getUseAndCondition()),
            "fieldname" => $field,
            "metaData" => $filterDefinition->getMetaData(),
            "resultCount" => $productList->count()
        ));
    }

    public function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);

        $value = $params[$field];

        if(!empty($value)) {
            if(!is_array($value)) {
                $value = [$value];
            }
        }

        if(empty($value) && !$params['is_reload']) {
            if(!empty($preSelect) || $preSelect == '0') {
                $value = explode(",", $preSelect);
            }
        } else if(!empty($value) && in_array(AbstractFilterType::EMPTY_STRING, $value)) {
            $value = null;
        }

        $currentFilter[$field] = $value;

        if(!empty($value)) {
            $quotedValues = array();
            foreach($value as $v) {
                if(!empty($v)) {
                    $quotedValues[] = $productList->quote($v);
                }
            }
            if(!empty($quotedValues)) {
                if($filterDefinition->getUseAndCondition()) {
                    foreach ($quotedValues as $value) {
                        if($isPrecondition) {
                            $productList->addCondition($field . " = " . $value, "PRECONDITION_" . $field);
                        } else {
                            $productList->addCondition($field . " = " . $value, $field);
                        }
                    }
                } else {
                    if($isPrecondition) {
                        $productList->addCondition($field . " IN (" . implode(",", $quotedValues) . ")", "PRECONDITION_" . $field);
                    } else {
                        $productList->addCondition($field . " IN (" . implode(",", $quotedValues) . ")", $field);
                    }
                }



            }
        }
        return $currentFilter;
    }
}
