<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
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
    /**
     * @var CartInterface|null
     */
    protected $cart;

    /**
     * @var CartItemInterface|null
     */
    protected $cartItem;

    /**
     * @var CheckoutableInterface|null
     */
    protected $product;

    /**
     * @var VisitorInfo|null
     */
    protected $visitorInfo;

    /**
     * @var RuleInterface|null
     */
    protected $rule;

    /**
     * @var PriceInfoInterface|null
     */
    protected $priceInfo;

    /**
     * @var AbstractCategory[]
     */
    protected $categories = [];

    /**
     * @var AttributeBagInterface|null
     */
    protected $session;

    /**
     * Execution mode of system - either product or cart
     *
     * @var string
     */
    protected $executionMode = EnvironmentInterface::EXECUTION_MODE_PRODUCT;

    /**
     * @param CartInterface $cart
     *
     * @return EnvironmentInterface
     */
    public function setCart(CartInterface $cart)
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * @return CartInterface|null
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @return CartItemInterface|null
     */
    public function getCartItem()
    {
        return $this->cartItem;
    }

    /**
     * @param CartItemInterface $cartItem
     *
     * @return $this
     */
    public function setCartItem(CartItemInterface $cartItem)
    {
        $this->cartItem = $cartItem;

        return $this;
    }

    /**
     * @param CheckoutableInterface|null $product
     *
     * @return EnvironmentInterface
     */
    public function setProduct(CheckoutableInterface $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return CheckoutableInterface|null
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param VisitorInfo $visitorInfo
     *
     * @return EnvironmentInterface
     */
    public function setVisitorInfo(VisitorInfo $visitorInfo)
    {
        $this->visitorInfo = $visitorInfo;

        return $this;
    }

    /**
     * @return VisitorInfo|null
     */
    public function getVisitorInfo()
    {
        return $this->visitorInfo;
    }

    /**
     * @param RuleInterface $rule
     *
     * @return EnvironmentInterface
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @return RuleInterface|null
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param PriceInfoInterface $priceInfo
     *
     * @return EnvironmentInterface
     */
    public function setPriceInfo(PriceInfoInterface $priceInfo)
    {
        $this->priceInfo = $priceInfo;

        return $this;
    }

    /**
     * @return PriceInfoInterface|null
     */
    public function getPriceInfo()
    {
        return $this->priceInfo;
    }

    /**
     * @param array $categories
     *
     * @return EnvironmentInterface
     */
    public function setCategories(array $categories)
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @return AbstractCategory[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @return AttributeBagInterface|null
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param AttributeBagInterface $session
     *
     * @return EnvironmentInterface
     */
    public function setSession(AttributeBagInterface $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @param string $executionMode
     */
    public function setExecutionMode($executionMode)
    {
        $this->executionMode = $executionMode;
    }

    /**
     * @return string
     */
    public function getExecutionMode()
    {
        return $this->executionMode;
    }

    /**
     * returns hash of environment based on its content
     *
     * @return string
     */
    public function getHash()
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
