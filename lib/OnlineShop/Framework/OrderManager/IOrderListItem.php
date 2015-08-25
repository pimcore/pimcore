<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 07.04.2015
 * Time: 16:47
 */

namespace OnlineShop\Framework\OrderManager;

use OnlineShop_Framework_AbstractOrder as Order;
use OnlineShop_Framework_AbstractOrderItem as OrderItem;

interface IOrderListItem
{
    /**
     * @return int
     */
    public function getId();


    /**
     * @return Order|OrderItem
     */
    public function reference();
}