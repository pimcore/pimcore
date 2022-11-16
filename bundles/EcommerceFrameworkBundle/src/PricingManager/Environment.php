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
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class Environment implements EnvironmentInterface
{
    protected ?CartInterface $cart = null;

    protected ?CartItemInterface $cartItem = null;

    protected ?CheckoutableInterface $product = null;

    protected ?VisitorInfo $visitorInfo = null;

    protected ?RuleInterface $rule = null;

    protected ?PriceInfoInterface $priceInfo = null;

    /**
     * @var AbstractCategory[]
     */
    protected array $categories = [];

    protected ?AttributeBagInterface $session = null;

    /**
     * Execution mode of system - either product or cart
     *
     * @var string
     */
    protected string $executionMode = EnvironmentInterface::EXECUTION_MODE_PRODUCT;

    public function setCart(CartInterface $cart): EnvironmentInterface
    {
        $this->cart = $cart;

        return $this;
    }

    public function getCart(): ?CartInterface
    {
        return $this->cart;
    }

    public function getCartItem(): ?CartItemInterface
    {
        return $this->cartItem;
    }

    public function setCartItem(CartItemInterface $cartItem): static
    {
        $this->cartItem = $cartItem;

        return $this;
    }

    /**
     * @param CheckoutableInterface|null $product
     *
     * @return EnvironmentInterface
     */
    public function setProduct(CheckoutableInterface $product = null): EnvironmentInterface
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct(): ?CheckoutableInterface
    {
        return $this->product;
    }

    public function setVisitorInfo(VisitorInfo $visitorInfo): EnvironmentInterface
    {
        $this->visitorInfo = $visitorInfo;

        return $this;
    }

    public function getVisitorInfo(): ?VisitorInfo
    {
        return $this->visitorInfo;
    }

    public function setRule(RuleInterface $rule): EnvironmentInterface
    {
        $this->rule = $rule;

        return $this;
    }

    public function getRule(): ?RuleInterface
    {
        return $this->rule;
    }

    public function setPriceInfo(PriceInfoInterface $priceInfo): EnvironmentInterface
    {
        $this->priceInfo = $priceInfo;

        return $this;
    }

    public function getPriceInfo(): ?PriceInfoInterface
    {
        return $this->priceInfo;
    }

    public function setCategories(array $categories): EnvironmentInterface
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @return AbstractCategory[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    public function setExecutionMode(string $executionMode)
    {
        $this->executionMode = $executionMode;
    }

    public function getExecutionMode(): string
    {
        return $this->executionMode;
    }

    /**
     * returns hash of environment based on its content
     *
     * @return string
     */
    public function getHash(): string
    {
        $hash = '';
        if ($this->getCart()) {
            $hash .= json_encode($this->getCart());
        }

        if (count($this->getCategories()) > 0) {
            $hash .= json_encode($this->getCategories());
        }

        if ($this->getPriceInfo()) {
            $hash .= spl_object_hash($this->getPriceInfo());
        }

        if ($this->getProduct()) {
            $hash .= spl_object_hash($this->getProduct());
        }

        if ($this->getRule()) {
            $hash .= spl_object_hash($this->getRule());
        }

        $hash .= $this->getExecutionMode();

        return sha1($hash);
    }
}
