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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IStatus;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\IPayment;

interface ICommitOrderProcessor
{
    /**
     * Checks if order is already committed and payment information with same internal payment id has same state
     *
     * @param array|IStatus $paymentResponseParams
     * @param IPayment $paymentProvider
     *
     * @return null|AbstractOrder
     *
     * @throws \Exception
     * @throws UnsupportedException
     */
    public function committedOrderWithSamePaymentExists($paymentResponseParams, IPayment $paymentProvider);

    /**
     * Facade method for
     *
     *  - handling payment response and
     *  - commit order payment
     *
     * Can be used by controllers to commit orders with payment
     *
     * @param $paymentResponseParams
     * @param IPayment $paymentProvider
     *
     * @return AbstractOrder
     */
    public function handlePaymentResponseAndCommitOrderPayment($paymentResponseParams, IPayment $paymentProvider);

    /**
     * Commits order payment
     *
     *  - updates order payment information in order object
     *  - only when payment status == [ORDER_STATE_COMMITTED, ORDER_STATE_PAYMENT_AUTHORIZED] -> order is committed
     *
     * Use this for committing order when payment is activated
     *
     * @param IStatus $paymentStatus
     * @param IPayment $paymentProvider
     *
     * @return AbstractOrder
     */
    public function commitOrderPayment(IStatus $paymentStatus, IPayment $paymentProvider);

    /**
     * Commits order
     *
     * @param AbstractOrder $order
     *
     * @return AbstractOrder
     */
    public function commitOrder(AbstractOrder $order);

    /**
     * @param string $confirmationMail
     */
    public function setConfirmationMail($confirmationMail);

    /**
     * Cleans up orders with state pending payment after 1h
     *
     * @return void
     */
    public function cleanUpPendingOrders();
}
