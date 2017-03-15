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

interface IPriceInfo extends \OnlineShop\Framework\PriceSystem\IPriceInfo
{
    /**
     * @param \OnlineShop\Framework\PriceSystem\IPriceInfo           $priceInfo
     * @param IEnvironment $environment
     */
    public function __construct(\OnlineShop\Framework\PriceSystem\IPriceInfo $priceInfo, IEnvironment $environment);

    /**
     * @param \OnlineShop\Framework\PricingManager\IRule $rule
     *
     * @return \OnlineShop\Framework\PriceSystem\IPriceInfo
     */
    public function addRule(\OnlineShop\Framework\PricingManager\IRule $rule);

    /**
     * returns all valid rules, if forceRecalc, recalculation of valid rules is forced
     *
     * @param bool $forceRecalc
     * @return \OnlineShop\Framework\PricingManager\IRule[]
     */
    public function getRules($forceRecalc = false);

    /**
     * @param float $amount
     *
     * @return IPriceInfo
     */
    public function setAmount($amount);

    /**
     * @return mixed
     */
    public function getAmount();

    /**
     * @return \OnlineShop\Framework\PriceSystem\IPrice
     */
    public function getOriginalPrice();

    /**
     * @return \OnlineShop\Framework\PriceSystem\IPrice
     */
    public function getOriginalTotalPrice();

    /**
     * @return IEnvironment
     */
    public function getEnvironment();

    /**
     * @param IEnvironment $environment
     *
     * @return \OnlineShop\Framework\PriceSystem\IPriceInfo
     */
    public function setEnvironment(IEnvironment $environment);

    /**
     * @return bool
     */
    public function hasDiscount();

    /**
     * @return \OnlineShop\Framework\PriceSystem\IPrice
     */
    public function getDiscount();

    /**
     * @return \OnlineShop\Framework\PriceSystem\IPrice
     */
    public function getTotalDiscount();

    /**
     * get discount in percent
     * @return float
     */
    public function getDiscountPercent();

    /**
     * @return bool
     */
    public function hasRulesApplied();
}