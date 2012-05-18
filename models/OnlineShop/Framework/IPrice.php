<?php

/**
 * Interface for price implementations of online shop framework
 */
interface OnlineShop_Framework_IPrice {

    /**
     * @abstract
     * @return float
     */
    public function getAmount();

    /**
     * @abstract
     * @return Zend_Currency
     */
    public function getCurrency();

    /**
     * @abstract
     * @return bool
     */
    public function isMinPrice();

    /**
     * @abstract
     * @param float $amount
     * @return void
     */
    public function setAmount($amount);

}
 
