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

namespace OnlineShop\Framework\FilterService\FilterType;

class NumberRange extends AbstractFilterType {

    public function getFilterFrontend(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter) {
        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }
        return $this->view->partial($script, array(
                                                  "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
                                                  "label" => $filterDefinition->getLabel(),
                                                  "currentValue" => $currentFilter[$this->getField($filterDefinition)],
                                                  "values" => $productList->getGroupByValues($this->getField($filterDefinition), true),
                                                  "definition" => $filterDefinition,
                                                  "fieldname" => $this->getField($filterDefinition),
                                                  "metaData" => $filterDefinition->getMetaData()
                                             ));
    }

    public function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $field = $this->getField($filterDefinition);
        $value = $params[$field];

        if(empty($value)) {
            $value['from'] = $filterDefinition->getPreSelectFrom();
            $value['to'] = $filterDefinition->getPreSelectTo();
        }

        $currentFilter[$field] = $value;

        if(!empty($value)) {
            if(!empty($value['from'])) {

                if($isPrecondition) {
                    $productList->addCondition($this->getField($filterDefinition) . " >= " . $productList->quote($value['from']), "PRECONDITION_" . $this->getField($filterDefinition));
                } else {
                    $productList->addCondition($this->getField($filterDefinition) . " >= " . $productList->quote($value['from']), $this->getField($filterDefinition));
                }

            }
            if(!empty($value['to'])) {

                if($isPrecondition) {
                    $productList->addCondition($this->getField($filterDefinition) . " <= " . $productList->quote($value['to']), "PRECONDITION_" . $this->getField($filterDefinition));
                } else {
                    $productList->addCondition($this->getField($filterDefinition) . " <= " . $productList->quote($value['to']), $this->getField($filterDefinition));
                }

            }
        }
        return $currentFilter;
    }
}
