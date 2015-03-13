<?php

class OnlineShop_Framework_Impl_CartCheckoutData_List extends \Pimcore\Model\Listing\AbstractListing {

    /**
     * @var array
     */
    public $cartCheckoutDataItems;

    /**
     * @var array
     */
    public function isValidOrderKey($key) {
        if($key == "key" || $key == "cartId") {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    function getCartCheckoutDataItems() {
        if(empty($this->cartCheckoutDataItems)) {
            $this->load();
        }
        return $this->cartCheckoutDataItems;
    }

    /**
     * @param array $cartCheckoutDataItems
     * @return void
     */
    function setCartCheckoutDataItems($cartCheckoutDataItems) {
        $this->cartCheckoutDataItems = $cartCheckoutDataItems;
    }

}
