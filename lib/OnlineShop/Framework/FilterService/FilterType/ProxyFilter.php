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

/**
 * @deprecated
 *
 * Class ProxyFilter
 * @package OnlineShop\Framework\FilterService\FilterType
 */
class ProxyFilter extends AbstractFilterType
{
    /** @var $proxy AbstractFilterType*/
    private $proxy;
    protected  $field;


    function __construct($view, $script,$config)
    {
        parent::__construct($view,$script,$config);
        if (!$config->proxyclass){
            throw new \Exception("wrong configuration for " .  __CLASS__ . ": config setting proxyclass is missing!");
        }
        if (!$config->field){
            throw new \Exception("wrong configuration for " .  __CLASS__ . ": config setting field is missing!");
        }

        $this->proxy = new $config->proxyclass($view,$script,$config);
        $this->field= $config->field;
    }

    public function getFilterFrontend(
        \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition,
        \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter
    )
    {
        $filterDefinition->field=$this->field;
        return $this->proxy->getFilterFrontend($filterDefinition,$productList,$currentFilter);
    }

    public function addCondition(
        \OnlineShop\Framework\Model\AbstractFilterDefinitionType $filterDefinition,
        \OnlineShop\Framework\IndexService\ProductList\IProductList $productList, $currentFilter, $params,
        $isPrecondition = false
    ) {
        $filterDefinition->field=$this->field;
        return $this->proxy->addCondition($filterDefinition,$productList,$currentFilter,$params,$isPrecondition);
    }



}
