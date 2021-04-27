<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7;

use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\PaymentNotSuccessfulException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManagerLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Status;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\PaymentInterface;
use Pimcore\Event\Ecommerce\CommitOrderProcessorEvents;
use Pimcore\Event\Model\Ecommerce\CommitOrderProcessorEvent;
use Pimcore\Event\Model\Ecommerce\SendConfirmationMailEvent;
use Pimcore\Log\ApplicationLogger;
use Pimcore\Log\FileObject;
use Pimcore\Logger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommitOrderProcessor implements CommitOrderProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const LOCK_KEY = 'ecommerce-framework-commitorder-lock';
    const LOGGER_NAME = 'commit-order-processor';

    /**
     * @var OrderManagerLocatorInterface
     */
    protected $orderManagers;

    /**
     * @var LockFactory
     */
    private $lockFactory;

    /**
     * @var string
     */
    protected $confirmationMail = '/emails/order-confirmation';

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ApplicationLogger
     */
    protected $applicationLogger;

    /**
     * @var null | string
     */
    protected $lastPaymentStateResponseHash = null;

    /**
     * @var null | StatusInterface
     */
    protected $lastPaymentStatus = null;

    public function __construct(LockFactory $lockFactory, OrderManagerLocatorInterface $orderManagers, EventDispatcherInterface $eventDispatcher, ApplicationLogger $applicationLogger, array $options = [])
    {
        $this->orderManagers = $orderManagers;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->processOptions($resolver->resolve($options));

        $this->eventDispatcher = $eventDispatcher;
        $this->applicationLogger = $applicationLogger;
        $this->lockFactory = $lockFactory;
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
     * @param array|StatusInterface $paymentResponseParams
     * @param PaymentInterface $paymentProvider
     *
     * @return Status|StatusInterface
     */
    protected function getPaymentStatus($paymentResponseParams, PaymentInterface $paymentProvider)
    {
        $responseHash = md5(serialize($paymentResponseParams));

        if ($this->lastPaymentStateResponseHash === $responseHash) {
            return $this->lastPaymentStatus;
        }

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
                StatusInterface::STATUS_CANCELLED
            );
        }

        $this->lastPaymentStateResponseHash = $responseHash;
        $this->lastPaymentStatus = $paymentStatus;

        return $paymentStatus;
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnsupportedException
     */
    public function handlePaymentResponseAndCommitOrderPayment($paymentResponseParams, PaymentInterface $paymentProvider)
    {
        $this->logger->info('Payment Provider Response received. ' . print_r($paymentResponseParams, true));

        // check if order is already committed and payment information with same internal payment id has same state
        // if so, do nothing and return order
        if ($committedOrder = $this->committedOrderWithSamePaymentExists($paymentResponseParams, $paymentProvider)) {
            return $committedOrder;
        }

        $paymentStatus = $this->getPaymentStatus($paymentResponseParams, $paymentProvider);

        return $this->commitOrderPayment($paymentStatus, $paymentProvider);
    }

    /**
     * {@inheritdoc}
     */
    public function committedOrderWithSamePaymentExists($paymentResponseParams, PaymentInterface $paymentProvider)
    {
        if (!$paymentResponseParams instanceof StatusInterface) {
            $paymentStatus = $this->getPaymentStatus($paymentResponseParams, $paymentProvider);
        } else {
            $paymentStatus = $paymentResponseParams;
        }

        $order = $this->orderManagers->getOrderManager()->getOrderByPaymentStatus($paymentStatus);

        if ($order && $order->getOrderState() == $order::ORDER_STATE_COMMITTED) {
            $paymentInformationCollection = $order->getPaymentInfo();
            if ($paymentInformationCollection) {
                foreach ($paymentInformationCollection as $paymentInfo) {
                    if ($paymentInfo->getInternalPaymentId() == $paymentStatus->getInternalPaymentId()) {
                        if ($paymentInfo->getPaymentState() == $paymentStatus->getStatus()) {
                            return $order;
                        } else {
                            Logger::warning('Payment state of order ' . $order->getId() . ' does not match with new request!');
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnsupportedException|PaymentNotSuccessfulException
     * @throws \Exception
     */
    public function commitOrderPayment(StatusInterface $paymentStatus, PaymentInterface $paymentProvider, AbstractOrder $sourceOrder = null)
    {
        // acquire lock to make sure only one process is committing order payment
        $lock = $this->lockFactory->createLock(self::LOCK_KEY . $paymentStatus->getInternalPaymentId());
        $lock->acquire(true);

        $event = new CommitOrderProcessorEvent($this, null, ['paymentStatus' => $paymentStatus]);
        $this->eventDispatcher->dispatch($event, CommitOrderProcessorEvents::PRE_COMMIT_ORDER_PAYMENT);
        $paymentStatus = $event->getArgument('paymentStatus');

        // check if order is already committed and payment information with same internal payment id has same state
        // if so, do nothing and return order
        if ($committedOrder = $this->committedOrderWithSamePaymentExists($paymentStatus, $paymentProvider)) {
            return $committedOrder;
        }

        $orderManager = $this->orderManagers->getOrderManager();
        $order = $orderManager->getOrderByPaymentStatus($paymentStatus);

        if (empty($order)) {
            $message = 'No order found for payment status: ' . print_r($paymentStatus, true);
            $this->logger->error($message);
            throw new \Exception($message);
        }

        $orderAgent = $orderManager->createOrderAgent($order);
        $orderAgent->setPaymentProvider($paymentProvider, $sourceOrder);

        $order = $orderAgent->updatePayment($paymentStatus)->getOrder();
        $this->applyAdditionalDataToOrder($order, $paymentStatus, $paymentProvider);

        if ($order->getOrderState() === $order::ORDER_STATE_COMMITTED) {
            $message = 'Order with ID ' . $order->getId() . ' got payment status after it was already committed.';
            $this->applicationLogger->critical($message,
                [
                    'fileObject' => new FileObject(print_r($paymentStatus, true)),
                    'relatedObject' => $order,
                    'component' => self::LOGGER_NAME,
                ]
            );

            $lock->release();

            throw new UnsupportedException($message);
        }

        if (in_array($paymentStatus->getStatus(), [StatusInterface::STATUS_CLEARED, StatusInterface::STATUS_AUTHORIZED])) {
            // only when payment state is committed or authorized -> proceed and commit order
            $order = $this->commitOrder($order);
        } elseif ($paymentStatus->getStatus() == StatusInterface::STATUS_PENDING) {
            $order->setOrderState(AbstractOrder::ORDER_STATE_PAYMENT_PENDING);
            $order->save(['versionNote' => 'CommitOrderProcessor::commitOrderPayment - set order state to Pending since payment is still pending.']);
        } else {
            $order->setOrderState(null);
            $order->save(['versionNote' => 'CommitOrderProcessor::commitOrderPayment - set order state to null because payment not successful. Payment status was' . $paymentStatus->getStatus()]);

            throw new PaymentNotSuccessfulException($order, $paymentStatus, 'Payment not successful, state was ' . $paymentStatus->getStatus());
        }

        $event = new CommitOrderProcessorEvent($this, $order, ['paymentStatus' => $paymentStatus]);
        $this->eventDispatcher->dispatch($event, CommitOrderProcessorEvents::POST_COMMIT_ORDER_PAYMENT);

        $lock->release();

        return $order;
    }

    /**
     * Method for applying additional data to the order object based on payment information
     * Called in commitOrderPayment() just after updatePayment on OrderAgent is called
     *
     * @param AbstractOrder $order
     * @param StatusInterface $paymentStatus
     * @param PaymentInterface $paymentProvider
     */
    protected function applyAdditionalDataToOrder(AbstractOrder $order, StatusInterface $paymentStatus, PaymentInterface $paymentProvider)
    {
        // nothing to do by default
    }

    /**
     * {@inheritdoc}
     */
    public function commitOrder(AbstractOrder $order)
    {
        $this->eventDispatcher->dispatch(new CommitOrderProcessorEvent($this, $order), CommitOrderProcessorEvents::PRE_COMMIT_ORDER);

        $this->processOrder($order);

        $order->setOrderState(AbstractOrder::ORDER_STATE_COMMITTED);
        $order->save(['versionNote' => 'CommitOrderProcessor::commitOrder - set state to committed before sending confirmation mail.']);

        try {
            $this->sendConfirmationMail($order);
        } catch (\Exception $e) {
            $this->logger->error('Error during sending confirmation e-mail: ' . $e);
        }

        $this->eventDispatcher->dispatch(new CommitOrderProcessorEvent($this, $order), CommitOrderProcessorEvents::POST_COMMIT_ORDER);

        return $order;
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
     * {@inheritdoc}
     */
    protected function sendConfirmationMail(AbstractOrder $order)
    {
        $event = new SendConfirmationMailEvent($this, $order, $this->confirmationMail);
        $this->eventDispatcher->dispatch($event, CommitOrderProcessorEvents::SEND_CONFIRMATION_MAILS);

        if (!$event->doSkipDefaultBehaviour()) {
            $customer = $order->getCustomer();
            $params = [];
            $params['order'] = $order;
            $params['customer'] = $customer;
            $params['ordernumber'] = $order->getOrdernumber();

            $mail = new \Pimcore\Mail(['document' => $event->getConfirmationMailConfig(), 'params' => $params]);
            if ($customer && method_exists($customer, 'getEmail')) {
                $mail->addTo($customer->getEmail());
                $mail->send();
            } else {
                $this->logger->error('No Customer found!');
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function cleanUpPendingOrders()
    {
        $dateTime = new \DateTime();
        $dateTime->sub(new \DateInterval('PT1H'));
        $timestamp = $dateTime->getTimestamp();

        $orderManager = $this->orderManagers->getOrderManager();

        //Abort orders with payment pending
        $list = $orderManager->buildOrderList();
        $list->addFieldCollection('PaymentInfo');
        $list->setCondition('orderState = ? AND o_modificationDate < ?', [AbstractOrder::ORDER_STATE_PAYMENT_PENDING, $timestamp]);

        /** @var AbstractOrder $order */
        foreach ($list as $order) {
            Logger::warn('Setting order ' . $order->getId() . ' to ' . AbstractOrder::ORDER_STATE_ABORTED);
            $order->setOrderState(AbstractOrder::ORDER_STATE_ABORTED);
            $order->save(['versionNote' => 'CommitOrderProcessor::cleanUpPendingOrders - set state to aborted.']);
        }

        //Abort payments with payment pending
        $list = $orderManager->buildOrderList();
        $list->addFieldCollection('PaymentInfo', 'paymentinfo');
        $list->setCondition('`PaymentInfo~paymentinfo`.paymentState = ? AND `PaymentInfo~paymentinfo`.paymentStart < ?', [AbstractOrder::ORDER_STATE_PAYMENT_PENDING, $timestamp]);

        /** @var AbstractOrder $order */
        foreach ($list as $order) {
            $payments = $order->getPaymentInfo();

            foreach ($payments as $payment) {
                if ($payment->getPaymentState() == AbstractOrder::ORDER_STATE_PAYMENT_PENDING && $payment->getPaymentStart()->getTimestamp() < $timestamp) {
                    Logger::warn('Setting order ' . $order->getId() . ' payment ' . $payment->getInternalPaymentId() . ' to ' . AbstractOrder::ORDER_STATE_ABORTED);
                    $payment->setPaymentState(AbstractOrder::ORDER_STATE_ABORTED);
                }
            }
            $order->save(['versionNote' => 'CommitOrderProcessor:cleanupPendingOrders- payment aborted.']);
        }
    }
}
