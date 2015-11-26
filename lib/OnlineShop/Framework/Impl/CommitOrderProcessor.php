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


/**
 * Class OnlineShop_Framework_Impl_CommitOrderProcessor
 */
class OnlineShop_Framework_Impl_CommitOrderProcessor implements OnlineShop_Framework_ICommitOrderProcessor {

    /**
     * @var string
     */
    protected $confirmationMail = "/emails/order-confirmation";

    /**
     * @param string $confirmationMail
     */
    public function setConfirmationMail($confirmationMail) {
        if(!empty($confirmationMail)) {
            $this->confirmationMail = $confirmationMail;
        }
    }

    /**
     * @param $paymentResponseParams
     * @param OnlineShop_Framework_IPayment $paymentProvider
     * @return OnlineShop_Framework_AbstractOrder
     * @throws Exception
     */
    public function handlePaymentResponseAndCommitOrderPayment($paymentResponseParams, OnlineShop_Framework_IPayment $paymentProvider) {

        //since handle response can throw exceptions and commitOrderPayment must be executed,
        // this needs to be in a try-catch block
        try {
            $paymentStatus = $paymentProvider->handleResponse($paymentResponseParams);
        } catch(Exception $e) {
            \Logger::err($e);

            //create payment status with error message and cancelled payment
            $paymentStatus = new OnlineShop_Framework_Impl_Payment_Status(
                $paymentResponseParams['orderIdent'], "unknown", "there was an error: " . $e->getMessage(), OnlineShop_Framework_Payment_IStatus::STATUS_CANCELLED
            );
        }

        return $this->commitOrderPayment($paymentStatus, $paymentProvider);
    }

    /**
     * @param OnlineShop_Framework_Payment_IStatus $paymentStatus
     * @param OnlineShop_Framework_IPayment $paymentProvider
     * @return OnlineShop_Framework_AbstractOrder
     * @throws Exception
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function commitOrderPayment(OnlineShop_Framework_Payment_IStatus $paymentStatus, OnlineShop_Framework_IPayment $paymentProvider) {

        //update order payment -> e.g. setting all available payment information to order object
        $orderManager = \OnlineShop_Framework_Factory::getInstance()->getOrderManager();
        $order = $orderManager->getOrderByPaymentStatus($paymentStatus);

        if(empty($order)) {
            $message = "No order found for payment status: " . print_r($paymentStatus, true);
            \Logger::error($message);
            throw new Exception($message);
        }


        $orderAgent = $orderManager->createOrderAgent( $order );
        $orderAgent->setPaymentProvider( $paymentProvider );

        $order = $orderAgent->updatePayment( $paymentStatus )->getOrder();

        if (in_array($paymentStatus->getStatus(), [OnlineShop_Framework_AbstractOrder::ORDER_STATE_COMMITTED, OnlineShop_Framework_AbstractOrder::ORDER_STATE_PAYMENT_AUTHORIZED])) {
            //only when payment state is committed or authorized -> proceed and commit order
            $order = $this->commitOrder( $order );
        } else {
            $order->setOrderState(null);
            $order->save();
        }

        return $order;

    }

    /**
     * @param OnlineShop_Framework_AbstractOrder $order
     *
     * @return OnlineShop_Framework_AbstractOrder
     * @throws Exception
     */
    public function commitOrder(OnlineShop_Framework_AbstractOrder $order) {
        try {
            $this->processOrder($order);
            $order->setOrderState(OnlineShop_Framework_AbstractOrder::ORDER_STATE_COMMITTED);
            $order->save();
        } catch(Exception $e) {
            $order->delete();
            throw $e;
        }

        try {
            $this->sendConfirmationMail($order);
        } catch(Exception $e) {
            Logger::err("Error during sending confirmation e-mail", $e);
        }
        return $order;
    }

    protected function sendConfirmationMail(OnlineShop_Framework_AbstractOrder $order) {
        $params = array();
        $params["order"] = $order;
        $params["customer"] = $order->getCustomer();
        $params["ordernumber"] = $order->getOrdernumber();

        $mail = new \Pimcore\Mail(array("document" => $this->confirmationMail, "params" => $params));
        if($order->getCustomer()) {
            $mail->addTo($order->getCustomer()->getEmail());
            $mail->send();
        } else {
            Logger::err("No Customer found!");
        }
    }

    /**
     * implementation-specific processing of order, must be implemented in subclass (e.g. sending order to ERP-system)
     *
     * @param OnlineShop_Framework_AbstractOrder $order
     */
    protected function processOrder(OnlineShop_Framework_AbstractOrder $order) {
        //nothing to do
    }

    /**
     * @throws Exception
     */
    public function cleanUpPendingOrders() {
        $orderListClass = $this->orderClass . "_List";
        if(!class_exists($orderListClass)) {
            throw new Exception("Class $orderListClass does not exist.");
        }

        $timestamp = Zend_Date::now()->sub(1, Zend_Date::HOUR)->get();

        //Abort orders with payment pending
        $list = new $orderListClass();
        $list->addFieldCollection("PaymentInfo");
        $list->setCondition("orderState = ? AND orderdate < ?", array(OnlineShop_Framework_AbstractOrder::ORDER_STATE_PAYMENT_PENDING, $timestamp));

        foreach($list as $order) {
            Logger::warn("Setting order " . $order->getId() . " to " . OnlineShop_Framework_AbstractOrder::ORDER_STATE_ABORTED);
            $order->setOrderState(OnlineShop_Framework_AbstractOrder::ORDER_STATE_ABORTED);
            $order->save();
        }

        //Abort payments with payment pending
        $list = new $orderListClass();
        $list->addFieldCollection("PaymentInfo", "paymentinfo");
        $list->setCondition("`PaymentInfo~paymentinfo`.paymentState = ? AND `PaymentInfo~paymentinfo`.paymentStart < ?", array(OnlineShop_Framework_AbstractOrder::ORDER_STATE_PAYMENT_PENDING, $timestamp));
        foreach($list as $order) {
            $payments = $order->getPaymentInfo();
            foreach($payments as $payment) {
                if($payment->getPaymentState() == OnlineShop_Framework_AbstractOrder::ORDER_STATE_PAYMENT_PENDING && $payment->getPaymentStart()->get() < $timestamp) {
                    Logger::warn("Setting order " . $order->getId() . " payment " . $payment->getInternalPaymentId() . " to " . OnlineShop_Framework_AbstractOrder::ORDER_STATE_ABORTED);
                    $payment->setPaymentState(OnlineShop_Framework_AbstractOrder::ORDER_STATE_ABORTED);
                }
            }
            $order->save();
        }

    }
}
