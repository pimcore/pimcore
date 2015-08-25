<?php

class OnlineShop_Framework_Impl_SessionCartItem extends OnlineShop_Framework_AbstractCartItem implements OnlineShop_Framework_ICartItem {

    public function getCart() {
        if (empty($this->cart)) {
            $this->cart = OnlineShop_Framework_Impl_SessionCart::getById($this->cartId);
        }
        return $this->cart;
    }


    public function save() {
        throw new Exception("Not implemented, should not be needed for this cart type.");
    }

    public static function getByCartIdItemKey($cartId, $itemKey, $parentKey = "") {
        throw new Exception("Not implemented, should not be needed for this cart type.");
    }

    public static function removeAllFromCart($cartId) {
        $cartItem = new self();
        $cart = $cartItem->getCart();
        $cart->setItems(null);
        $cart->save();
    }

    /**
     * @return OnlineShop_Framework_ICartItem[]
     */
    public function getSubItems() {
        return (array)$this->subItems;
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
