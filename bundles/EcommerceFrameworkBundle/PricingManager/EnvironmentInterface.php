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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

interface EnvironmentInterface
{
    const EXECUTION_MODE_PRODUCT = 'product';
    const EXECUTION_MODE_CART = 'cart';

    /**
     * @param CartInterface $cart
     *
     * @return EnvironmentInterface
     */
    public function setCart(CartInterface $cart);

    /**
     * @return CartInterface
     */
    public function getCart();

    /**
     * @param CartItemInterface $cartItem
     *
     * @return EnvironmentInterface
     */
    public function setCartItem(CartItemInterface $cartItem);

    /**
     * @return CartItemInterface
     */
    public function getCartItem();

    /**
     * @param CheckoutableInterface $product
     *
     * @return EnvironmentInterface
     */
    public function setProduct(CheckoutableInterface $product);

    /**
     * @return CheckoutableInterface
     */
    public function getProduct();

    /**
     * @param VisitorInfo $visitorInfo
     *
     * @return EnvironmentInterface
     */
    public function setVisitorInfo(VisitorInfo $visitorInfo);

    /**
     * @return VisitorInfo
     */
    public function getVisitorInfo();

    /**
     * @param RuleInterface $rule
     *
     * @return EnvironmentInterface
     */
    public function setRule($rule);

    /**
     * @return RuleInterface
     */
    public function getRule();

    /**
     * @param PriceInfoInterface $priceInfo
     *
     * @return EnvironmentInterface
     */
    public function setPriceInfo(PriceInfoInterface $priceInfo);

    /**
     * @return PriceInfoInterface
     */
    public function getPriceInfo();

    /**
     * @param array $categories
     *
     * @return EnvironmentInterface
     */
    public function setCategories(array $categories);

    /**
     * @return AbstractCategory[]
     */
    public function getCategories();

    /**
     * @param AttributeBagInterface $namespace
     *
     * @return EnvironmentInterface
     */
    public function setSession(AttributeBagInterface $namespace);

    /**
     * @return AttributeBagInterface
     */
    public function getSession();

    /**
     * sets execution mode of system - either product or cart
     *
     * @param string $executionMode
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

class_alias(EnvironmentInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment');
