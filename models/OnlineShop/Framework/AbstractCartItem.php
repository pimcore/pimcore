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


abstract class OnlineShop_Framework_AbstractCartItem extends \Pimcore\Model\AbstractModel implements OnlineShop_Framework_ICartItem {

    /**
     * @var OnlineShop_Framework_ProductInterfaces_ICheckoutable
     */
    protected $product;
    protected $productId;
    protected $itemKey;
    protected $count;
    protected $comment;
    protected $parentItemKey = "";

    protected $subItems = null;

    /**
     * @var OnlineShop_Framework_ICart
     */
    protected $cart;
    protected $cartId;


    /**
     * @var int unix timestamp
     */
    protected $addedDateTimestamp;


    public function __construct()
    {
        $this->setAddedDate(Zend_Date::now());
    }

    public function setCount($count) {
        $this->count = $count;
    }

    public function getCount() {
        return $this->count;
    }

    public function setProduct(OnlineShop_Framework_ProductInterfaces_ICheckoutable $product) {
        $this->product = $product;
        $this->productId = $product->getId();
    }

    /**
     * @return OnlineShop_Framework_ProductInterfaces_ICheckoutable
     */
    public function getProduct() {
        if ($this->product) {
            return $this->product;
        }
        $this->product = \Pimcore\Model\Object\AbstractObject::getById($this->productId);
        return $this->product;
    }

    public function setCart(OnlineShop_Framework_ICart $cart) {
        $this->cart = $cart;
        $this->cartId = $cart->getId();
    }

    public abstract function getCart();

    public function getCartId() {
        return $this->cartId;
    }

    public function setCartId($cartId) {
        $this->cartId = $cartId;
    }

    public function getProductId() {
        if ($this->productId) {
            return $this->productId;
        }
        return $this->getProduct()->getId();
    }

    public function setProductId($productId) {
        $this->productId = $productId;
    }

    public function setParentItemKey($parentItemKey) {
        $this->parentItemKey = $parentItemKey;
    }

    public function getParentItemKey() {
        return $this->parentItemKey;
    }

    public function setItemKey($itemKey) {
        $this->itemKey = $itemKey;
    }

    public function getItemKey() {
        return $this->itemKey;
    }


    public abstract function save();

    public abstract static function getByCartIdItemKey($cartId, $itemKey, $parentKey = "");

    public abstract static function removeAllFromCart($cartId);

    /**
     * @return OnlineShop_Framework_ICartItem[]
     */
    public abstract function getSubItems();

    /**
     * @param  OnlineShop_Framework_ICartItem[] $subItems
     * @return void
     */
    public function setSubItems($subItems) {
        foreach ($subItems as $item) {
            $item->setParentItemKey($this->getItemKey());
        }
        $this->subItems = $subItems;
    }


    /**
     * @return OnlineShop_Framework_IPrice
     */
    public function getPrice()
    {
        return $this->getPriceInfo()->getPrice();
    }



    /**
     * @return OnlineShop_Framework_IPriceInfo
     */
    public function getPriceInfo() {

        if ($this->getProduct() instanceof OnlineShop_Framework_AbstractSetProduct) {
            $priceInfo = $this->getProduct()->getOSPriceInfo($this->getCount(),$this->getSetEntries());
        } else {
            $priceInfo = $this->getProduct()->getOSPriceInfo($this->getCount());
        }

        if($priceInfo instanceof OnlineShop_Framework_Pricing_IPriceInfo)
        {
            $priceInfo->getEnvironment()->setCart( $this->getCart() );
            $priceInfo->getEnvironment()->setCartItem( $this );
        }

        return $priceInfo;
    }

    /**
     * @return OnlineShop_Framework_IAvailability
     */
    public function getAvailabilityInfo() {
        if ($this->getProduct() instanceof OnlineShop_Framework_AbstractSetProduct) {
            return $this->getProduct()->getOSAvailabilityInfo($this->getCount(),$this->getSetEntries());

        } else {
            return $this->getProduct()->getOSAvailabilityInfo($this->getCount());
        }
    }

    /**
     * @return OnlineShop_Framework_AbstractSetProductEntry[]
     */
    public function getSetEntries() {
        $products = array();
        if($this->getSubItems()) {
            foreach ($this->getSubItems() as $item) {
                $products[] = new OnlineShop_Framework_AbstractSetProductEntry($item->getProduct(), $item->getCount());
            }
        }
        return $products;

    }

    /**
     * @param string $comment
     */
    public function setComment($comment) {
        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getComment() {
        return $this->comment;
    }


    /**
     * @return OnlineShop_Framework_IPrice
     */
    public function getTotalPrice() {
        return $this->getPriceInfo()->getTotalPrice();
    }


    public function setAddedDate(Zend_Date $date = null) {
        if($date) {
            $this->addedDateTimestamp = $date->getTimestamp();
        } else {
            $this->addedDateTimestamp = null;
        }
    }

    public function getAddedDate() {
        return $this->addedDateTimestamp !== NULL ? new Zend_Date($this->addedDateTimestamp) : null;
    }

    public function getAddedDateTimestamp()
    {
        return $this->addedDateTimestamp;
    }

    public function setAddedDateTimestamp($time)
    {
        $this->addedDateTimestamp = $time;
    }

    /**
     * get item name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getProduct()->getOSName();
    }
}
