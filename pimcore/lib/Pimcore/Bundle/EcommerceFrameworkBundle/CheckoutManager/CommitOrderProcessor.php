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

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IStatus;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\IPayment;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Status;
use Pimcore\Log\ApplicationLogger;
use Pimcore\Log\FileObject;
use Pimcore\Logger;
use Pimcore\Model\Tool\Lock;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommitOrderProcessor implements ICommitOrderProcessor
{
    const LOCK_KEY = 'ecommerce-framework-commitorder-lock';
    const LOGGER_NAME = 'commit-order-processor';

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var string
     */
    protected $confirmationMail = '/emails/order-confirmation';

    public function __construct(Factory $factory, array $options = [])
    {
        $this->factory = $factory;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->processOptions($resolver->resolve($options));
    }

    protected function processOptions(array $options)
    {
        if (isset($options['confirmation_mail'])) {
            $this->confirmationMail = $options['confirmation_mail'];
        }
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('confirmation_mail');
    }

    protected function getOrderManager(): IOrderManager
    {
        // fetching order manager from factory at runtime as it needs to be
        // resolved from current checkout context
        return $this->factory->getOrderManager();
    }

    /**
     * @param string $confirmationMail
     */
    public function setConfirmationMail($confirmationMail)
    {
        if (!empty($confirmationMail)) {
            $this->confirmationMail = $confirmationMail;
        }
    }

    /**
     * @param $paymentResponseParams
     * @param IPayment $paymentProvider
     *
     * @return Status|IStatus
     */
    protected function getPaymentStatus($paymentResponseParams, IPayment $paymentProvider)
    {
        // since handle response can throw exceptions and commitOrderPayment must be executed,
        // this needs to be in a try-catch block
        try {
            $paymentStatus = $paymentProvider->handleResponse($paymentResponseParams);
        } catch (\Exception $e) {
            Logger::err($e);

            //create payment status with error message and cancelled payment
            $paymentStatus = new Status(
                $paymentResponseParams['orderIdent'],
                'unknown',
                'there was an error: ' . $e->getMessage(),
                IStatus::STATUS_CANCELLED
            );
        }

        return $paymentStatus;
    }

    /**
     * @inheritdoc
     */
    public function handlePaymentResponseAndCommitOrderPayment($paymentResponseParams, IPayment $paymentProvider)
    {
        // check if order is already committed and payment information with same internal payment id has same state
        // if so, do nothing and return order
        if ($committedOrder = $this->committedOrderWithSamePaymentExists($paymentResponseParams, $paymentProvider)) {
            return $committedOrder;
        }

        $paymentStatus = $this->getPaymentStatus($paymentResponseParams, $paymentProvider);

        return $this->commitOrderPayment($paymentStatus, $paymentProvider);
    }

    /**
     * @inheritdoc
     */
    public function committedOrderWithSamePaymentExists($paymentResponseParams, IPayment $paymentProvider)
    {
        if (!$paymentResponseParams instanceof IStatus) {
            $paymentStatus = $this->getPaymentStatus($paymentResponseParams, $paymentProvider);
        } else {
            $paymentStatus = $paymentResponseParams;
        }

        $order = $this->getOrderManager()->getOrderByPaymentStatus($paymentStatus);

        if ($order && $order->getOrderState() == $order::ORDER_STATE_COMMITTED) {
            $paymentInformationCollection = $order->getPaymentInfo();
            if ($paymentInformationCollection) {
                foreach ($paymentInformationCollection as $paymentInfo) {
                    if ($paymentInfo->getInternalPaymentId() == $paymentStatus->getInternalPaymentId()) {
                        if ($paymentInfo->getPaymentState() == $paymentStatus->getStatus()) {
                            return $order;
                        } else {
                            $message = 'Payment state of order ' . $order->getId() . ' does not match with new request!';
                            Logger::error($message);
                            throw new \Exception($message);
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function commitOrderPayment(IStatus $paymentStatus, IPayment $paymentProvider)
    {
        // acquire lock to make sure only one process is committing order payment
        Lock::acquire(self::LOCK_KEY . $paymentStatus->getInternalPaymentId());

        // check if order is already committed and payment information with same internal payment id has same state
        // if so, do nothing and return order
        if ($committedOrder = $this->committedOrderWithSamePaymentExists($paymentStatus, $paymentProvider)) {
            return $committedOrder;
        }

        $orderManager = $this->getOrderManager();
        $order = $orderManager->getOrderByPaymentStatus($paymentStatus);

        if (empty($order)) {
            $message = 'No order found for payment status: ' . print_r($paymentStatus, true);
            Logger::error($message);
            throw new \Exception($message);
        }

        $orderAgent = $orderManager->createOrderAgent($order);
        $orderAgent->setPaymentProvider($paymentProvider);

        $order = $orderAgent->updatePayment($paymentStatus)->getOrder();
        $this->applyAdditionalDataToOrder($order, $paymentStatus, $paymentProvider);

        if (in_array($paymentStatus->getStatus(), [AbstractOrder::ORDER_STATE_COMMITTED, AbstractOrder::ORDER_STATE_PAYMENT_AUTHORIZED])) {
            // only when payment state is committed or authorized -> proceed and commit order
            $order = $this->commitOrder($order);
        } elseif ($order->getOrderState() == $order::ORDER_STATE_COMMITTED) {

            // do not overwrite status if order is already committed. normally this shouldn't happen at all.
            $logger = ApplicationLogger::getInstance(self::LOGGER_NAME, true);
            $logger->setRelatedObject($order);
            $logger->setFileObject(new FileObject(print_r($paymentStatus, true)));
            $logger->critical('Order with ID ' . $order->getId() . ' got payment status after it was already committed.');
        } else {
            $order->setOrderState(null);
            $order->save();
        }

        Lock::release(self::LOCK_KEY . $paymentStatus->getInternalPaymentId());

        return $order;
    }

    /**
     * Method for applying additional data to the order object based on payment information
     * Called in commitOrderPayment() just after updatePayment on OrderAgent is called
     *
     * @param AbstractOrder $order
     * @param IStatus $paymentStatus
     * @param IPayment $paymentProvider
     */
    protected function applyAdditionalDataToOrder(AbstractOrder $order, IStatus $paymentStatus, IPayment $paymentProvider)
    {
        // nothing to do by default
    }

    /**
     * @inheritdoc
     */
    public function commitOrder(AbstractOrder $order)
    {
        $this->processOrder($order);

        $order->setOrderState(AbstractOrder::ORDER_STATE_COMMITTED);
        $order->save();

        try {
            $this->sendConfirmationMail($order);
        } catch (\Exception $e) {
            Logger::err('Error during sending confirmation e-mail: ' . $e);
        }

        return $order;
    }

    protected function sendConfirmationMail(AbstractOrder $order)
    {
        $params = [];
        $params['order'] = $order;
        $params['customer'] = $order->getCustomer();
        $params['ordernumber'] = $order->getOrdernumber();

        $mail = new \Pimcore\Mail(['document' => $this->confirmationMail, 'params' => $params]);
        if ($order->getCustomer()) {
            $mail->addTo($order->getCustomer()->getEmail());
            $mail->send();
        } else {
            Logger::err('No Customer found!');
        }
    }

    /**
     * Implementation-specific processing of order, must be implemented in subclass (e.g. sending order to ERP-system)
     *
     * @param AbstractOrder $order
     */
    protected function processOrder(AbstractOrder $order)
    {
        // nothing to do
    }

    /**
     * @throws \Exception
     */
    public function cleanUpPendingOrders()
    {
        $dateTime = new \DateTime();
        $dateTime->add(new \DateInterval('PT1H'));
        $timestamp = $dateTime->getTimestamp();

        $orderManager = $this->getOrderManager();

        //Abort orders with payment pending
        $list = $orderManager->buildOrderList();
        $list->addFieldCollection('PaymentInfo');
        $list->setCondition('orderState = ? AND orderdate < ?', [AbstractOrder::ORDER_STATE_PAYMENT_PENDING, $timestamp]);

        /** @var AbstractOrder $order */
        foreach ($list as $order) {
            Logger::warn('Setting order ' . $order->getId() . ' to ' . AbstractOrder::ORDER_STATE_ABORTED);
            $order->setOrderState(AbstractOrder::ORDER_STATE_ABORTED);
            $order->save();
        }

        //Abort payments with payment pending
        $list = $orderManager->buildOrderList();
        $list->addFieldCollection('PaymentInfo', 'paymentinfo');
        $list->setCondition('`PaymentInfo~paymentinfo`.paymentState = ? AND `PaymentInfo~paymentinfo`.paymentStart < ?', [AbstractOrder::ORDER_STATE_PAYMENT_PENDING, $timestamp]);

        /** @var AbstractOrder[] $list */
        foreach ($list as $order) {
            $payments = $order->getPaymentInfo();

            foreach ($payments as $payment) {
                if ($payment->getPaymentState() == AbstractOrder::ORDER_STATE_PAYMENT_PENDING && $payment->getPaymentStart()->getTimestamp() < $timestamp) {
                    Logger::warn('Setting order ' . $order->getId() . ' payment ' . $payment->getInternalPaymentId() . ' to ' . AbstractOrder::ORDER_STATE_ABORTED);
                    $payment->setPaymentState(AbstractOrder::ORDER_STATE_ABORTED);
                }
            }

            $order->save();
        }
    }
}
