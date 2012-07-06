<?php

class OnlineShop_Framework_Impl_SessionCart_List extends Pimcore_Model_List_Abstract {

    /**
     * @var array
     */
    public $carts;

    /**
     * @var array
     */
    public function isValidOrderKey($key) {
        if($key == "userId" || $key == "name") {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    function getCarts() {
        if(empty($this->carts)) {
            $this->load();
        }
        return $this->carts;
    }

    /**
     * @param array $carts
     * @return void
     */
    function setCarts($carts) {
        $this->carts = $carts;
    }

}
