<?php

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
     * @abstract
     * @param OnlineShop_Framework_AbstractProduct $product
     * @param int $count
     * @param null $itemKey
     * @param bool $replace replace if item with same key exists
     * @param array $params optional additional item informations
     * @param OnlineShop_Framework_AbstractSetProductEntry[] $subProducts
     * @return string $itemKey
     */
    public function addItem(OnlineShop_Framework_AbstractProduct $product, $count, $itemKey = null, $replace = false, $params = array(), $subProducts = array());


    /**
     * @abstract
     * @param string $itemKey
     * @param OnlineShop_Framework_AbstractProduct $product
     * @param int $count
     * @param array $params optional additional item informations
     * @param OnlineShop_Framework_AbstractSetProductEntry[] $subProducts
     * @return string $itemKey
     */
    public function updateItem($itemKey, OnlineShop_Framework_AbstractProduct $product, $count, $replace = false, $params = array(), $subProducts = array());

    /**
     * @abstract
     * @param string $itemKey
     * @return void
     */
    public function removeItem($itemKey);
    public function clear();

    /**
     * @abstract
     * @return OnlineShop_Framework_ICartPriceCalculator
     */
    public function getPriceCalculator();

    /**
     * @abstract
     * @param  $key string
     * @param  $data string
     * @return void
     */
    public function setCheckoutData($key, $data);

    /**
     * @abstract
     * @param  $key string
     * @return string
     */
    public function getCheckoutData($key);


    public function getName();
    public function setName($name);


    public function getIsBookable();

    /**
     * @abstract
     * @return Zend_Date
     */
    public function getCreationDate();
    public function setCreationDate(Zend_Date $creationDate = null);

    public function save();
    public function delete();

    public static function getById($id);
    public static function getAllCartsForUser($userId);
}
