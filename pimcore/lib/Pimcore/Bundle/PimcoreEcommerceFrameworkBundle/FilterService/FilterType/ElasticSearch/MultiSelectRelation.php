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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;

class MultiSelectRelation extends \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\MultiSelectRelation {

    public function prepareGroupByValues(AbstractFilterDefinitionType $filterDefinition, IProductList $productList) {
        $field = $this->getField($filterDefinition);
        $productList->prepareGroupByRelationValues($field, true, !$filterDefinition->getUseAndCondition());
    }

    public function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
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

        } else if(!empty($value) && in_array(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType::EMPTY_STRING, $value)) {
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
