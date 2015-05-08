<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 07.04.15
 * Time: 16:45
 * To change this template use File | Settings | File Templates.
 */

namespace OnlineShop\Framework;

use OnlineShop\Framework\OrderManager;
use OnlineShop_Framework_AbstractOrder as Order;

interface IOrderManager
{
    /**
     * @return OrderManager\IOrderList
     */
    public function createOrderList();


    /**
     * @param Order $order
     *
     * @return OrderManager\IOrderAgent
     */
    public function createOrderAgent(Order $order);
}