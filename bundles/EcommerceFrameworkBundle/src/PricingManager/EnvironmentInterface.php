<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItemInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractCategory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Targeting\Model\VisitorInfo;

interface EnvironmentInterface
{
    public const EXECUTION_MODE_PRODUCT = 'product';

    public const EXECUTION_MODE_CART = 'cart';

    public function setCart(CartInterface $cart): EnvironmentInterface;

    public function getCart(): ?CartInterface;

    public function setCartItem(CartItemInterface $cartItem): EnvironmentInterface;

    public function getCartItem(): ?CartItemInterface;

    public function setProduct(CheckoutableInterface $product): EnvironmentInterface;

    public function getProduct(): ?CheckoutableInterface;

    public function setVisitorInfo(VisitorInfo $visitorInfo): EnvironmentInterface;

    public function getVisitorInfo(): ?VisitorInfo;

    public function setRule(RuleInterface $rule): EnvironmentInterface;

    public function getRule(): ?RuleInterface;

    public function setPriceInfo(PriceInfoInterface $priceInfo): EnvironmentInterface;

    public function getPriceInfo(): ?PriceInfoInterface;

    public function setCategories(array $categories): EnvironmentInterface;

    /**
     * @return AbstractCategory[]
     */
    public function getCategories(): array;

    /**
     * sets execution mode of system - either product or cart
     *
     * @param string $executionMode
     */
    public function setExecutionMode(string $executionMode);

    /**
     * returns in with execution mode the system is - either product or cart
     *
     * @return string
     */
    public function getExecutionMode(): string;

    /**
     * returns hash of environment based on its content
     *
     * @return string
     */
    public function getHash(): string;
}
