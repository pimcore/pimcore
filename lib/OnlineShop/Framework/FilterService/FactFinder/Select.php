<?php

class OnlineShop_Framework_FilterService_FactFinder_Select extends OnlineShop_Framework_FilterService_Select
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
     * @param array                                             $currentFilter
     * @param array                                             $params
     * @param bool                                              $isPrecondition
     *
     * @return array
     */
    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        // init
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);
        $value = $params[$field];


        // set defaults
        if($value == OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING){
            $value = null;
        } else if(empty($value) && !$params['is_reload']) {
            $value = $preSelect;
        }


        $value = trim($value);
        $currentFilter[$field] = $value;


        // add condition
        if(!empty($value))
        {
            $productList->addCondition(trim($value), $field);
        }

        return $currentFilter;
    }
}
