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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\FactFinder;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;

class SelectCategory extends \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\SelectCategory
{
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
        $preSelect = $this->getPreSelect($filterDefinition);
        $value = $params[$field];


        // set defaults
        //only works with Root categories!

         if (empty($value) && !$params['is_reload']) {
             $value[] = $preSelect->getId();
         }


//        $value = trim($value);
        $currentFilter[$field] = $value;


        // add condition
        if (!empty($value)) {
            $field = 'CategoryPathROOT';
            $lastId = null;
            foreach ($value as $id) {
                if ($lastId !== null) {
                    $field .= '/' . $lastId;
                }

                $productList->addCondition($id, $field);
                $lastId = $id;
            }
        }

        return $currentFilter;
    }


    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter)
    {
        // init
        $script = $filterDefinition->getScriptPath() ?: $this->script;
        $rawValues = $productList->getGroupByValues('CategoryPath', true);
        $values = [];
        
        
        // ...
        $availableRelations = [];
        if ($filterDefinition->getAvailableCategories()) {
            foreach ($filterDefinition->getAvailableCategories() as $rel) {
                $availableRelations[$rel->getId()] = true;
            }
        }

        
        // prepare values
        foreach ($rawValues as $v) {
            $explode = explode(",", $v['value']);
            foreach ($explode as $e) {
                if (!empty($e) && (empty($availableRelations) || $availableRelations[$e] === true)) {
                    if ($values[$e]) {
                        $count = $values[$e]['count'] + $v['count'];
                    } else {
                        $count = $v['count'];
                    }
                    $values[$e] = ['value' => $e, "count" => $count];
                }
            }
        }
        
        
        // done
        return $this->render($script, [
            'hideFilter' => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()])
            , 'label' => $filterDefinition->getLabel()
            , 'currentValue' => $currentFilter[$filterDefinition->getField()]
            , 'values' => array_values($values)
            , 'fieldname' => $filterDefinition->getField()
            , 'metaData' => $filterDefinition->getMetaData()
            , "resultCount" => $productList->count()
        ]);
    }
}
