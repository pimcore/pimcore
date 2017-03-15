<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager;

use \OnlineShop\Framework\Model\AbstractOrder as Order;

interface IOrderManager
{
    /**
     * @return IOrderList
     */
    public function createOrderList();


    /**
     * @param Order $order
     *
     * @return IOrderAgent
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
     * @return \OnlineShop\Framework\Model\AbstractOrder
     */
    public function getOrCreateOrderFromCart(\OnlineShop\Framework\CartManager\ICart $cart);

    /**
     * Looks if order object for given cart exists and returns it - it does not create it!
     *
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return \OnlineShop\Framework\Model\AbstractOrder
     */
    public function getOrderFromCart(\OnlineShop\Framework\CartManager\ICart $cart);

    /**
     * gets order based on given payment status
     *
     * @param \OnlineShop\Framework\PaymentManager\IStatus $paymentStatus
     * @return \OnlineShop\Framework\Model\AbstractOrder
     */
    public function getOrderByPaymentStatus(\OnlineShop\Framework\PaymentManager\IStatus $paymentStatus);

    /**
     * Build order listing
     *
     * @return \Pimcore\Model\Object\Listing\Concrete
     * @throws \Exception
     */
    public function buildOrderList();

    /**
     * Build order item listing
     *
     * @return \Pimcore\Model\Object\Listing\Concrete
     * @throws \Exception
     */
    public function buildOrderItemList();
}
