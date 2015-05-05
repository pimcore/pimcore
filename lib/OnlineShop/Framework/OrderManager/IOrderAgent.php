<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 07.04.2015
 * Time: 16:47
 */

namespace OnlineShop\Framework\OrderManager;

use OnlineShop_Framework_IPayment;
use OnlineShop_Framework_Payment_IStatus;

use Exception;
use Zend_Currency;

use OnlineShop_Framework_AbstractOrder as Order;
use OnlineShop_Framework_AbstractOrderItem as OrderItem;
use Pimcore\Model\Object\Fieldcollection\Data\PaymentInfo;


interface IOrderAgent
{
    /**
     * @param Order $order
     */
    public function __construct(Order $order);


    /**
     * @return Order
     */
    public function getOrder();


    /**
     * cancel order item and refund payment
     *
     * @param OrderItem $item
     *
     * @return $this
     * @throws \Exception
     */
    public function itemCancel(OrderItem $item);

//
//    public function itemReturn(OrderItem $item);
//

    /**
     * change order item
     *
     * @param OrderItem $item
     * @param int $amount
     *
     * @return $this
     */
    public function itemChangeAmount(OrderItem $item, $amount);

//
//    public function itemSetStatus(OrderItem $item, $amount);


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
    public function startPayment($forceNew = false);

    /**
     * @param OnlineShop_Framework_Payment_IStatus $status
     *
     * @return IOrderAgent
     */
    public function updatePayment(OnlineShop_Framework_Payment_IStatus $status);
}
