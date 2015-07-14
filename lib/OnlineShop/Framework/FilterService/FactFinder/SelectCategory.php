<?php

class OnlineShop_Framework_FilterService_FactFinder_SelectCategory extends OnlineShop_Framework_FilterService_SelectCategory
{
    /**
     * @param OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition
     * @param OnlineShop_Framework_IProductList                 $productList
     * @param array                                             $currentFilter
     * @param array                                             $params
     * @param bool                                              $isPrecondition
     *
     * @return mixed
     */
    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter, $params, $isPrecondition = false) {

        // init
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);
        $value = $params[$field];


        // set defaults
        //only works with Root categories!

         if(empty($value) && !$params['is_reload']) {
            $value[] = $preSelect->getId();
        }


//        $value = trim($value);
        $currentFilter[$field] = $value;


        // add condition
        if(!empty($value))
        {
            $field = 'CategoryPathROOT';
            $lastId = null;
            foreach($value as $id)
            {
                if($lastId !== null)
                {
                    $field .= '/' . $lastId;
                }

                $productList->addCondition($id, $field);
                $lastId = $id;
            }
        }

        return $currentFilter;
    }


    public function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter)
    {
        // init
        $script = $filterDefinition->getScriptPath() ?: $this->script;
        $rawValues = $productList->getGroupByValues('CategoryPath', true);
        $values = array();
        
        
        // ...
        $availableRelations = array();
        if($filterDefinition->getAvailableCategories()) {
            foreach($filterDefinition->getAvailableCategories() as $rel) {
                $availableRelations[$rel->getId()] = true;
            }
        }

        
        // prepare values
        foreach($rawValues as $v) {
            $explode = explode(",", $v['value']);
            foreach($explode as $e) {
                if(!empty($e) && (empty($availableRelations) || $availableRelations[$e] === true)) {
                    if($values[$e]) {
                        $count = $values[$e]['count'] + $v['count'];
                    } else {
                        $count = $v['count'];
                    }
                    $values[$e] = array('value' => $e, "count" => $count);
                }
            }
        }
        
        
        // done
        return $this->view->partial($script, [
            'hideFilter' => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()])
            , 'label' => $filterDefinition->getLabel()
            , 'currentValue' => $currentFilter[$filterDefinition->getField()]
            , 'values' => array_values($values)
            , 'fieldname' => $filterDefinition->getField()
            , 'metaData' => $filterDefinition->getMetaData()
        ]);
    }
}
