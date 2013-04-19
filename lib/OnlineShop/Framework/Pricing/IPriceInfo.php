<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 11.04.13
 * Time: 14:19
 * To change this template use File | Settings | File Templates.
 */

interface OnlineShop_Framework_Pricing_IPriceInfo extends OnlineShop_Framework_IPriceInfo
{
    /**
     * @param OnlineShop_Framework_IPriceInfo $priceInfo
     */
    public function __construct(OnlineShop_Framework_IPriceInfo $priceInfo);

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
     * @return array|OnlineShop_Framework_Pricing_IRule
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
}