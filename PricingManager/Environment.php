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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager;

class Environment implements IEnvironment
{
    /**
     * @var \OnlineShop\Framework\CartManager\ICart
     */
    protected $cart;

    /**
     * @var \OnlineShop\Framework\CartManager\ICartItem
     */
    protected $cartItem;

    /**
     * @var \OnlineShop\Framework\Model\ICheckoutable
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
     * @var \OnlineShop\Framework\Model\AbstractCategory[]
     */
    protected $categories = array();

    /**
     * @var \Zend_Session_Namespace
     */
    protected $session;


    /**
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     *
     * @return IEnvironment
     */
    public function setCart(\OnlineShop\Framework\CartManager\ICart $cart)
    {
        $this->cart = $cart;
        return $this;
    }

    /**
     * @return \OnlineShop\Framework\CartManager\ICart
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @return \OnlineShop\Framework\CartManager\ICartItem
     */
    public function getCartItem()
    {
        return $this->cartItem;
    }

    /**
     * @param \OnlineShop\Framework\CartManager\ICartItem $cartItem
     *
     * @return $this
     */
    public function setCartItem(\OnlineShop\Framework\CartManager\ICartItem $cartItem)
    {
        $this->cartItem = $cartItem;
        return $this;
    }

    /**
     * @param \OnlineShop\Framework\Model\ICheckoutable $product
     *
     * @return IEnvironment
     */
    public function setProduct(\OnlineShop\Framework\Model\ICheckoutable $product = null)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return \OnlineShop\Framework\Model\ICheckoutable
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
     * @return \OnlineShop\Framework\PriceSystem\IPriceInfo
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
     * @return array|\OnlineShop\Framework\Model\AbstractCategory
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @return \Zend_Session_Namespace
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param \Zend_Session_Namespace $session
     *
     * @return IEnvironment
     */
    public function setSession(\Zend_Session_Namespace $session)
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