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

class Input extends \Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\Input
{
    public function addCondition(AbstractFilterDefinitionType $filterDefinition, IProductList $productList, $currentFilter, $params, $isPrecondition = false)
    {
        $field = $filterDefinition->getField($filterDefinition);
        $preSelect = $filterDefinition->getPreSelect($filterDefinition);

        $value = $params[$field];

        if ($value == \Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } elseif (empty($value) && !$params['is_reload']) {
            $value = $preSelect;
        }

        $value = trim($value);
        $currentFilter[$field] = $value;

        if (!empty($value)) {
            $value =  '.*"' . $value .  '".*';
            $productList->addCondition(['regexp' => ['attributes.' . $field => $value]], $field);
        }

        return $currentFilter;
    }
}
