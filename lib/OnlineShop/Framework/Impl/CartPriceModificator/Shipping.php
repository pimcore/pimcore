<?php

class OnlineShop_Framework_Impl_CartPriceModificator_Shipping implements OnlineShop_Framework_CartPriceModificator_IShipping
{
    /**
     * @var float
     */
    protected $charge = 5.99; # sample


    public function __construct($config) {
        if($config->charge) {
            $this->charge = floatval($config->charge);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "shipping";
    }

    /**
     * @param OnlineShop_Framework_IPrice $currentSubTotal
     * @param OnlineShop_Framework_ICart  $cart
     *
     * @return OnlineShop_Framework_IPrice
     */
    public function modify(OnlineShop_Framework_IPrice $currentSubTotal, OnlineShop_Framework_ICart $cart)
    {
        return new OnlineShop_Framework_Impl_ModificatedPrice($this->getCharge(), new Zend_Currency(Zend_Registry::get("Zend_Locale")));
    }

    /**
     * @param float $charge
     *
     * @return return OnlineShop_Framework_ICartPriceModificator
     */
    public function setCharge($charge)
    {
        $this->charge = $charge;

        return $this;
    }

    /**
     * @return float
     */
    public function getCharge()
    {
        return $this->charge;
    }
}