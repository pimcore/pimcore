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
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

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
    protected $field;

    /**
     * ProxyFilter constructor.
     * @param string $script
     * @param \Pimcore\Config\Config $config
     * @param TranslatorInterface $translator
     * @param EngineInterface $engine
     * @throws \Exception
     */
    public function __construct($script, $config, TranslatorInterface $translator, EngineInterface $engine)
    {
        parent::__construct($script, $config, $translator, $engine);
        if (!$config->proxyclass) {
            throw new \Exception("wrong configuration for " .  __CLASS__ . ": config setting proxyclass is missing!");
        }
        if (!$config->field) {
            throw new \Exception("wrong configuration for " .  __CLASS__ . ": config setting field is missing!");
        }

        $this->proxy = new $config->proxyclass($script, $config, $translator, $engine);
        $this->field= $config->field;
    }

    public function getFilterFrontend(
        AbstractFilterDefinitionType $filterDefinition,
        IProductList $productList, $currentFilter
    ) {
        $filterDefinition->field=$this->field;

        return $this->proxy->getFilterFrontend($filterDefinition, $productList, $currentFilter);
    }

    public function addCondition(
        AbstractFilterDefinitionType $filterDefinition,
        IProductList $productList, $currentFilter, $params,
        $isPrecondition = false
    ) {
        $filterDefinition->field=$this->field;

        return $this->proxy->addCondition($filterDefinition, $productList, $currentFilter, $params, $isPrecondition);
    }
}
