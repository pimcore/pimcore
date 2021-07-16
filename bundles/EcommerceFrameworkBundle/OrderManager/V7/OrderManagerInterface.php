<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\V7;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderAgentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Model\DataObject\Folder;

interface OrderManagerInterface
{
    /**
     * @return OrderListInterface
     */
    public function createOrderList();

    /**
     * @param AbstractOrder $order
     *
     * @return OrderAgentInterface
     */
    public function createOrderAgent(AbstractOrder $order);

    /**
     * @param int|Folder $orderParentFolder
     */
    public function setParentOrderFolder($orderParentFolder);

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
     * @return AbstractOrder
     */
    public function getOrCreateOrderFromCart(CartInterface $cart);

    /**
     * @param CartInterface $cart
     *
     * @return AbstractOrder
     */
    public function recreateOrder(CartInterface $cart): AbstractOrder;

    /**
     * @param AbstractOrder $sourceOrder
     *
     * @return AbstractOrder
     */
    public function recreateOrderBasedOnSourceOrder(AbstractOrder $sourceOrder): AbstractOrder;

    /**
     * Looks if order object for given cart exists and returns it - it does not create it!
     *
     * @param CartInterface $cart
     *
     * @return AbstractOrder|null
     */
    public function getOrderFromCart(CartInterface $cart);

    /**
     * Returns order based on given payment status
     *
     * @param StatusInterface $paymentStatus
     *
     * @return AbstractOrder|null
     */
    public function getOrderByPaymentStatus(StatusInterface $paymentStatus);

    /**
     * Builds order listing
     *
     * @return \Pimcore\Model\DataObject\Listing\Concrete
     *
     * @throws \Exception
     */
    public function buildOrderList();

    /**
     * Build order item listing
     *
     * @return \Pimcore\Model\DataObject\Listing\Concrete
     *
     * @throws \Exception
     */
    public function buildOrderItemList();

    /**
     * @param CartInterface $cart
     *
     * @return bool
     */
    public function cartHasPendingPayments(CartInterface $cart): bool;

    /**
     * @param CartInterface $cart
     * @param AbstractOrder $order
     *
     * @return bool
     *
     * @throws UnsupportedException
     */
    public function orderNeedsUpdate(CartInterface $cart, AbstractOrder $order): bool;
}
