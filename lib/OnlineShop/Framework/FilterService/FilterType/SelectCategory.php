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


namespace OnlineShop\Framework\FilterService\FilterType;

class SelectCategory extends AbstractFilterType {

    public function getFilterFrontend(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter) {
        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }

        $rawValues = $productList->getGroupByValues($filterDefinition->getField(), true);
        $values = array();

        $availableRelations = array();
        if($filterDefinition->getAvailableCategories()) {
            foreach($filterDefinition->getAvailableCategories() as $rel) {
                $availableRelations[$rel->getId()] = true;
            }
        }


        foreach($rawValues as $v) {
            $explode = explode(",", $v['value']);
            foreach($explode as $e) {
                if(!empty($e) && (empty($availableRelations) || $availableRelations[$e] === true)) {
                    if($values[$e]) {
                        $count = $values[$e]['count'] + $v['count'];
                    } else {
                        $count = $v['count'];
                    }
                    $values[$e] = array('value' => $e, "count" => $count);
                }
            }
        }

        return $this->view->partial($script, array(
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$filterDefinition->getField()],
            "values" => array_values($values),
            "fieldname" => $filterDefinition->getField(),
            "metaData" => $filterDefinition->getMetaData(),
            "rootCategory" => $filterDefinition->getRootCategory(),
            "document" => $this->view->document,
            "resultCount" => $productList->count()
        ));
    }

    public function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $value = $params[$filterDefinition->getField()];

        if($value == AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } else if(empty($value) && !$params['is_reload']) {
            $value = $filterDefinition->getPreSelect();
            if(is_object($value)) {
                $value = $value->getId();
            }
        }

        $currentFilter[$filterDefinition->getField()] = $value;

        if(!empty($value)) {

            $value = "%," . trim($value) . ",%";

            if($isPrecondition) {
                $productList->addCondition($filterDefinition->getField() . " LIKE " . $productList->quote($value), "PRECONDITION_" . $filterDefinition->getField());
            } else {
                $productList->addCondition($filterDefinition->getField() . " LIKE " . $productList->quote($value), $filterDefinition->getField());
            }

        }
        return $currentFilter;
    }
}
