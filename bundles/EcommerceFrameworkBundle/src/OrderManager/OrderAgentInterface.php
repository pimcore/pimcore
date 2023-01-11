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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder as Order;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem as OrderItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\PaymentInterface;
use Pimcore\Model\Element\Note;

interface OrderAgentInterface
{
    public function getOrder(): Order;

    /**
     * cancel order item and refund payment
     *
     * @param OrderItem $item
     *
     * @return Note
     */
    public function itemCancel(OrderItem $item): Note;

    /**
     * start item complaint
     *
     * @param OrderItem $item
     * @param float $quantity
     *
     * @return Note
     */
    public function itemComplaint(OrderItem $item, float $quantity): Note;

    /**
     * change order item
     *
     * @param OrderItem $item
     * @param float $amount
     *
     * @return Note
     */
    public function itemChangeAmount(OrderItem $item, float $amount): Note;

    /**
     * set a item state
     *
     * @param OrderItem $item
     * @param string $state
     *
     * @return Note
     */
    public function itemSetState(OrderItem $item, string $state): Note;

    public function getCurrency(): Currency;

    public function hasPayment(): bool;

    public function getPaymentProvider(): PaymentInterface;

    /**
     * @param PaymentInterface $paymentProvider
     * @param AbstractOrder|null $sourceOrder
     *
     * @return OrderAgentInterface
     */
    public function setPaymentProvider(PaymentInterface $paymentProvider, AbstractOrder $sourceOrder = null): OrderAgentInterface;

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
    public function initPayment(): AbstractPaymentInformation;

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
    public function startPayment(): AbstractPaymentInformation;

    /**
     * Returns current payment info of order, or null if none exists
     *
     * @return null|AbstractPaymentInformation
     */
    public function getCurrentPendingPaymentInfo(): ?AbstractPaymentInformation;

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
    public function cancelStartedOrderPayment(): Order;

    public function updatePayment(StatusInterface $status): OrderAgentInterface;

    /**
     * @return Note[]
     */
    public function getFullChangeLog(): array;
}
