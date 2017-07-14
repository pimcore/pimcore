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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

interface IEnvironment
{
    const EXECUTION_MODE_PRODUCT = 'product';
    const EXECUTION_MODE_CART = 'cart';

    /**
     * @param ICart $cart
     *
     * @return IEnvironment
     */
    public function setCart(ICart $cart);

    /**
     * @return ICart
     */
    public function getCart();

    /**
     * @param ICartItem $cartItem
     *
     * @return IEnvironment
     */
    public function setCartItem(ICartItem $cartItem);

    /**
     * @return ICartItem
     */
    public function getCartItem();

    /**
     * @param ICheckoutable $product
     *
     * @return IEnvironment
     */
    public function setProduct(ICheckoutable $product);

    /**
     * @return ICheckoutable
     */
    public function getProduct();

    /**
     * @param IRule $rule
     *
     * @return IEnvironment
     */
    public function setRule($rule);

    /**
     * @return IRule
     */
    public function getRule();

    /**
     * @param IPriceInfo $priceInfo
     *
     * @return IEnvironment
     */
    public function setPriceInfo(IPriceInfo $priceInfo);

    /**
     * @return IPriceInfo
     */
    public function getPriceInfo();

    /**
     * @param array $categories
     *
     * @return IEnvironment
     */
    public function setCategories(array $categories);

    /**
     * @return AbstractCategory[]
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
