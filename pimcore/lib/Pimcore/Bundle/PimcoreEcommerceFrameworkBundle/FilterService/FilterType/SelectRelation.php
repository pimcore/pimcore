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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Pimcore\Logger;

class SelectRelation extends AbstractFilterType
{
    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter)
    {
        $field = $this->getField($filterDefinition);


        $values = $productList->getGroupByRelationValues($field, true);

        $objects = [];
        Logger::info("Load Objects...");

        $availableRelations = [];
        if ($filterDefinition->getAvailableRelations()) {
            $availableRelations = $this->loadAllAvailableRelations($filterDefinition->getAvailableRelations());
        }

        foreach ($values as $v) {
            if (empty($availableRelations) || $availableRelations[$v['value']] === true) {
                $objects[$v['value']] = \Pimcore\Model\Object\AbstractObject::getById($v['value']);
            }
        }
        Logger::info("done.");

        if ($filterDefinition->getScriptPath()) {
            $script = $filterDefinition->getScriptPath();
        } else {
            $script = $this->script;
        }

        return $this->render($script, [
            "hideFilter" => $filterDefinition->getRequiredFilterField() && empty($currentFilter[$filterDefinition->getRequiredFilterField()]),
            "label" => $filterDefinition->getLabel(),
            "currentValue" => $currentFilter[$field],
            "values" => $values,
            "objects" => $objects,
            "fieldname" => $field,
            "metaData" => $filterDefinition->getMetaData(),
            "resultCount" => $productList->count()
        ]);
    }

    protected function loadAllAvailableRelations($availableRelations, $availableRelationsArray = [])
    {
        foreach ($availableRelations as $rel) {
            if ($rel instanceof \Pimcore\Model\Object\Folder) {
                $availableRelationsArray = $this->loadAllAvailableRelations($rel->getChilds(), $availableRelationsArray);
            } else {
                $availableRelationsArray[$rel->getId()] = true;
            }
        }

        return $availableRelationsArray;
    }


    public function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);


        $value = $params[$field];

        if (empty($value) && !$params['is_reload']) {
            $o = $preSelect;
            if (!empty($o)) {
                if (is_object($o)) {
                    $value = $o->getId();
                } else {
                    $value = $o;
                }
            }
        } elseif ($value == AbstractFilterType::EMPTY_STRING) {
            $value = null;
        }

        $currentFilter[$field] = $value;


        if (!empty($value)) {
            //            if($isPrecondition) {
//                $productList->addRelationCondition("PRECONDITION_" . $filterDefinition->getField(),  "dest = " . $productList->quote($value));
//            } else {
                $productList->addRelationCondition($field, "dest = " . $productList->quote($value));
//            }
        }

        return $currentFilter;
    }
}
