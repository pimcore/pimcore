<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 10.04.13
 * Time: 15:35
 * To change this template use File | Settings | File Templates.
 */

interface OnlineShop_Framework_CartPriceModificator_IShipping extends OnlineShop_Framework_ICartPriceModificator
{
    /**
     * @param float $charge
     *
     * @return return OnlineShop_Framework_ICartPriceModificator
     */
    public function setCharge($charge);

    /**
     * @return float
     */
    public function getCharge();
}