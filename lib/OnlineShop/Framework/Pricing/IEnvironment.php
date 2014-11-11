<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 13:11
 * To change this template use File | Settings | File Templates.
 */

interface OnlineShop_Framework_Pricing_IEnvironment
{
    /**
     * @param OnlineShop_Framework_ICart $cart
     *
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function setCart(OnlineShop_Framework_ICart $cart);

    /**
     * @return OnlineShop_Framework_ICart
     */
    public function getCart();

    /**
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     *
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function setProduct(OnlineShop_Framework_ProductInterfaces_ICheckoutable $product);

    /**
     * @return OnlineShop_Framework_ProductInterfaces_ICheckoutable
     */
    public function getProduct();

    /**
     * @param OnlineShop_Framework_Pricing_IRule $rule
     *
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function setRule($rule);

    /**
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function getRule();

    /**
     * @param OnlineShop_Framework_Pricing_IPriceInfo $priceInfo
     *
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function setPriceInfo(OnlineShop_Framework_Pricing_IPriceInfo $priceInfo);

    /**
     * @return OnlineShop_Framework_Pricing_IPriceInfo
     */
    public function getPriceInfo();

    /**
     * @param array $categories
     *
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function setCategories(array $categories);

    /**
     * @return OnlineShop_Framework_AbstractCategory[]
     */
    public function getCategories();

    /**
     * @param Zend_Session_Namespace $namespace
     *
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function setSession(Zend_Session_Namespace $namespace);

    /**
     * @return Zend_Session_Namespace
     */
    public function getSession();
}