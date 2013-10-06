<?php

class OnlineShop_Framework_Impl_SessionCartCheckoutData extends OnlineShop_Framework_AbstractCartCheckoutData {

    protected $cartId;

    public function save() {
        throw new Exception("Not implemented, should not be needed for this cart type.");
    }

    public static function getByKeyCartId($key, $cartId) {
        throw new Exception("Not implemented, should not be needed for this cart type.");
    }

    public static function removeAllFromCart($cartId) {
        $checkoutDataItem = new self();
        $checkoutDataItem->getCart()->checkoutData = array();
    }


    public function setCart(OnlineShop_Framework_ICart $cart) {
        $this->cart = $cart;
        $this->cartId = $cart->getId();
    }

    public function getCart() {
        if (empty($this->cart)) {
            $this->cart = OnlineShop_Framework_Impl_SessionCart::getById($this->cartId);
        }
        return $this->cart;
    }

    public function getCartId() {
        return $this->cartId;
    }

    public function setCartId($cartId) {
        $this->cartId = $cartId;
    }


    /**
     * @return array
     */
    public function __sleep() {
        $vars = parent::__sleep();

        $blockedVars = array("cart","product");

        $finalVars = array();
        foreach ($vars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

}
