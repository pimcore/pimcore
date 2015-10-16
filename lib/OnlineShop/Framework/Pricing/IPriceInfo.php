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


interface OnlineShop_Framework_Pricing_IPriceInfo extends OnlineShop_Framework_IPriceInfo
{
    /**
     * @param OnlineShop_Framework_IPriceInfo           $priceInfo
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     */
    public function __construct(OnlineShop_Framework_IPriceInfo $priceInfo, OnlineShop_Framework_Pricing_IEnvironment $environment);

    /**
     * @param OnlineShop_Framework_Pricing_IRule $rule
     *
     * @return OnlineShop_Framework_IPriceInfo
     */
    public function addRule(OnlineShop_Framework_Pricing_IRule $rule);

    /**
     * returns all valid rules, if forceRecalc, recalculation of valid rules is forced
     *
     * @param bool $forceRecalc
     * @return OnlineShop_Framework_Pricing_IRule[]
     */
    public function getRules($forceRecalc = false);

    /**
     * @param float $amount
     *
     * @return OnlineShop_Framework_Pricing_IPriceInfo
     */
    public function setAmount($amount);

    /**
     * @return mixed
     */
    public function getAmount();

    /**
     * @return OnlineShop_Framework_IPrice
     */
    public function getOriginalPrice();

    /**
     * @return OnlineShop_Framework_IPrice
     */
    public function getOriginalTotalPrice();

    /**
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function getEnvironment();

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return OnlineShop_Framework_IPriceInfo
     */
    public function setEnvironment(OnlineShop_Framework_Pricing_IEnvironment $environment);

    /**
     * @return bool
     */
    public function hasDiscount();

    /**
     * @return OnlineShop_Framework_IPrice
     */
    public function getDiscount();

    /**
     * @return OnlineShop_Framework_IPrice
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