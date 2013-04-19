<?php

class OnlineShop_Framework_Impl_CartPriceModificator_Discount implements OnlineShop_Framework_CartPriceModificator_IDiscount
{
    /**
     * @var float
     */
    protected $amount = 0;

    /**
     * @var null|OnlineShop_Framework_Pricing_IRule
     */
    protected $rule = null;


    /**
     * @param OnlineShop_Framework_Pricing_IRule $rule
     */
    public function __construct(OnlineShop_Framework_Pricing_IRule $rule) {
        $this->rule = $rule;
    }


    /**
     * modificator name
     *
     * @return string
     */
    public function getName()
    {
        if($this->rule) {
            return $this->rule->getName();
        }
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
        if($this->getAmount() != 0) {
            return new OnlineShop_Framework_Impl_ModificatedPrice($this->getAmount(), $currentSubTotal->getCurrency(), false, $this->rule->getLabel());
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