<?php

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

        if($value === null) {
            $value = [];
        }

        $currentFilter[$field] = $value;

        $productList->addCondition($value, $field);

        return $currentFilter;
    }
}
