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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager;

interface IPricingManager
{
    /**
     * @param \OnlineShop\Framework\PriceSystem\IPriceInfo $priceinfo
     *
     * @return IPriceInfo
     */
    public function applyProductRules(\OnlineShop\Framework\PriceSystem\IPriceInfo $priceinfo);

    /**
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     *
     * @return IPricingManager
     */
    public function applyCartRules(\OnlineShop\Framework\CartManager\ICart $cart);

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
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
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
     * @param \OnlineShop\Framework\PriceSystem\IPriceInfo $priceInfo
     *
     * @return IPriceInfo
     */
    public function getPriceInfo(\OnlineShop\Framework\PriceSystem\IPriceInfo $priceInfo);
}