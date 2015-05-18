<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 03.10.2014
 * Time: 16:45
 */

namespace OnlineShop\Framework\Impl;

use OnlineShop\Framework\IOrderManager;
use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderAgent;
use OnlineShop_Framework_AbstractOrder as Order;
use Zend_Config;

class OrderManager implements IOrderManager
{
    /**
     * @var Zend_Config
     */
    protected $config;


    /**
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return IOrderList
     */
    public function createOrderList()
    {
        $orderList = new $this->config->orderList->class();
        /* @var IOrderList $orderList */
        $orderList->setItemClassName( $this->config->orderList->classItem );

        return $orderList;
    }

    /**
     * @param Order $order
     *
     * @return IOrderAgent
     */
    public function createOrderAgent(Order $order)
    {
        return new $this->config->orderAgent->class( \OnlineShop_Framework_Factory::getInstance(), $order );
    }
}