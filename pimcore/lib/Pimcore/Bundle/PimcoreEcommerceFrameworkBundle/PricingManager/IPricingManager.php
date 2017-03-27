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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager;

interface IPricingManager
{
    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPriceInfo $priceinfo
     *
     * @return IPriceInfo
     */
    public function applyProductRules(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPriceInfo $priceinfo);

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart
     *
     * @return IPricingManager
     */
    public function applyCartRules(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart);

    /**
     * Factory
     * @return IRule
     */
    public function getRule();

    /**
     * Factory
     * @param string $type
     *
     * @return ICondition
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function getCondition($type);

    /**
     * Factory
     * @param $type
     *
     * @return IAction
     */
    public function getAction($type);

    /**
     * @return IEnvironment
     */
    public function getEnvironment();

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPriceInfo $priceInfo
     *
     * @return IPriceInfo
     */
    public function getPriceInfo(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPriceInfo $priceInfo);
}
