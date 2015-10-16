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


class OnlineShop_Framework_Impl_Pricing_Environment implements OnlineShop_Framework_Pricing_IEnvironment
{
    /**
     * @var OnlineShop_Framework_ICart
     */
    protected $cart;

    /**
     * @var OnlineShop_Framework_ICartItem
     */
    protected $cartItem;

    /**
     * @var OnlineShop_Framework_ProductInterfaces_ICheckoutable
     */
    protected $product;

    /**
     * @var OnlineShop_Framework_Pricing_IRule
     */
    protected $rule;

    /**
     * @var OnlineShop_Framework_Pricing_IPriceInfo
     */
    protected $priceInfo;

    /**
     * @var OnlineShop_Framework_AbstractCategory[]
     */
    protected $categories = array();

    /**
     * @var Zend_Session_Namespace
     */
    protected $session;


    /**
     * @param OnlineShop_Framework_ICart $cart
     *
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function setCart(OnlineShop_Framework_ICart $cart)
    {
        $this->cart = $cart;
        return $this;
    }

    /**
     * @return OnlineShop_Framework_ICart
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @return OnlineShop_Framework_ICartItem
     */
    public function getCartItem()
    {
        return $this->cartItem;
    }

    /**
     * @param OnlineShop_Framework_ICartItem $cartItem
     *
     * @return $this
     */
    public function setCartItem(OnlineShop_Framework_ICartItem $cartItem)
    {
        $this->cartItem = $cartItem;
        return $this;
    }

    /**
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     *
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function setProduct(OnlineShop_Framework_ProductInterfaces_ICheckoutable $product = null)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return OnlineShop_Framework_ProductInterfaces_ICheckoutable
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param OnlineShop_Framework_Pricing_IRule $rule
     *
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function setRule($rule)
    {
        $this->rule = $rule;
        return $this;
    }

    /**
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param OnlineShop_Framework_Pricing_IPriceInfo $priceInfo
     *
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function setPriceInfo(OnlineShop_Framework_Pricing_IPriceInfo $priceInfo)
    {
        $this->priceInfo = $priceInfo;
        return $this;
    }

    /**
     * @return OnlineShop_Framework_IPriceInfo
     */
    public function getPriceInfo()
    {
        return $this->priceInfo;
    }

    /**
     * @param array $categories
     *
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function setCategories(array $categories)
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * @return array|OnlineShop_Framework_AbstractCategory
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @return Zend_Session_Namespace
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param Zend_Session_Namespace $session
     *
     * @return OnlineShop_Framework_Pricing_IEnvironment
     */
    public function setSession(Zend_Session_Namespace $session)
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
            $hash .= spl_object_hash(new ArrayObject($this->getCategories()));
        if($this->getPriceInfo())
            $hash .= spl_object_hash($this->getPriceInfo());

        if($this->getProduct())
            $hash .= spl_object_hash($this->getProduct());

        if($this->getRule())
            $hash .= spl_object_hash($this->getRule());

        return sha1($hash);
    }
}