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


namespace OnlineShop\Framework\OrderManager;

use \OnlineShop\Framework\Factory;
use OnlineShop_Framework_IPayment;
use OnlineShop_Framework_Payment_IStatus;

use Zend_Currency;

use OnlineShop_Framework_AbstractOrder as Order;
use \OnlineShop\Framework\Model\AbstractOrderItem as OrderItem;
use Pimcore\Model\Object\Fieldcollection\Data\PaymentInfo;
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
     * @return Zend_Currency
     */
    public function getCurrency();


    /**
     * @return bool
     */
    public function hasPayment();


    /**
     * @return OnlineShop_Framework_IPayment
     */
    public function getPaymentProvider();


    /**
     * @param OnlineShop_Framework_IPayment $paymentProvider
     *
     * @return Order
     */
    public function setPaymentProvider(OnlineShop_Framework_IPayment $paymentProvider);


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
     * @param OnlineShop_Framework_Payment_IStatus $status
     *
     * @return IOrderAgent
     */
    public function updatePayment(OnlineShop_Framework_Payment_IStatus $status);


    /**
     * @return Note[]
     */
    public function getFullChangeLog();
}
