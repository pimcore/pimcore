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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager;

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
