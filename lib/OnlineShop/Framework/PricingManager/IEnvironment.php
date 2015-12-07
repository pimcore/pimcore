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

namespace OnlineShop\Framework\PricingManager;

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
}