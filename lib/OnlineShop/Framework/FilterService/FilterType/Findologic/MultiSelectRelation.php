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

namespace OnlineShop\Framework\FilterService\FilterType\Findologic;

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
        \Logger::log("Load Objects...", \Zend_Log::INFO);
        $availableRelations = array();
        if($filterDefinition->getAvailableRelations()) {
            $availableRelations = $this->loadAllAvailableRelations($filterDefinition->getAvailableRelations());
        }

        foreach($values as $v) {
            if(empty($availableRelations) || $availableRelations[$v['value']] === true) {
                $objects[$v['value']] = \Pimcore\Model\Object\AbstractObject::getById($v['value']);
            }
        }
        \Logger::log("done.", \Zend_Log::INFO);

        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }
        return $this->view->partial($script, array(
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$field],
            "values" => $values,
            "objects" => $objects,
            "fieldname" => $field
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
