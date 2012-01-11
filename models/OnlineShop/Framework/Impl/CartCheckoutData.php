<?php

class OnlineShop_Framework_Impl_CartCheckoutData extends Pimcore_Model_Abstract {

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
        $cacheKey = OnlineShop_Framework_Impl_CartCheckoutData_Resource::TABLE_NAME . "_" . $key . "_" . $cartId;

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


}
