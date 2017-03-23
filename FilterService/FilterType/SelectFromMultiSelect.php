<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType;


use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Worker\IWorker;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;

class SelectFromMultiSelect extends AbstractFilterType {

    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter) {
        //return "";
        $field = $this->getField($filterDefinition);

        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }

        $rawValues = $productList->getGroupByValues($field, true);

        $values = array();
        foreach($rawValues as $v) {
            $explode = explode(IWorker::MULTISELECT_DELIMITER, $v['value']);
            foreach($explode as $e) {
                if(!empty($e)) {
                    if($values[$e]) {
                        $values[$e]['count'] += $v['count'];
                    } else {
                        $values[$e] = array('value' => $e, "count" => $v['count']);
                    }
                }
            }
        }

        return $this->render($script, array(
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$field],
            "values" => array_values($values),
            "fieldname" => $field,
            "metaData" => $filterDefinition->getMetaData(),
            "resultCount" => $productList->count()
        ));
    }

    public function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);

        $value = $params[$field];

        if($value == AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } else if(empty($value) && !$params['is_reload']) {
            $value = $preSelect;
        }

        $value = trim($value);

        $currentFilter[$field] = $value;


        if(!empty($value)) {
            $value =  "%" . IWorker::MULTISELECT_DELIMITER  . $value .  IWorker::MULTISELECT_DELIMITER . "%";
            if($isPrecondition) {
                $productList->addCondition($field . " LIKE " . $productList->quote($value), "PRECONDITION_" . $field);
            } else {
                $productList->addCondition($field . " LIKE " . $productList->quote($value), $field);
            }

        }
        return $currentFilter;
    }
}
