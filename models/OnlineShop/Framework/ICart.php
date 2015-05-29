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
     * @param $id int
     * @return void
     */
    public function setId($id);

    /**
     * @abstract
     * @return OnlineShop_Framework_ICartItem[]
     */
    public function getItems();

    /**
     * @return bool
     */
    public function isEmpty();

    /**
     * @param string $itemKey
     *
     * @return OnlineShop_Framework_ICartItem
     */
    public function getItem($itemKey);

    /**
     * @return OnlineShop_Framework_ICartItem[]
     */
    public function getGiftItems();

    /**
     * @param string $itemKey
     *
     * @return OnlineShop_Framework_ICartItem
     */
    public function getGiftItem($itemKey);

    /**
     * @abstract
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     * @param int $count
     * @param null $itemKey
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param OnlineShop_Framework_AbstractSetProductEntry[] $subProducts
     * @param string $comment
     * @return string $itemKey
     */
    public function addItem(OnlineShop_Framework_ProductInterfaces_ICheckoutable $product, $count, $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null);


    /**
     * @abstract
     * @param string $itemKey
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     * @param int $count
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param array $subProducts
     * @param null $comment
     * @return string $itemKey
     */
    public function updateItem($itemKey, OnlineShop_Framework_ProductInterfaces_ICheckoutable $product, $count, $replace = false, $params = array(), $subProducts = array(), $comment = null);

    /**
     * updates count of specific cart item
     *
     * @param $itemKey
     * @param $count
     * @return mixed
     */
    public function updateItemCount($itemKey, $count);

    /**
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     * @param int $count
     * @param null $itemKey
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param OnlineShop_Framework_AbstractSetProductEntry[] $subProducts
     * @param string $comment
     * @return string $itemKey
     */
    public function addGiftItem(OnlineShop_Framework_ProductInterfaces_ICheckoutable $product, $count, $itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null);


    /**
     * @param string $itemKey
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     * @param int $count
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item information
     * @param array $subProducts
     * @param null $comment
     * @return string $itemKey
     */
    public function updateGiftItem($itemKey, OnlineShop_Framework_ProductInterfaces_ICheckoutable $product, $count, $replace = false, $params = array(), $subProducts = array(), $comment = null);

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
     * @param int $count
     *
     * @return OnlineShop_Framework_ProductInterfaces_ICheckoutable[]
     */
    public function getRecentlyAddedItems($count);


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
     * @abstract
     * @return Zend_Date
     */
    public function getModificationDate();

    /**
     * @abstract
     * @param null|Zend_Date $modificationDate
     * @return void
     */
    public function setModificationDate(Zend_Date $modificationDate = null);

    /**
     * sorts all items in cart according to a given callback function
     *
     * @param callable $value_compare_func
     * @return OnlineShop_Framework_ICart
     */
    public function sortItems(callable $value_compare_func);

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

    /**
     * @param OnlineShop_Framework_VoucherService_Token $token
     * @throws Exception
     * @return bool
     */
    public function addVoucherToken($token);

    /**
     * @param OnlineShop_Framework_VoucherService_Token $token
     * @return bool
     */
    public function removeVoucherToken($token);

    /**
     * @return array
     */
    public function getVoucherTokenCodes();

    /**
     * @return bool
     */
    public function isVoucherErrorCode($errorCode);
}
