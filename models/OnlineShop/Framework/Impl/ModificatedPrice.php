<?php

class OnlineShop_Framework_Impl_ModificatedPrice extends OnlineShop_Framework_Impl_Price implements OnlineShop_Framework_IModificatedPrice {

    protected $description;

    public function __construct($amount, Zend_Currency $currency, $minPrice = false, $description = null) {
        parent::__construct($amount, $currency, $minPrice);
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

}
