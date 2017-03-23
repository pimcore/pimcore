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
 * Interface \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\ICommitOrderProcessor
 */
interface ICommitOrderProcessor {


    /**
     * check if order is already committed and payment information with same internal payment id has same state
     *
     * @param array|\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\IStatus $paymentResponseParams
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\Payment\IPayment $paymentProvider
     * @return null|\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder
     * @throws \Exception
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\UnsupportedException
     */
    public function committedOrderWithSamePaymentExists($paymentResponseParams, \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\Payment\IPayment $paymentProvider);

    /**
     * facade method for
     * - handling payment response and
     * - commit order payment
     *
     * can be used by controllers to commit orders with payment
     *
     * @param $paymentResponseParams
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\Payment\IPayment $paymentProvider
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder
     */
    public function handlePaymentResponseAndCommitOrderPayment($paymentResponseParams, \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\Payment\IPayment $paymentProvider);

    /**
     * commits order payment
     *   - updates order payment information in order object
     *   - only when payment status == [ORDER_STATE_COMMITTED, ORDER_STATE_PAYMENT_AUTHORIZED] -> order is committed
     *
     * use this for committing order when payment is activated
     *
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\IStatus $paymentStatus
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\Payment\IPayment $paymentProvider
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder
     */
    public function commitOrderPayment(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\IStatus $paymentStatus, \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\Payment\IPayment $paymentProvider);

    /**
     * commits order
     *
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder $order
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder
     */
    public function commitOrder(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder $order);

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
