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

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManagerLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\PaymentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Status;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Log\ApplicationLogger;
use Pimcore\Log\FileObject;
use Pimcore\Logger;
use Symfony\Component\Lock\Factory as LockFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommitOrderProcessor implements CommitOrderProcessorInterface
{
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

    public function __construct(LockFactory $lockFactory, OrderManagerLocatorInterface $orderManagers, array $options = [])
    {
        @trigger_error(
            'Class ' . self::class . ' is deprecated since version 6.1.0 and will be removed in 7.0.0. ' .
            ' Use ' . \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\CommitOrderProcessor::class . ' class instead.',
            E_USER_DEPRECATED
        );

        $this->orderManagers = $orderManagers;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->processOptions($resolver->resolve($options));

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
     * @var null | string
     */
    protected $lastPaymentStateResponseHash = null;

    /**
     * @var null | StatusInterface
     */
    protected $lastPaymentStatus = null;

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
     * @inheritdoc
     */
    public function handlePaymentResponseAndCommitOrderPayment($paymentResponseParams, PaymentInterface $paymentProvider)
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
     * @inheritdoc
     */
    public function commitOrderPayment(StatusInterface $paymentStatus, PaymentInterface $paymentProvider, AbstractOrder $sourceOrder = null)
    {
        // acquire lock to make sure only one process is committing order payment
        $lock = $this->lockFactory->createLock(self::LOCK_KEY . $paymentStatus->getInternalPaymentId());
        $lock->acquire(true);

        // check if order is already committed and payment information with same internal payment id has same state
        // if so, do nothing and return order
        if ($committedOrder = $this->committedOrderWithSamePaymentExists($paymentStatus, $paymentProvider)) {
            return $committedOrder;
        }

        $orderManager = $this->orderManagers->getOrderManager();
        $order = $orderManager->getOrderByPaymentStatus($paymentStatus);

        if (empty($order)) {
            $message = 'No order found for payment status: ' . print_r($paymentStatus, true);
            Logger::error($message);
            throw new \Exception($message);
        }

        $orderAgent = $orderManager->createOrderAgent($order);
        $orderAgent->setPaymentProvider($paymentProvider, $sourceOrder);

        $order = $orderAgent->updatePayment($paymentStatus)->getOrder();
        $this->applyAdditionalDataToOrder($order, $paymentStatus, $paymentProvider);

        if ($order->getOrderState() === $order::ORDER_STATE_COMMITTED) {
            // only when we receive an unsuccessful payment request after order is already committed
            // do not overwrite status if order is already committed. normally this shouldn't happen at all.
            $logger = ApplicationLogger::getInstance(self::LOGGER_NAME, true);

            $message = 'Order with ID ' . $order->getId() . ' got payment status after it was already committed.';
            $logger->critical($message,
                [
                    'fileObject' => new FileObject(print_r($paymentStatus, true)),
                    'relatedObject' => $order,
                ]
            );

            $lock->release();

            throw new UnsupportedException($message);
        }

        if (in_array($paymentStatus->getStatus(), [AbstractOrder::ORDER_STATE_COMMITTED, AbstractOrder::ORDER_STATE_PAYMENT_AUTHORIZED])) {
            // only when payment state is committed or authorized -> proceed and commit order
            $order = $this->commitOrder($order);
        } else {
            $order->setOrderState(null);
            $order->save(['versionNote' => 'CommitOrderProcessor::commitOrderPayment - set order state to null.']);
        }

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
     * @inheritdoc
     */
    public function commitOrder(AbstractOrder $order)
    {
        $this->processOrder($order);

        $order->setOrderState(AbstractOrder::ORDER_STATE_COMMITTED);
        $order->save(['versionNote' => 'CommitOrderProcessor::commitOrder - set state to committed before sending confirmation mail.']);

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
