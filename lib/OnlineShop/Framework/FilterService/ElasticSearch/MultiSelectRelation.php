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


class OnlineShop_Framework_FilterService_ElasticSearch_MultiSelectRelation extends OnlineShop_Framework_FilterService_MultiSelectRelation {

    public function prepareGroupByValues(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList) {
        $field = $this->getField($filterDefinition);
        $productList->prepareGroupByRelationValues($field, true, !$filterDefinition->getUseAndCondition());
    }

    public function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);


        $value = $params[$field];

        if(empty($value) && !$params['is_reload']) {
            $objects = $preSelect;
            $value = array();

            if(!is_array($objects)) {
                $objects = explode(",", $objects);
            }

            if (is_array($objects)){
                foreach($objects as $o) {
                    if(is_object($o)) {
                        $value[] = $o->getId();
                    } else {
                        $value[] = $o;
                    }
                }
            }

        } else if(!empty($value) && in_array(OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING, $value)) {
            $value = null;
        }

        $currentFilter[$field] = $value;

        if(!empty($value)) {
            $quotedValues = array();
            foreach($value as $v) {
                if(!empty($v)) {
                    $quotedValues[] = $v;
                }
            }
            if(!empty($quotedValues)) {
                if($filterDefinition->getUseAndCondition()) {
                    foreach ($quotedValues as $value) {
                        $productList->addRelationCondition($field, $value);
                    }
                } else {
                    $productList->addRelationCondition($field, ['terms' => [$field => $quotedValues]]);
                }
            }
        }
        return $currentFilter;
    }
}
