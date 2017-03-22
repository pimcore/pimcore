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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\Findologic;

class MultiSelectRelation extends \OnlineShop\Framework\FilterService\FilterType\MultiSelectRelation
{
    public function getFilterFrontend(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter) {
        //return "";
        $field = $this->getField($filterDefinition);

        $values = $productList->getGroupByValues($field, true, !$filterDefinition->getUseAndCondition());


        // add current filter. workaround for findologic behavior
        if(array_key_exists($field, $currentFilter) && $currentFilter[$field] != null)
        {
            foreach($currentFilter[$field] as $id)
            {
                $add = true;
                foreach($values as $v)
                {
                    if($v['value'] == $id)
                    {
                        $add = false;
                        break;
                    }
                }

                if($add)
                {
                    array_unshift($values, [
                        'value' => $id
                        , 'label' => $id
                        , 'count' => null
                        , 'parameter' => null
                    ]);
                }
            }
        }


        $objects = array();
        \Logger::info("Load Objects...");
        $availableRelations = array();
        if($filterDefinition->getAvailableRelations()) {
            $availableRelations = $this->loadAllAvailableRelations($filterDefinition->getAvailableRelations());
        }

        foreach($values as $v) {
            if(empty($availableRelations) || $availableRelations[$v['value']] === true) {
                $objects[$v['value']] = \Pimcore\Model\Object\AbstractObject::getById($v['value']);
            }
        }

        // sort result
        $values = $this->sortResult($filterDefinition, $values);

        \Logger::info("done.");

        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }
        return $this->render($script, array(
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$field],
            "values" => $values,
            "objects" => $objects,
            "fieldname" => $field,
            "resultCount" => $productList->count()
        ));
    }


    public function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
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

        } else if(!empty($value) && in_array(\OnlineShop\Framework\FilterService\FilterType\AbstractFilterType::EMPTY_STRING, $value)) {
            foreach($value as $k => $v)
            {
                if($v == \OnlineShop\Framework\FilterService\FilterType\AbstractFilterType::EMPTY_STRING)
                {
                    unset($value[$k]);
                }
            }
        }

        $currentFilter[$field] = $value;

        if(!empty($value)) {
            $productList->addRelationCondition($field, $value);
        }
        return $currentFilter;
    }
}
