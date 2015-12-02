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


class OnlineShop_Framework_FilterService_Findologic_MultiSelect extends OnlineShop_Framework_FilterService_MultiSelect
{
    public function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter) {
        //return "";
        $field = $this->getField($filterDefinition);

        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }

        $values = [];
        foreach ($productList->getGroupByValues($this->getField($filterDefinition), true) as $value) {
            $values[] = ['value' => $value['label'],
                'count' => $value['count']];
        }


        // add current filter. workaround for findologic behavior
        if(array_key_exists($field, $currentFilter) && $currentFilter[$field] != null)
        {
            foreach($currentFilter[$field] as $value)
            {
                $add = true;
                foreach($values as $v)
                {
                    if($v['value'] == $value)
                    {
                        $add = false;
                        break;
                    }
                }

                if($add)
                {
                    array_unshift($values, [
                        'value' => $value
                        , 'label' => $value
                        , 'count' => null
                        , 'parameter' => null
                    ]);
                }
            }
        }



        return $this->view->partial($script, array(
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$field],
            "values" => $values,
            "fieldname" => $field
        ));
    }

    /**
     * @param OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition
     * @param OnlineShop_Framework_IProductList                 $productList
     * @param array                                             $currentFilter
     * @param array                                             $params
     * @param bool                                              $isPrecondition
     *
     * @return mixed
     */
    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        // init
        $field = $this->getField($filterDefinition);
        $value = $params[$field];


        // set defaults
        if(empty($value) && !$params['is_reload'] && ($preSelect = $this->getPreSelect($filterDefinition)))
        {
            $value = explode(",", $preSelect);
        }


        if(!empty($value) && in_array(OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING, $value)) {
            $value = [];
        }

        $currentFilter[$field] = $value;

        if($value)
        {
            $productList->addCondition($value, $field);
        }

        return $currentFilter;
    }
}
