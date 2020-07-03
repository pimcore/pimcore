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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder as Order;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem as OrderItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\PaymentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Model\Element\Note;

interface OrderAgentInterface
{
    /**
     * @return Order
     */
    public function getOrder();

    /**
     * cancel order item and refund payment
     *
     * @param OrderItem $item
     *
     * @return Note
     */
    public function itemCancel(OrderItem $item);

    /**
     * start item complaint
     *
     * @param OrderItem $item
     * @param float $quantity
     *
     * @return Note
     */
    public function itemComplaint(OrderItem $item, $quantity);

    /**
     * change order item
     *
     * @param OrderItem $item
     * @param float $amount
     *
     * @return Note
     */
    public function itemChangeAmount(OrderItem $item, $amount);

    /**
     * set a item state
     *
     * @param OrderItem $item
     * @param string    $state
     *
     * @return Note
     */
    public function itemSetState(OrderItem $item, $state);

    /**
     * @return Currency
     */
    public function getCurrency();

    /**
     * @return bool
     */
    public function hasPayment();

    /**
     * @return PaymentInterface
     */
    public function getPaymentProvider();

    /**
     * @param PaymentInterface $paymentProvider
     * @param AbstractOrder|null $sourceOrder
     *
     * @return Order
     */
    public function setPaymentProvider(PaymentInterface $paymentProvider, AbstractOrder $sourceOrder = null);

    /**
     * Init payment:
     *
     * creates new payment info with INIT state
     *
     * throws exception when payment info exists
     *
     * @return AbstractPaymentInformation
     *
     * @throws UnsupportedException
     */
    public function initPayment();

    /**
     * Starts payment:
     *
     * checks if payment info with PENDING payment exists and checks if order fingerprint has not changed
     * if true -> returns existing payment info
     * if false -> creates new payment info (and aborts existing PENDING payment infos)
     *
     * @return AbstractPaymentInformation
     *
     * @throws UnsupportedException
     */
    public function startPayment();

    /**
     * Returns current payment info of order, or null if none exists
     *
     * @return null|AbstractPaymentInformation
     */
    public function getCurrentPendingPaymentInfo();

    /**
     * cancels payment for current payment info
     * - payment will be cancelled, order state will be resetted and cart will we writable again.
     *
     * -> this should be used, when user cancels payment
     *
     * only possible when payment state is PENDING, otherwise exception is thrown
     *
     * @return Order
     *
     * @throws UnsupportedException
     */
    public function cancelStartedOrderPayment();

    /**
     * @param StatusInterface $status
     *
     * @return OrderAgentInterface
     */
    public function updatePayment(StatusInterface $status);

    /**
     * @return Note[]
     */
    public function getFullChangeLog();
}

class_alias(OrderAgentInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderAgent');
