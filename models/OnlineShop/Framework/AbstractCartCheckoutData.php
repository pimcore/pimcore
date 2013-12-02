<?php

abstract class OnlineShop_Framework_AbstractCartCheckoutData extends Pimcore_Model_Abstract {

    protected $key;
    protected $data;
    /**
     * @var OnlineShop_Framework_ICart
     */
    protected $cart;

    public function setCart(OnlineShop_Framework_ICart $cart) {
        $this->cart = $cart;
    }

    public function getCart() {
        return $this->cart;
    }

    public function getCartId() {
        return $this->getCart()->getId();
    }

    public abstract function save();

    public abstract static function getByKeyCartId($key, $cartId);

    public abstract static function removeAllFromCart($cartId);

    public function setKey($key) {
        $this->key = $key;
    }

    public function getKey() {
        return $this->key;
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }


}
