<?php

class OnlineShop_Framework_Impl_CartPriceModificator_Discount implements OnlineShop_Framework_CartPriceModificator_IDiscount
{
    /**
     * @var float
     */
    protected $amount = 0;

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
     * @return OnlineShop_Framework_IPrice
     */
    public function modify(OnlineShop_Framework_IPrice $currentSubTotal, OnlineShop_Framework_ICart $cart)
    {
        if($this->getAmount() != 0)
            return new OnlineShop_Framework_Impl_Price($this->getAmount(), $currentSubTotal->getCurrency());
    }

    /**
     * @param float $amount
     *
     * @return OnlineShop_Framework_ICartPriceModificator_IDiscount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }


}