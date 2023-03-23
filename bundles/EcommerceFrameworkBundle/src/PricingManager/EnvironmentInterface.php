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
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;

interface EnvironmentInterface
{
    public const EXECUTION_MODE_PRODUCT = 'product';

    public const EXECUTION_MODE_CART = 'cart';

    /**
     * @return $this
     */
    public function setCart(CartInterface $cart): static;

    public function getCart(): ?CartInterface;

    /**
     * @return $this
     */
    public function setCartItem(CartItemInterface $cartItem): static;

    public function getCartItem(): ?CartItemInterface;

    /**
     * @return $this
     */
    public function setProduct(CheckoutableInterface $product): static;

    public function getProduct(): ?CheckoutableInterface;

    /**
     * @return $this
     */
    public function setVisitorInfo(VisitorInfo $visitorInfo): static;

    public function getVisitorInfo(): ?VisitorInfo;

    /**
     * @return $this
     */
    public function setRule(RuleInterface $rule): static;

    public function getRule(): ?RuleInterface;

    /**
     * @return $this
     */
    public function setPriceInfo(PriceInfoInterface $priceInfo): static;

    public function getPriceInfo(): ?PriceInfoInterface;

    /**
     * @return $this
     */
    public function setCategories(array $categories): static;

    /**
     * @return AbstractCategory[]
     */
    public function getCategories(): array;

    /**
     * sets execution mode of system - either product or cart
     *
     * @return $this
     */
    public function setExecutionMode(string $executionMode): static;

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
