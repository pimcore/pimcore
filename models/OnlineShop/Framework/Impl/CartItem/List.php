<?php

class OnlineShop_Framework_Impl_CartItem_List extends Pimcore_Model_List_Abstract {

    /**
     * @var array
     */
    public $cartItems;

    /**
     * @var array
     */
    protected $order = array('ASC');

    /**
     * @var array
     */
    protected $orderKey = array('`addedDateTimestamp`');

    /**
     * @var array
     * @return boolean
     */
    public function isValidOrderKey($key) {
        if($key == "productId" || $key == "cartId" || $key == "count" || $key == "itemKey" || $key == "addedDateTimestamp") {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    function getCartItems() {
        if(empty($this->cartItems)) {
            $this->load();
        }
        return $this->cartItems;
    }

    /**
     * @param array $cartItems
     * @return void
     */
    function setCartItems($cartItems) {
        $this->cartItems = $cartItems;
    }

}
