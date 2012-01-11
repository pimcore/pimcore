<?php

interface OnlineShop_Framework_IPrice {

    /** @return double*/
    public function getAmount();

    /** @return Zend_Currency*/
    public function getCurrency();

    /**
     * @abstract
     * @return bool
     */
    public function isMinPrice();

    /**
     * @abstract
     * @param $amount int
     * @return void
     */
    public function setAmount($amount);

}
 
