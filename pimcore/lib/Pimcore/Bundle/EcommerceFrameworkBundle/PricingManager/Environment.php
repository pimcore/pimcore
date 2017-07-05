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

class Environment implements IEnvironment
{
    /**
     * @var ICart
     */
    protected $cart;

    /**
     * @var ICartItem
     */
    protected $cartItem;

    /**
     * @var ICheckoutable
     */
    protected $product;

    /**
     * @var IRule
     */
    protected $rule;

    /**
     * @var IPriceInfo
     */
    protected $priceInfo;

    /**
     * @var AbstractCategory[]
     */
    protected $categories = [];

    /**
     * @var AttributeBagInterface
     */
    protected $session;

    /**
     * Execution mode of system - either product or cart
     *
     * @var string
     */
    protected $executionMode = IEnvironment::EXECUTION_MODE_PRODUCT;

    /**
     * @param ICart $cart
     *
     * @return IEnvironment
     */
    public function setCart(ICart $cart)
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * @return ICart
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @return ICartItem
     */
    public function getCartItem()
    {
        return $this->cartItem;
    }

    /**
     * @param ICartItem $cartItem
     *
     * @return $this
     */
    public function setCartItem(ICartItem $cartItem)
    {
        $this->cartItem = $cartItem;

        return $this;
    }

    /**
     * @param ICheckoutable $product
     *
     * @return IEnvironment
     */
    public function setProduct(ICheckoutable $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return ICheckoutable
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param IRule $rule
     *
     * @return IEnvironment
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @return IRule
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param IPriceInfo $priceInfo
     *
     * @return IEnvironment
     */
    public function setPriceInfo(IPriceInfo $priceInfo)
    {
        $this->priceInfo = $priceInfo;

        return $this;
    }

    /**
     * @return IPriceInfo
     */
    public function getPriceInfo()
    {
        return $this->priceInfo;
    }

    /**
     * @param array $categories
     *
     * @return IEnvironment
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
     * @return AttributeBagInterface
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param AttributeBagInterface $session
     *
     * @return IEnvironment
     */
    public function setSession(AttributeBagInterface $session)
    {
        $this->session = $session;

        return $this;
    }

    public function setExecutionMode($executionMode)
    {
        $this->executionMode = $executionMode;
    }

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
