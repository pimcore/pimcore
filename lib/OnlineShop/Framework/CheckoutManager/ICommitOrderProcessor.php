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

namespace OnlineShop\Framework\CheckoutManager;

/**
 * Interface \OnlineShop\Framework\CheckoutManager\ICommitOrderProcessor
 */
interface ICommitOrderProcessor {


    /**
     * check if order is already committed and payment information with same internal payment id has same state
     *
     * @param array|\OnlineShop\Framework\PaymentManager\IStatus $paymentResponseParams
     * @param \OnlineShop\Framework\PaymentManager\Payment\IPayment $paymentProvider
     * @return null|\OnlineShop\Framework\Model\AbstractOrder
     * @throws \Exception
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function committedOrderWithSamePaymentExists($paymentResponseParams, \OnlineShop\Framework\PaymentManager\Payment\IPayment $paymentProvider);

    /**
     * facade method for
     * - handling payment response and
     * - commit order payment
     *
     * can be used by controllers to commit orders with payment
     *
     * @param $paymentResponseParams
     * @param \OnlineShop\Framework\PaymentManager\Payment\IPayment $paymentProvider
     * @return \OnlineShop\Framework\Model\AbstractOrder
     */
    public function handlePaymentResponseAndCommitOrderPayment($paymentResponseParams, \OnlineShop\Framework\PaymentManager\Payment\IPayment $paymentProvider);

    /**
     * commits order payment
     *   - updates order payment information in order object
     *   - only when payment status == [ORDER_STATE_COMMITTED, ORDER_STATE_PAYMENT_AUTHORIZED] -> order is committed
     *
     * use this for committing order when payment is activated
     *
     * @param \OnlineShop\Framework\PaymentManager\IStatus $paymentStatus
     * @param \OnlineShop\Framework\PaymentManager\Payment\IPayment $paymentProvider
     * @return \OnlineShop\Framework\Model\AbstractOrder
     */
    public function commitOrderPayment(\OnlineShop\Framework\PaymentManager\IStatus $paymentStatus, \OnlineShop\Framework\PaymentManager\Payment\IPayment $paymentProvider);

    /**
     * commits order
     *
     * @param \OnlineShop\Framework\Model\AbstractOrder $order
     * @return \OnlineShop\Framework\Model\AbstractOrder
     */
    public function commitOrder(\OnlineShop\Framework\Model\AbstractOrder $order);

    /**
     * @param string $confirmationMail
     */
    public function setConfirmationMail($confirmationMail);


    /**
     * cleans up orders with state pending payment after 1h
     *
     * @return void
     */
    public function cleanUpPendingOrders();
}
