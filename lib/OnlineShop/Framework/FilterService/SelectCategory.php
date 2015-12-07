<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


class OnlineShop_Framework_FilterService_SelectCategory extends OnlineShop_Framework_FilterService_AbstractFilterType {

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
            "rootCategory" => $filterDefinition->getRootCategory()
        ));
    }

    public function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $value = $params[$filterDefinition->getField()];

        if($value == OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING) {
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
