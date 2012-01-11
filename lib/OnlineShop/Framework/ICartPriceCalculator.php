<?php

interface OnlineShop_Framework_ICartPriceCalculator {

    public function __construct($config, OnlineShop_Framework_ICart $cart);

    /**
     * @abstract
     * @return void
     */
    public function calculate();

    /**
     * @abstract
     * @return void
     */
    public function reset();


    /**
     * @abstract
     * @return OnlineShop_Framework_IPrice $price
     */
    public function getSubTotal();

    /**
     * @abstract
     * @return OnlineShop_Framework_IPrice[] $priceModification
     */
    public function getPriceModifications();

    /**
     * @abstract
     * @return OnlineShop_Framework_IPrice $price
     */
    public function getGrandTotal();
}
