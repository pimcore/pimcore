<?php

/**
 * interface for cart item implementations of online shop framework
 */
interface OnlineShop_Framework_ICartItem {

    /**
     * @abstract
     * @return OnlineShop_Framework_AbstractProduct
     */
    public function getProduct();

    /**
     * @abstract
     * @return int
     */
    public function getCount();

    /**
     * @abstract
     * @return string
     */
    public function getItemKey();

    /**
     * @abstract
     * @param OnlineShop_Framework_AbstractProduct $product
     * @return void
     */
    public function setProduct(OnlineShop_Framework_AbstractProduct $product);

    /**
     * @abstract
     * @param int $count
     * @return void
     */
    public function setCount($count);

    /**
     * @abstract
     * @param OnlineShop_Framework_ICart $cart
     * @return void
     */
    public function setCart(OnlineShop_Framework_ICart $cart);

    /**
     * @abstract
     * @return OnlineShop_Framework_ICart
     */
    public function getCart();

    /**
     * @abstract
     * @return OnlineShop_Framework_ICartItem[]
     */
    public function getSubItems();

    /**
     * @abstract
     * @param  OnlineShop_Framework_ICartItem[] $subItems
     * @return void
     */
    public function setSubItems($subItems);

    /**
     * @abstract
     * @return OnlineShop_Framework_IPrice
     */
    public function getPrice();

    /**
     * @abstract
     * @return OnlineShop_Framework_IPriceInfo
     */
    public function getPriceInfo();

    /**
     * @abstract
     * @return string
     */
    public function getComment();

    /**
     * @return OnlineShop_Framework_AbstractSetProductEntry[]
     */
    public function getSetEntries();

    /**
     * @abstract
     * @return OnlineShop_Framework_IAvailability
     */
    public function getAvailabilityInfo();


    /**
     * @static
     * @abstract
     * @param $cartId
     * @param $itemKey
     * @param string $parentKey
     * @return OnlineShop_Framework_ICartItem
     */
    public static function getByCartIdItemKey($cartId, $itemKey, $parentKey = "");

    /**
     * @static
     * @abstract
     * @param $cartId
     * @return void
     */
    public static function removeAllFromCart($cartId);

    /**
     * @abstract
     * @return void
     */
    public function save();
}
