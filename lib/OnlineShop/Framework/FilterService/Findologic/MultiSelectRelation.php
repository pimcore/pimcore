<?php

class OnlineShop_Framework_FilterService_Findologic_MultiSelectRelation extends OnlineShop_Framework_FilterService_MultiSelectRelation
{
    public function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter) {
        //return "";
        $field = $this->getField($filterDefinition);

        $values = $productList->getGroupByRelationValues($field, true, !$filterDefinition->getUseAndCondition());


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
        Logger::log("Load Objects...", Zend_Log::INFO);
        $availableRelations = array();
        if($filterDefinition->getAvailableRelations()) {
            $availableRelations = $this->loadAllAvailableRelations($filterDefinition->getAvailableRelations());
        }

        foreach($values as $v) {
            if(empty($availableRelations) || $availableRelations[$v['value']] === true) {
                $objects[$v['value']] = \Pimcore\Model\Object\AbstractObject::getById($v['value']);
            }
        }
        Logger::log("done.", Zend_Log::INFO);

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


    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter, $params, $isPrecondition = false)
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

        } else if(!empty($value) && in_array(OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING, $value)) {
            $value = null;
        }

        $currentFilter[$field] = $value;

        if(!empty($value)) {
            $productList->addRelationCondition($field, $value);
        }
        return $currentFilter;
    }
}
