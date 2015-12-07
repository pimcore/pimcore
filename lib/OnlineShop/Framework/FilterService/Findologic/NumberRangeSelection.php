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


class OnlineShop_Framework_FilterService_Findologic_NumberRangeSelection extends OnlineShop_Framework_FilterService_NumberRangeSelection {

    public function prepareGroupByValues(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList) {
        //$productList->prepareGroupByValues($this->getField($filterDefinition), true);
    }

    public function getFilterFrontend(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter) {
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
            if($groupByValue['label']) {
                $value = floatval($groupByValue['label']);

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
                $values[] = array("from" => $row['from'], "to" => $row['to'], "label" => $this->createLabel($row), "count" => $counts[$row['from'] . "_" . $row['to']], "unit" => $filterDefinition->getUnit());
            }
        }

        $currentValue = "";
        if($currentFilter[$filterDefinition->getField()]['from'] || $currentFilter[$filterDefinition->getField()]['to']) {
            $currentValue = implode($currentFilter[$filterDefinition->getField()], "-");
        }


        return $this->view->partial($script, array(
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentValue,
            "currentNiceValue" => $this->createLabel($currentFilter[$filterDefinition->getField()]),
            "unit" => $filterDefinition->getUnit(),
            "values" => $values,
            "definition" => $filterDefinition,
            "fieldname" => $filterDefinition->getField()
        ));
    }

    public function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, OnlineShop_Framework_IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $field = $filterDefinition->getField();
        $rawValue = $params[$field];

        if(!empty($rawValue) && $rawValue != OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING) {
            $values = explode("-", $rawValue);
            $value['from'] = trim($values[0]);
            $value['to'] = trim($values[1]);
        } else if($rawValue == OnlineShop_Framework_FilterService_AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } else {
            $value['from'] = $filterDefinition->getPreSelectFrom();
            $value['to'] = $filterDefinition->getPreSelectTo();
        }

        $currentFilter[$field] = $value;


        if($value['from'] || $value['to']) {
            $v = [];
            if($value['from']) {
                $v['min'] = $value['from'];
            }else {
                $v['min'] = 0;
            }

            if($value['to']) {
                $v['max'] = $value['to'];
            }else {
                $v['max'] = 9999999999999999;       // findologic won't accept only one of max or min, always needs both
            }
            $productList->addCondition($v, $field);
        }
        return $currentFilter;
    }

    private function createLabel($data) {
        if(is_array($data)) {
            if(!empty($data['from'])) {
                if(!empty($data['to'])) {
                    return $data['from'] . " - " . $data['to'];
                } else {
                    return $this->view->translate("more than") . " " . $data['from'];
                }
            } else if(!empty($data['to'])) {
                return $this->view->translate("less than") . " " . $data['to'];
            }
        } else {
            return "";
        }
    }
}
