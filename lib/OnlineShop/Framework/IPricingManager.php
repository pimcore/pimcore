<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 12:57
 * To change this template use File | Settings | File Templates.
 */

interface OnlineShop_Framework_IPricingManager
{
    /**
     * @param OnlineShop_Framework_IPriceInfo $priceinfo
     *
     * @return OnlineShop_Framework_Pricing_IPriceInfo
     */
    public function applyProductRules(OnlineShop_Framework_IPriceInfo $priceinfo);

    /**
     * @param OnlineShop_Framework_ICart $cart
     *
     * @return OnlineShop_Framework_IPricingManager
     */
    public function applyCartRules(OnlineShop_Framework_ICart $cart);

    /**
     * Factory
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function getRule();

    /**
     * Factory
     * @param string $type
     *
     * @return OnlineShop_Framework_Pricing_ICondition
     * @throws OnlineShop_Framework_Exception_InvalidConfigException
     */
    public function getCondition($type);

    /**
     * Factory
     * @param $type
     *
     * @return OnlineShop_Framework_Pricing_IAction
     */
    public function getAction($type);

    /**
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function getEnvironment();

    /**
     * @param OnlineShop_Framework_IPriceInfo $priceInfo
     *
     * @return OnlineShop_Framework_Pricing_IPriceInfo
     */
    public function getPriceInfo(OnlineShop_Framework_IPriceInfo $priceInfo);
}