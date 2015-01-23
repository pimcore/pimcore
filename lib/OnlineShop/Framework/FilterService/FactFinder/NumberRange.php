<?php

class OnlineShop_Framework_FilterService_FactFinder_NumberRange extends OnlineShop_Framework_FilterService_NumberRange
{
    /**
     * @param OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition
     * @param OnlineShop_Framework_IProductList                 $productList
     */
    public function prepareGroupByValues(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList)
    {

    }


    /**
     * @param OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition
     * @param OnlineShop_Framework_IProductList                 $productList
     * @param                                                   $currentFilter
     * @param                                                   $params
     * @param bool                                              $isPrecondition
     *
     * @return mixed
     */
    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        // init
        $field = $this->getField($filterDefinition);
        $value = $params[$field];


        // set default preselect
        if(empty($value))
        {
            $value['from'] = $filterDefinition->getPreSelectFrom();
            $value['to'] = $filterDefinition->getPreSelectTo();

            $currentFilter[$field] = $value;
        }


        // add condition
        if(!empty($value))
        {
            $productList->addPriceCondition($value['from'], $value['to']);
        }

        return $currentFilter;
    }
}
