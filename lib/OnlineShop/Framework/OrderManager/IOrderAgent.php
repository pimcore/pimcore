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

use OnlineShop_Framework_Factory;
use OnlineShop_Framework_IPayment;
use OnlineShop_Framework_Payment_IStatus;

use Zend_Currency;

use OnlineShop_Framework_AbstractOrder as Order;
use OnlineShop_Framework_AbstractOrderItem as OrderItem;
use Pimcore\Model\Object\Fieldcollection\Data\PaymentInfo;
use Pimcore\Model\Element\Note;


interface IOrderAgent
{
    /**
     * @param OnlineShop_Framework_Factory $factory
     * @param Order                        $order
     */
    public function __construct(OnlineShop_Framework_Factory $factory, Order $order);


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
     * @return PaymentInfo
     */
    public function startPayment($forceNew = true);

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
