<?php

/**
 * Class OnlineShop_Framework_Impl_AttributePriceInfo
 *
 * attribute info for attribute price system
 */
class OnlineShop_Framework_Impl_AttributePriceInfo extends OnlineShop_Framework_AbstractPriceInfo implements OnlineShop_Framework_IPriceInfo {

    /**
     * @var OnlineShop_Framework_IPrice
     */
    protected $price;

    /**
     * @var OnlineShop_Framework_IPrice
     */
    protected $totalPrice;


    public function __construct(OnlineShop_Framework_IPrice $price, $quantity, OnlineShop_Framework_IPrice $totalPrice) {
        $this->price = $price;
        $this->totalPrice = $totalPrice;
        $this->quantity = $quantity;
    }

    public function getPrice() {
        return $this->price;
    }

    public function getTotalPrice() {
        return $this->totalPrice;
    }

    /**
     * try to delegate all other functions to the product
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {
        return $this->product->$name($arguments);
    }

}
