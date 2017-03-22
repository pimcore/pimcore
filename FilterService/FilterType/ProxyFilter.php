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


    function __construct($script,$config)
    {
        parent::__construct($script, $config);
        if (!$config->proxyclass){
            throw new \Exception("wrong configuration for " .  __CLASS__ . ": config setting proxyclass is missing!");
        }
        if (!$config->field){
            throw new \Exception("wrong configuration for " .  __CLASS__ . ": config setting field is missing!");
        }

        $this->proxy = new $config->proxyclass($script, $config);
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
