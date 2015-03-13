<?php

class OnlineShop_Framework_Impl_Cart_List extends \Pimcore\Model\Listing\AbstractListing {

    /**
     * @var array
     */
    public $carts;

    public function __construct() {
        $this->getResource()->setCartClass(OnlineShop_Framework_Factory::getInstance()->getCartManager()->getCartClassName());
    }

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
