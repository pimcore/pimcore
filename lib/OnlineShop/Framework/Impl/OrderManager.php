<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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