<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 07.04.15
 * Time: 16:45
 * To change this template use File | Settings | File Templates.
 */

namespace OnlineShop\Framework;

use OnlineShop\Framework\OrderManager\IOrderList;
use Pimcore\Model\Object\OnlineShopOrder;

interface IOrderManager
{
    /**
     * @return IOrderList
     */
    public function createOrderList();


    /**
     * @param OnlineShopOrder $order
     *
     * @return mixed
     */
    public function createOrderAgent(OnlineShopOrder $order);
}