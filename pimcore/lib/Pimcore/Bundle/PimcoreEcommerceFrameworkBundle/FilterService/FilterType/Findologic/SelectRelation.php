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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\Findologic;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;
use Pimcore\Logger;

class SelectRelation extends \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\SelectRelation
{
    public function prepareGroupByValues(AbstractFilterDefinitionType $filterDefinition, IProductList $productList)
    {
        //$productList->prepareGroupByValues($this->getField($filterDefinition), true);
    }

    public function getFilterFrontend(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter)
    {
        $field = $this->getField($filterDefinition);


        $values = $productList->getGroupByValues($field, true);

        $objects = [];
        Logger::info("Load Objects...");

        $availableRelations = [];
        if ($filterDefinition->getAvailableRelations()) {
            $availableRelations = $this->loadAllAvailableRelations($filterDefinition->getAvailableRelations());
        }

        foreach ($values as $v) {
            if (empty($availableRelations) || $availableRelations[$v['label']] === true) {
                $objects[$v['label']] = \Pimcore\Model\Object\AbstractObject::getById($v['label']);
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
            "resultCount" => $productList->count()
        ]);
    }

    public function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        $field = $this->getField($filterDefinition);
        $preSelect = $this->getPreSelect($filterDefinition);

        $value = $params[$field];

        if ($value == \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } elseif (empty($value) && !$params['is_reload']) {
            $value = $preSelect;
        }

        $value = trim($value);
        $currentFilter[$field] = $value;


        if (!empty($value)) {
            $productList->addCondition([$value], $field);
        }

        return $currentFilter;
    }
}
