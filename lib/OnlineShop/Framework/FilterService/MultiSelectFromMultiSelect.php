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


class OnlineShop_Framework_FilterService_MultiSelectFromMultiSelect extends OnlineShop_Framework_FilterService_SelectFromMultiSelect
{

    /**
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition
     * @param \OnlineShop\Framework\IndexService\ProductList\IProductList                  $productList
     * @param                                                   $currentFilter
     *
     * @return string[]
     */
    public function getFilterFrontend(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter) {

        $field = $this->getField($filterDefinition);

        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }

        $rawValues = $productList->getGroupByValues($field, true, !$filterDefinition->getUseAndCondition());

        $values = array();
        foreach($rawValues as $v) {
            $explode = explode(\OnlineShop\Framework\IndexService\Worker\IWorker::MULTISELECT_DELIMITER, $v['value']);
            foreach($explode as $e) {
                if(!empty($e)) {
                    if($values[$e]) {
                        $values[$e]['count'] += $v['count'];
                    } else {
                        $values[$e] = array('value' => $e, "count" => $v['count']);
                    }
                }
            }
        }

        return $this->view->partial($script, array(
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$field],
            "values" => array_values($values),
            "fieldname" => $field,
            "metaData" => $filterDefinition->getMetaData()
        ));
    }


    /**
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition
     * @param \OnlineShop\Framework\IndexService\ProductList\IProductList                  $productList
     * @param array                                             $currentFilter
     * @param                                                   $params
     * @param bool                                              $isPrecondition
     *
     * @return string[]
     */
    public function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);

        $value = $params[$field];


        if(empty($value) && !$params['is_reload']) {
            if(is_array($preSelect)) {
                $value = $preSelect;
            } else {
                $value = explode(",", $preSelect);
            }

            foreach($value as $key => $v) {
                if(!$v) {
                    unset($value[$key]);
                }
            }
        } else if(!empty($value) && in_array(OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING, $value)) {
            $value = null;
        }

      //  $value = trim($value);

        $currentFilter[$field] = $value;


        if(!empty($value)) {


            $quotedValues = array();
            foreach($value as $v) {
                $v =   "%" . \OnlineShop\Framework\IndexService\Worker\IWorker::MULTISELECT_DELIMITER  . $v .  \OnlineShop\Framework\IndexService\Worker\IWorker::MULTISELECT_DELIMITER . "%" ;
                $quotedValues[] = $field . ' like '.$productList->quote($v);
            }

            if($filterDefinition->getUseAndCondition()) {
                $quotedValues = implode(' and ', $quotedValues);
            } else {
                $quotedValues = implode(' or ', $quotedValues);
            }
            $quotedValues = '('.$quotedValues.')';

            if(!empty($quotedValues)) {

                if($isPrecondition) {
                    $productList->addCondition($quotedValues, "PRECONDITION_" . $field);
                } else {
                    $productList->addCondition($quotedValues, $field);
                }
            }

        }
        return $currentFilter;
    }

}