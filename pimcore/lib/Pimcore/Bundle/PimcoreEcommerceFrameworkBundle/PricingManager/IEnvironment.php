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

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

interface IEnvironment
{
    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart
     *
     * @return IEnvironment
     */
    public function setCart(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart);

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart
     */
    public function getCart();

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICartItem $cartItem
     *
     * @return IEnvironment
     */
    public function setCartItem(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICartItem $cartItem);

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICartItem
     */
    public function getCartItem();

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable $product
     *
     * @return IEnvironment
     */
    public function setProduct(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable $product);

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable
     */
    public function getProduct();

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IRule $rule
     *
     * @return IEnvironment
     */
    public function setRule($rule);

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IRule
     */
    public function getRule();

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IPriceInfo $priceInfo
     *
     * @return IEnvironment
     */
    public function setPriceInfo(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IPriceInfo $priceInfo);

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IPriceInfo
     */
    public function getPriceInfo();

    /**
     * @param array $categories
     *
     * @return IEnvironment
     */
    public function setCategories(array $categories);

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory[]
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
     * returns hash of environment based on its content
     *
     * @return string
     */
    public function getHash();

}