<?php
declare(strict_types=1);

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
    public function createOrderList(): OrderListInterface;

    public function createOrderAgent(AbstractOrder $order): OrderAgentInterface;

    public function setParentOrderFolder(int|Folder $orderParentFolder): void;

    public function setOrderClass(string $classname): void;

    public function setOrderItemClass(string $classname): void;

    /**
     * Looks if order object for given cart already exists, otherwise creates it
     *
     */
    public function getOrCreateOrderFromCart(CartInterface $cart): AbstractOrder;

    public function recreateOrder(CartInterface $cart): AbstractOrder;

    public function recreateOrderBasedOnSourceOrder(AbstractOrder $sourceOrder): AbstractOrder;

    /**
     * Looks if order object for given cart exists and returns it - it does not create it!
     *
     * @param CartInterface $cart
     *
     * @return AbstractOrder|null
     */
    public function getOrderFromCart(CartInterface $cart): ?AbstractOrder;

    /**
     * Returns order based on given payment status
     *
     * @param StatusInterface $paymentStatus
     *
     * @return AbstractOrder|null
     */
    public function getOrderByPaymentStatus(StatusInterface $paymentStatus): ?AbstractOrder;

    /**
     * Builds order listing
     *
     * @return \Pimcore\Model\DataObject\Listing\Concrete
     *
     * @throws \Exception
     */
    public function buildOrderList(): \Pimcore\Model\DataObject\Listing\Concrete;

    /**
     * Build order item listing
     *
     * @return \Pimcore\Model\DataObject\Listing\Concrete
     *
     * @throws \Exception
     */
    public function buildOrderItemList(): \Pimcore\Model\DataObject\Listing\Concrete;

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
