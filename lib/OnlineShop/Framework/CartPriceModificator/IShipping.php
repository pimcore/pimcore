<?php

/**
 * Interface OnlineShop_Framework_CartPriceModificator_IShipping
 *
 * special interface for shipping price modifications - needed for pricing rule that remove shipping costs
 */
interface OnlineShop_Framework_CartPriceModificator_IShipping extends OnlineShop_Framework_ICartPriceModificator
{
    /**
     * @param float $charge
     *
     * @return OnlineShop_Framework_ICartPriceModificator
     */
    public function setCharge($charge);

    /**
     * @return float
     */
    public function getCharge();
}