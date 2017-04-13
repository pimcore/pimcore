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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\Findologic;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;

class MultiSelect extends \Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\MultiSelect
{
    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter)
    {
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
        if (array_key_exists($field, $currentFilter) && $currentFilter[$field] != null) {
            foreach ($currentFilter[$field] as $value) {
                $add = true;
                foreach ($values as $v) {
                    if ($v['value'] == $value) {
                        $add = false;
                        break;
                    }
                }

                if ($add) {
                    array_unshift($values, [
                        'value' => $value, 'label' => $value, 'count' => null, 'parameter' => null
                    ]);
                }
            }
        }

        return $this->render($script, [
            'hideFilter' => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            'label' => $filterDefinition->getLabel(),
            'currentValue' => $currentFilter[$field],
            'values' => $values,
            'fieldname' => $field,
            'resultCount' => $productList->count()
        ]);
    }

    /**
     * @param AbstractFilterDefinitionType $filterDefinition
     * @param IProductList                 $productList
     * @param array                                             $currentFilter
     * @param array                                             $params
     * @param bool                                              $isPrecondition
     *
     * @return mixed
     */
    public function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        // init
        $field = $this->getField($filterDefinition);
        $value = $params[$field];

        // set defaults
        if (empty($value) && !$params['is_reload'] && ($preSelect = $this->getPreSelect($filterDefinition))) {
            $value = explode(',', $preSelect);
        }

        if (!empty($value) && in_array(\Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType::EMPTY_STRING, $value)) {
            $value = [];
        }

        $currentFilter[$field] = $value;

        if ($value) {
            $productList->addCondition($value, $field);
        }

        return $currentFilter;
    }
}
