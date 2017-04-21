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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

interface IEnvironment
{
    const EXECUTION_MODE_PRODUCT = 'product';
    const EXECUTION_MODE_CART = 'cart';

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart
     *
     * @return IEnvironment
     */
    public function setCart(\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart);

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart
     */
    public function getCart();

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem $cartItem
     *
     * @return IEnvironment
     */
    public function setCartItem(\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem $cartItem);

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem
     */
    public function getCartItem();

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable $product
     *
     * @return IEnvironment
     */
    public function setProduct(\Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable $product);

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable
     */
    public function getProduct();

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IRule $rule
     *
     * @return IEnvironment
     */
    public function setRule($rule);

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IRule
     */
    public function getRule();

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPriceInfo $priceInfo
     *
     * @return IEnvironment
     */
    public function setPriceInfo(\Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPriceInfo $priceInfo);

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPriceInfo
     */
    public function getPriceInfo();

    /**
     * @param array $categories
     *
     * @return IEnvironment
     */
    public function setCategories(array $categories);

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory[]
     */
    public function getCategories();

    /**
     * @param AttributeBagInterface $namespace
     *
     * @return IEnvironment
     */
    public function setSession(AttributeBagInterface $namespace);

    /**
     * @return AttributeBagInterface
     */
    public function getSession();

    /**
     * sets execution mode of system - either product or cart
     *
     * @param $executionMode
     */
    public function setExecutionMode($executionMode);

    /**
     * returns in with execution mode the system is - either product or cart
     *
     * @return string
     */
    public function getExecutionMode();

    /**
     * returns hash of environment based on its content
     *
     * @return string
     */
    public function getHash();
}
