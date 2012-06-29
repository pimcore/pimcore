<?php

class OnlineShop_Framework_FilterService_NumberRangeSelection extends OnlineShop_Framework_FilterService_AbstractFilterType {

    public function getFilterFrontend(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter) {
        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }

        $ranges = $filterDefinition->getRanges();

        $groupByValues = $productList->getGroupByValues($filterDefinition->getField(), true);

        $counts = array();
        foreach($ranges->getData() as $row) {
            $counts[$row['from'] . "_" . $row['to']] = 0;
        }


        foreach($groupByValues as $groupByValue) {
            if($groupByValue['value'] !== null) {
                $value = floatval($groupByValue['value']);

                if(!$value) {
                    $value = 0;
                }
                foreach($ranges->getData() as $row) {
                    if((empty($row['from']) || ($row['from'] <= $value)) && (empty($row['to']) || $row['to'] >= $value)) {
                        $counts[$row['from'] . "_" . $row['to']] += $groupByValue['count'];
                        break;
                    }
                }
            }
        }
        $values = array();
        foreach($ranges->getData() as $row) {
            if($counts[$row['from'] . "_" . $row['to']]) {
                $values[] = array("from" => $row['from'], "to" => $row['to'], "label" => $this->createLabel($row, $filterDefinition->getUnit()), "count" => $counts[$row['from'] . "_" . $row['to']], "unit" => $filterDefinition->getUnit());
            }
        }


        return $this->view->partial($script, array(
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $this->createLabel($currentFilter[$filterDefinition->getField()], $filterDefinition->getUnit()),
            "values" => $values,
            "definition" => $filterDefinition,
            "fieldname" => $filterDefinition->getField()
        ));
    }

    private function createLabel($data, $unit) {
        if(is_array($data)) {
            if(!empty($data['from'])) {
                if(!empty($data['to'])) {
                    return $this->view->translate($unit) . " " . $data['from'] . " - " . $data['to'];
                } else {
                    return $this->view->translate("more than " . $unit) . " " . $data['from'];
                }
            } else if(!empty($data['to'])) {
                return $this->view->translate("less than " . $unit) . " " . $data['to'];
            }
        } else {
            return "";
        }
    }

    public function addCondition(OnlineShop_Framework_AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_ProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $rawValue = $params[$filterDefinition->getField()];



        if(!empty($rawValue)) {
            $values = explode("-", $rawValue);
            $value['from'] = trim($values[0]);
            $value['to'] = trim($values[1]);
        } else {
            $value['from'] = $filterDefinition->getPreSelectFrom();
            $value['to'] = $filterDefinition->getPreSelectTo();
        }

        $currentFilter[$filterDefinition->getField()] = $value;


        if(!empty($value)) {
            if(!empty($value['from'])) {

                if($isPrecondition) {
                    $productList->addCondition($filterDefinition->getField() . " >= " . $productList->quote($value['from']), "PRECONDITION_" . $filterDefinition->getField());
                } else {
                    $productList->addCondition($filterDefinition->getField() . " >= " . $productList->quote($value['from']), $filterDefinition->getField());
                }

            }
            if(!empty($value['to'])) {

                if($isPrecondition) {
                    $productList->addCondition($filterDefinition->getField() . " <= " . $productList->quote($value['to']), "PRECONDITION_" . $filterDefinition->getField());
                } else {
                    $productList->addCondition($filterDefinition->getField() . " <= " . $productList->quote($value['to']), $filterDefinition->getField());
                }

            }
        }
        return $currentFilter;
    }
}
