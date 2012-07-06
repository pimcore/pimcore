<?php

class OnlineShop_Framework_Impl_SessionCartCheckoutData extends Pimcore_Model_Abstract {

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

    public function save() {
        $this->getResource()->save();
    }

    public static function getByKeyCartId($key, $cartId) {
        $cacheKey = "SessionCheckOutData_" . $key . "_" . $cartId;

        try {
            $checkoutDataItem = Zend_Registry::get($cacheKey);
        }
        catch (Exception $e) {
            try {
                $checkoutDataItem = new self();
                $checkoutDataItem->getResource()->getByKeyCartId($key, $cartId);
                Zend_Registry::set($cacheKey, $checkoutDataItem);
            } catch(Exception $ex) {
                Logger::debug($ex->getMessage());
                return null;
            }

        }
        return $checkoutDataItem;
    }

    public static function removeAllFromCart($cartId) {
        $checkoutDataItem = new self();
        $checkoutDataItem->getResource()->removeAllFromCart($cartId);
    }

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

    public function setValues($data) {
        if ($data instanceof stdClass && count($data) > 0) {
            foreach ($data as $key => $value) {
                $this->setValue($key,$value);
            }
        }
    }

    public function setValue($key, $value) {
        $method = "set" . ucfirst($key);
        if (method_exists($this, $method)) {
            $this->$method($value);
        }
    }


}
