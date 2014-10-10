<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 03.10.14
 * Time: 15:48
 * To change this template use File | Settings | File Templates.
 */

interface OnlineShop_Framework_IPaymentManager
{
    /**
     * @param $name
     *
     * @return OnlineShop_Framework_IPayment
     */
    public function getProvider($name);
}