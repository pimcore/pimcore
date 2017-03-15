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

interface IEnvironment
{
    /**
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     *
     * @return IEnvironment
     */
    public function setCart(\OnlineShop\Framework\CartManager\ICart $cart);

    /**
     * @return \OnlineShop\Framework\CartManager\ICart
     */
    public function getCart();

    /**
     * @param \OnlineShop\Framework\CartManager\ICartItem $cartItem
     *
     * @return IEnvironment
     */
    public function setCartItem(\OnlineShop\Framework\CartManager\ICartItem $cartItem);

    /**
     * @return \OnlineShop\Framework\CartManager\ICartItem
     */
    public function getCartItem();

    /**
     * @param \OnlineShop\Framework\Model\ICheckoutable $product
     *
     * @return IEnvironment
     */
    public function setProduct(\OnlineShop\Framework\Model\ICheckoutable $product);

    /**
     * @return \OnlineShop\Framework\Model\ICheckoutable
     */
    public function getProduct();

    /**
     * @param \OnlineShop\Framework\PricingManager\IRule $rule
     *
     * @return IEnvironment
     */
    public function setRule($rule);

    /**
     * @return \OnlineShop\Framework\PricingManager\IRule
     */
    public function getRule();

    /**
     * @param \OnlineShop\Framework\PricingManager\IPriceInfo $priceInfo
     *
     * @return IEnvironment
     */
    public function setPriceInfo(\OnlineShop\Framework\PricingManager\IPriceInfo $priceInfo);

    /**
     * @return \OnlineShop\Framework\PricingManager\IPriceInfo
     */
    public function getPriceInfo();

    /**
     * @param array $categories
     *
     * @return IEnvironment
     */
    public function setCategories(array $categories);

    /**
     * @return \OnlineShop\Framework\Model\AbstractCategory[]
     */
    public function getCategories();

    /**
     * @param \Zend_Session_Namespace $namespace
     *
     * @return IEnvironment
     */
    public function setSession(\Zend_Session_Namespace $namespace);

    /**
     * @return \Zend_Session_Namespace
     */
    public function getSession();


    /**
     * returns hash of environment based on its content
     *
     * @return string
     */
    public function getHash();

}