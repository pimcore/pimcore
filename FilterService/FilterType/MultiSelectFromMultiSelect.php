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

class MultiSelectFromMultiSelect extends \OnlineShop\Framework\FilterService\FilterType\SelectFromMultiSelect
{

    /**
     * @param \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition
     * @param \OnlineShop\Framework\IndexService\ProductList\IProductList                  $productList
     * @param                                                   $currentFilter
     *
     * @return string
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

        return $this->render($script, array(
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$field],
            "values" => array_values($values),
            "fieldname" => $field,
            "metaData" => $filterDefinition->getMetaData(),
            "resultCount" => $productList->count()
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
        } else if(!empty($value) && in_array(\OnlineShop\Framework\FilterService\FilterType\AbstractFilterType::EMPTY_STRING, $value)) {
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