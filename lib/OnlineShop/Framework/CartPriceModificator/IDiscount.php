<?php

/**
 * Interface OnlineShop_Framework_CartPriceModificator_IDiscount
 *
 * special interface for price modifications added by discount pricing rules for carts
 */
interface OnlineShop_Framework_CartPriceModificator_IDiscount extends OnlineShop_Framework_ICartPriceModificator
{
    /**
     * @param float $amount
     *
     * @return OnlineShop_Framework_CartPriceModificator_IDiscount
     */
    public function setAmount($amount);

    /**
     * @return float
     */
    public function getAmount();
}