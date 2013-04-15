<?php

/**
 * interface for cart implementations of online shop framework
 */
interface OnlineShop_Framework_ICart {

    /**
     * @abstract
     * @return int
     */
    public function getId();

    /**
     * @abstract
     * @return OnlineShop_Framework_ICartItem[]
     */
    public function getItems();

    /**
     * @return OnlineShop_Framework_ICartItem[]
     */
    public function getGiftItems();

    /**
     * @abstract
     * @param OnlineShop_Framework_AbstractProduct $product
     * @param int $count
     * @param null $itemKey
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param OnlineShop_Framework_AbstractSetProductEntry[] $subProducts
     * @param string $comment
     * @return string $itemKey
     */
    public function addItem(OnlineShop_Framework_AbstractProduct $product, $count, $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null);


    /**
     * @abstract
     * @param string $itemKey
     * @param OnlineShop_Framework_AbstractProduct $product
     * @param int $count
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param array $subProducts
     * @param null $comment
     * @return string $itemKey
     */
    public function updateItem($itemKey, OnlineShop_Framework_AbstractProduct $product, $count, $replace = false, $params = array(), $subProducts = array(), $comment = null);

    /**
     * @param OnlineShop_Framework_AbstractProduct $product
     * @param int $count
     * @param null $itemKey
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param OnlineShop_Framework_AbstractSetProductEntry[] $subProducts
     * @param string $comment
     * @return string $itemKey
     */
    public function addGiftItem(OnlineShop_Framework_AbstractProduct $product, $count, $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null);


    /**
     * @param string $itemKey
     * @param OnlineShop_Framework_AbstractProduct $product
     * @param int $count
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param array $subProducts
     * @param null $comment
     * @return string $itemKey
     */
    public function updateGiftItem($itemKey, OnlineShop_Framework_AbstractProduct $product, $count, $replace = false, $params = array(), $subProducts = array(), $comment = null);

    /**
     * @abstract
     * @param string $itemKey
     * @return void
     */
    public function removeItem($itemKey);

    /**
     * clears all items of cart
     *
     * @abstract
     * @return void
     */
    public function clear();

    /**
     * calculates amount of items in cart
     *
     * @abstract
     * @param bool $countSubItems
     *
     * @return int
     */
    public function getItemAmount($countSubItems = false);

    /**
     * returns price calculator of cart
     *
     * @abstract
     * @return OnlineShop_Framework_ICartPriceCalculator
     */
    public function getPriceCalculator();

    /**
     * Set custom checkout data for cart.
     * can be used for delivery information, ...
     *
     * @abstract
     * @param  $key string
     * @param  $data string
     * @return void
     */
    public function setCheckoutData($key, $data);

    /**
     * Get custom checkout data for cart with given key.
     *
     * @abstract
     * @param  $key string
     * @return string
     */
    public function getCheckoutData($key);

    /**
     * get name of cart.
     *
     * @abstract
     * @return string
     */
    public function getName();

    /**
     * set name of cart.
     *
     * @abstract
     * @param $name
     * @return void
     */
    public function setName($name);

    /**
     * returns if cart is bookable.
     * default implementation checks if all products of cart a bookable.
     *
     * @abstract
     * @return bool
     */
    public function getIsBookable();

    /**
     * @abstract
     * @return Zend_Date
     */
    public function getCreationDate();

    /**
     * @abstract
     * @param null|Zend_Date $creationDate
     * @return void
     */
    public function setCreationDate(Zend_Date $creationDate = null);

    /**
     * saves cart
     *
     * @abstract
     * @return void
     */
    public function save();

    /**
     * deletes cart
     *
     * @abstract
     * @return void
     */
    public function delete();

    /**
     * @static
     * @abstract
     * @param $id
     * @return OnlineShop_Framework_ICart
     */
    public static function getById($id);

    /**
     * returns all carts for given userId
     *
     * @static
     * @abstract
     * @param $userId
     * @return OnlineShop_Framework_ICart[]
     */
    public static function getAllCartsForUser($userId);
}
