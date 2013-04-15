<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 13:22
 * To change this template use File | Settings | File Templates.
 */

interface OnlineShop_Framework_Pricing_Action_IDiscount extends OnlineShop_Framework_Pricing_IAction
{
    /**
     * @param float $amount
     *
     * @return void
     */
    public function setAmount($amount);

    /**
     * @param float $percent
     *
     * @return void
     */
    public function setPercent($percent);

    /**
     * @return float
     */
    public function getAmount();

    /**
     * @return float
     */
    public function getPercent();
}