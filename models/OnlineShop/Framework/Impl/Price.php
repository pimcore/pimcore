<?php
/**
 * Created by IntelliJ IDEA.
 * User: rtippler
 * Date: 08.06.11
 * Time: 11:34
 * To change this template use File | Settings | File Templates.
 */
 
class OnlineShop_Framework_Impl_Price implements OnlineShop_Framework_IPrice{
    private $currency;
    private $amount;
    private $minPrice;

    /** @return Zend_Currency*/
    function getCurrency() {
        return $this->currency;
    }

    /** @return double*/
    function getAmount() {
        return $this->amount;
    }


    function __construct($amount, Zend_Currency $currency, $minPrice = false) {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->minPrice = $minPrice;
    }
    function __toString(){
        return $this->getCurrency()->toCurrency($this->amount);
    }


    /**
     * @return bool
     */
    public function isMinPrice() {
        return $this->minPrice;
    }

    /**
     * @param $amount int
     * @return void
     */
    public function setAmount($amount) {
        $this->amount = $amount;
    }
}
