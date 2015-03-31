<?php

class OnlineShop_Framework_Impl_CartPriceModificator_Discount implements OnlineShop_Framework_CartPriceModificator_IDiscount
{
    /**
     * @var float
     */
    protected $amount = 0;


    /**
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config = null)
    {
        if($config && $config->charge)
        {
            $this->charge = floatval($config->charge);
        }
    }


    /**
     * modificator name
     *
     * @return string
     */
    public function getName()
    {
        return "discount";
    }

    /**
     * modify price
     *
     * @param OnlineShop_Framework_IPrice $currentSubTotal
     * @param OnlineShop_Framework_ICart  $cart
     *
     * @return OnlineShop_Framework_IPrice|null
     */
    public function modify(OnlineShop_Framework_IPrice $currentSubTotal, OnlineShop_Framework_ICart $cart)
    {
        if($this->getAmount() != 0)
        {
            return new OnlineShop_Framework_Impl_ModificatedPrice($this->getAmount(), $currentSubTotal->getCurrency());
        }
    }

    /**
     * @param float $amount
     *
     * @return OnlineShop_Framework_CartPriceModificator_IDiscount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }


}