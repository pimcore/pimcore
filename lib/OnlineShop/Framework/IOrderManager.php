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


    /**
     * @param int $id
     */
    public function setParentOrderFolder($id);

    /**
     * @param string $classname
     */
    public function setOrderClass($classname);

    /**
     * @param string $classname
     */
    public function setOrderItemClass($classname);

    /**
     * Looks if order object for given cart already exists, otherwise creates it
     *
     * move to ordermanagers
     *
     * @return \OnlineShop_Framework_AbstractOrder
     */
    public function getOrCreateOrderFromCart(\OnlineShop\Framework\CartManager\ICart $cart);

    /**
     * Looks if order object for given cart exists and returns it - it does not create it!
     *
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return \OnlineShop_Framework_AbstractOrder
     */
    public function getOrderFromCart(\OnlineShop\Framework\CartManager\ICart $cart);

    /**
     * gets order based on given payment status
     *
     * @param \OnlineShop_Framework_Payment_IStatus $paymentStatus
     * @return \OnlineShop_Framework_AbstractOrder
     */
    public function getOrderByPaymentStatus(\OnlineShop_Framework_Payment_IStatus $paymentStatus);
}