<?php

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

    public function setCart(OnlineShop_Framework_ICart $cart);
    /**
     * @abstract
     * @return OnlineShop_Framework_ICart
     */
    public function getCart();

    /**
     * @abstract
     * @return array(OnlineShop_Framework_ICartItem)
     */
    public function getSubItems();

    /**
     * @abstract
     * @param  $subItems array(OnlineShop_Framework_ICartItem)
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
     * @return stdClass
     */
    public function getPriceInfo();

    /**
     * @abstract
     * @return OnlineShop_Framework_IAvailability
     */
    public function getAvailabilityInfo();


    public static function getByCartIdItemKey($cartId, $itemKey, $parentKey = "");
    public static function removeAllFromCart($cartId);

    public function save();
}
