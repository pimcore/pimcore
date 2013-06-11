<?php

class OnlineShop_Framework_FilterService_MultiSelectFromMultiSelect extends OnlineShop_Framework_FilterService_SelectFromMultiSelect
{
    /**
     * @param OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition
     * @param OnlineShop_Framework_ProductList                  $productList
     * @param                                                   $currentFilter
     *
     * @return string[]
     */
    public function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter) {
        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }

        $rawValues = $productList->getGroupByValues($filterDefinition->getField(), true, !$filterDefinition->getUseAndCondition());

        $values = array();
        foreach($rawValues as $v) {
            $explode = explode(OnlineShop_Framework_IndexService_Tenant_Worker::MULTISELECT_DELIMITER, $v['value']);
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
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$filterDefinition->getField()],
            "values" => array_values($values),
            "fieldname" => $filterDefinition->getField()
        ));
    }


    /**
     * @param OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition
     * @param OnlineShop_Framework_ProductList                  $productList
     * @param array                                             $currentFilter
     * @param                                                   $params
     * @param bool                                              $isPrecondition
     *
     * @return string[]
     */
    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $value = $params[$filterDefinition->getField()];


        if(empty($value)) {
            $value = explode(",", $filterDefinition->getPreSelect());

            foreach($value as $key => $v) {
                if(!$v) {
                    unset($value[$key]);
                }
            }
        } else if(in_array(OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING, $value)) {
            $value = null;
        }

      //  $value = trim($value);

        $currentFilter[$filterDefinition->getField()] = $value;


        if(!empty($value)) {


            $quotedValues = array();
            foreach($value as $v) {
                $v =   "%" . OnlineShop_Framework_IndexService_Tenant_Worker::MULTISELECT_DELIMITER  . $v .  OnlineShop_Framework_IndexService_Tenant_Worker::MULTISELECT_DELIMITER . "%" ;
                $quotedValues[] = $filterDefinition->getField(). ' like '.$productList->quote($v);
            }

            if($filterDefinition->getUseAndCondition()) {
                $quotedValues = implode(' and ', $quotedValues);
            } else {
                $quotedValues = implode(' or ', $quotedValues);
            }
            $quotedValues = '('.$quotedValues.')';

            if(!empty($quotedValues)) {

                if($isPrecondition) {
                    $productList->addCondition($quotedValues, "PRECONDITION_" . $filterDefinition->getField());
                } else {
                    $productList->addCondition($quotedValues, $filterDefinition->getField());
                }
            }

        }
        return $currentFilter;
    }

}