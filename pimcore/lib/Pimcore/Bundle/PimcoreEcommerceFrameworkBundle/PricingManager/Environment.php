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

class Environment implements IEnvironment
{
    /**
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart
     */
    protected $cart;

    /**
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICartItem
     */
    protected $cartItem;

    /**
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable
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
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory[]
     */
    protected $categories = array();

    /**
     * @var AttributeBagInterface
     */
    protected $session;


    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart
     *
     * @return IEnvironment
     */
    public function setCart(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart)
    {
        $this->cart = $cart;
        return $this;
    }

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICartItem
     */
    public function getCartItem()
    {
        return $this->cartItem;
    }

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICartItem $cartItem
     *
     * @return $this
     */
    public function setCartItem(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICartItem $cartItem)
    {
        $this->cartItem = $cartItem;
        return $this;
    }

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable $product
     *
     * @return IEnvironment
     */
    public function setProduct(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable $product = null)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable
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
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPriceInfo
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
     * @return array|\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractCategory
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


    /**
     * returns hash of environment based on its content
     *
     * @return string
     */
    public function getHash()
    {
        $hash = '';
        if($this->getCart()) {
            $hash .= json_encode($this->getCart());
        }

        if(count($this->getCategories()) > 0) {
            $hash .= json_encode($this->getCategories());
        }

        if($this->getPriceInfo()) {
            $hash .= spl_object_hash($this->getPriceInfo());
        }

        if($this->getProduct()) {
            $hash .= spl_object_hash($this->getProduct());
        }

        if($this->getRule()) {
            $hash .= spl_object_hash($this->getRule());
        }

        return sha1($hash);
    }
}