<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 10.04.13
 * Time: 15:40
 * To change this template use File | Settings | File Templates.
 */

interface OnlineShop_Framework_CartPriceModificator_IDiscount extends OnlineShop_Framework_ICartPriceModificator
{
    /**
     * @param float $amount
     *
     * @return OnlineShop_Framework_ICartPriceModificator_IDiscount
     */
    public function setAmount($amount);

    /**
     * @return float
     */
    public function getAmount();
}