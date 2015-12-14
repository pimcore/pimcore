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

namespace OnlineShop\Framework\FilterService\FilterType\ElasticSearch;

class Input extends \OnlineShop\Framework\FilterService\FilterType\Input {

    public function addCondition(\OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition, \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter, $params, $isPrecondition = false) {
        $field = $filterDefinition->getField($filterDefinition);
        $preSelect = $filterDefinition->getPreSelect($filterDefinition);

        $value = $params[$field];

        if($value == \OnlineShop\Framework\FilterService\FilterType\AbstractFilterType::EMPTY_STRING) {
            $value = null;
        } else if(empty($value) && !$params['is_reload']) {
            $value = $preSelect;
        }

        $value = trim($value);
        $currentFilter[$field] = $value;


        if(!empty($value)) {
            $value =  ".*\"" . $value .  "\".*";
            $productList->addCondition(['regexp' => ["attributes." . $field => $value]], $field);
        }
        return $currentFilter;
    }
}
