<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop\Framework\PricingManager;

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
     * @var \OnlineShop_Framework_ProductInterfaces_ICheckoutable
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
     * @var \OnlineShop_Framework_AbstractCategory[]
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
     * @param \OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     *
     * @return IEnvironment
     */
    public function setProduct(\OnlineShop_Framework_ProductInterfaces_ICheckoutable $product = null)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return \OnlineShop_Framework_ProductInterfaces_ICheckoutable
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
     * @return array|\OnlineShop_Framework_AbstractCategory
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
     * test für zukünftiges caching...
     * @return string
     */
    public function getHash()
    {
        $hash = '';
        if($this->getCart())
            $hash .= spl_object_hash($this->getCart());

        if(count($this->getCategories())>0)
            $hash .= spl_object_hash(new \ArrayObject($this->getCategories()));
        if($this->getPriceInfo())
            $hash .= spl_object_hash($this->getPriceInfo());

        if($this->getProduct())
            $hash .= spl_object_hash($this->getProduct());

        if($this->getRule())
            $hash .= spl_object_hash($this->getRule());

        return sha1($hash);
    }
}