<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 07.04.2015
 * Time: 16:47
 */

namespace OnlineShop\Framework\OrderManager;

interface IOrderListFilter
{
    /**
     * @param IOrderList $orderList
     *
     * @return IOrderListFilter
     */
    public function apply(IOrderList $orderList);
}