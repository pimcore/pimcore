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

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder as Order;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrderItem as OrderItem;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\Currency;
use Pimcore\Model\Element\Note;


interface IOrderAgent
{
    /**
     * @param \OnlineShop\Framework\Factory $factory
     * @param Order                        $order
     */
    public function __construct(\OnlineShop\Framework\Factory $factory, Order $order);


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
     * @return \OnlineShop\Framework\PaymentManager\Payment\IPayment
     */
    public function getPaymentProvider();


    /**
     * @param \OnlineShop\Framework\PaymentManager\Payment\IPayment $paymentProvider
     *
     * @return Order
     */
    public function setPaymentProvider(\OnlineShop\Framework\PaymentManager\Payment\IPayment $paymentProvider);


    /**
     * Starts payment:
     * checks if payment info with PENDING payment exists and checks if order fingerprint has not changed
     * if true -> returns existing payment info
     * if false -> creates new payment info (and aborts existing PENDING payment infos)
     *
     * @return \OnlineShop\Framework\Model\AbstractPaymentInformation
     */
    public function startPayment();

    /**
     * Returns current payment info of order, or null if none exists
     *
     * @return null|\OnlineShop\Framework\Model\AbstractPaymentInformation
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
     * @return \OnlineShop\Framework\Model\AbstractOrder
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function cancelStartedOrderPayment();


    /**
     * @param \OnlineShop\Framework\PaymentManager\IStatus $status
     *
     * @return IOrderAgent
     */
    public function updatePayment(\OnlineShop\Framework\PaymentManager\IStatus $status);


    /**
     * @return Note[]
     */
    public function getFullChangeLog();
}
