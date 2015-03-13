<?php

/**
 * Class OnlineShop_Framework_Impl_CartPriceModificator_Shipping
 */
class OnlineShop_Framework_Impl_CartPriceModificator_Shipping implements OnlineShop_Framework_CartPriceModificator_IShipping
{
    /**
     * @var float
     */
    protected $charge = 0;

    /**
     * @param $config
     */
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
     * @return OnlineShop_Framework_IModificatedPrice
     */
    public function modify(OnlineShop_Framework_IPrice $currentSubTotal, OnlineShop_Framework_ICart $cart)
    {
        return new OnlineShop_Framework_Impl_ModificatedPrice($this->getCharge(), new Zend_Currency(OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrencyLocale()));
    }

    /**
     * @param float $charge
     *
     * @return OnlineShop_Framework_ICartPriceModificator
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