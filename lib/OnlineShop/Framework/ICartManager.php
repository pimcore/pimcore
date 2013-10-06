<?php

interface OnlineShop_Framework_ICartManager extends OnlineShop_Framework_IComponent {

    /**
     * returns cart class name configered in the xml
     *
     * @return string
     */
    public function getCartClassName();
    
    /**
     * @abstract
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     * @param $count
     * @param $key
     * @param null $itemKey
     * @param bool $replace  replace item if same key already exists
     * @param array $params  optional addtional item informations
     * @param OnlineShop_Framework_AbstractSetProductEntry[] $subProducts
     * @param null $comment
     */
    public function addToCart(OnlineShop_Framework_ProductInterfaces_ICheckoutable $product, $count,  $key ,$itemKey = null, $replace = false, $params = array(), $subProducts = array(), $comment = null);

    /**
     * @abstract
     * @param string $itemKey
     * @param null $key optional identification of cart in case of multicart
     * @return void
     */
    public function removeFromCart($itemKey, $key = null);

    /**
     * @abstract
     * @param null $key optional identification of cart in case of multicart
     * @return OnlineShop_Framework_ICart
     */
    public function getCart($key = null);

    /**
     * @param $name
     * @return mixed
     */
    public function getCartByName($name);

    /**
     * @abstract
     * @return OnlineShop_Framework_ICart[]
     */
    public function getCarts();

    /**
     * @abstract
     * @param null $key optional identification of cart in case of multicart
     * @return void
     */
    public function clearCart($key = null);

    /**
     * @abstract
     * @param  $param array of cart informations
     * @return $key
     */
    public function createCart($param);

    /**
     * @abstract
     * @param  $param array of cart informations
     * @param null $key optional identification of cart in case of multicart
     * @return void
     */
    public function updateCartInformation($param, $key = null);

    /**
     * @abstract
     * @param  $key
     * @return void
     */
    public function deleteCart($key);


    /**
     * @abstract
     * @return OnlineShop_Framework_ICartPriceCalculator
     */
    public function getCartPriceCalcuator(OnlineShop_Framework_ICart $cart);
}
