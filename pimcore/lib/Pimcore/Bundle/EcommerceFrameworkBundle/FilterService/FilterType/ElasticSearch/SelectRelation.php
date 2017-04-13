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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\ElasticSearch;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\ProductList\IProductList;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractFilterDefinitionType;

class SelectRelation extends \Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\SelectRelation
{
    public function prepareGroupByValues(AbstractFilterDefinitionType $filterDefinition, IProductList $productList)
    {
        $productList->prepareGroupByValues($filterDefinition->getField(), true);
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
        } elseif ($value == \Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType::EMPTY_STRING) {
            $value = null;
        }

        $currentFilter[$field] = $value;

        if (!empty($value)) {
            $productList->addRelationCondition($field, $value);
        }

        return $currentFilter;
    }
}
