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


class OnlineShop_Framework_FilterService_ElasticSearch_MultiSelectFromMultiSelect extends OnlineShop_Framework_FilterService_MultiSelectFromMultiSelect
{

    public function prepareGroupByValues(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList) {
        $field = $this->getField($filterDefinition);
        $productList->prepareGroupByValues($field, true, !$filterDefinition->getUseAndCondition());
    }


    /**
     * @param OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition
     * @param OnlineShop_Framework_IProductList                  $productList
     * @param array                                             $currentFilter
     * @param                                                   $params
     * @param bool                                              $isPrecondition
     *
     * @return string[]
     */
    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
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

        $currentFilter[$field] = $value;

        if(!empty($value)) {


            $quotedValues = array();
            foreach($value as $v) {
                if($v) {
                    $v =  ".*\"" . OnlineShop_Framework_IndexService_Tenant_IWorker::MULTISELECT_DELIMITER  . $v .  OnlineShop_Framework_IndexService_Tenant_IWorker::MULTISELECT_DELIMITER . "\".*";
                    $quotedValues[] = $v;
                }
            }

            if($quotedValues) {
                if($filterDefinition->getUseAndCondition()) {
                    foreach($quotedValues as $value) {
                        $productList->addCondition(['regexp' => ["attributes." . $field => $value]], $field);
                    }
                } else {
                    $regexArray = [];
                    foreach($quotedValues as $value) {
                        $regexArray[] = ['regexp' => ["attributes." . $field => $value]];
                    }

                    $productList->addCondition(['or' => ['filters' => $regexArray]], $field);
                }
            }
        }
        return $currentFilter;
    }

}