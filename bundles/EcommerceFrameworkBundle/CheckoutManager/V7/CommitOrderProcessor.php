<?php

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\PaymentNotSuccessfulException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManagerLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\PaymentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Event\Ecommerce\CommitOrderProcessorEvents;
use Pimcore\Event\Model\Ecommerce\CommitOrderProcessorEvent;
use Pimcore\Event\Model\Ecommerce\SendConfirmationMailEvent;
use Pimcore\Log\ApplicationLogger;
use Pimcore\Log\FileObject;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\Factory as LockFactory;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommitOrderProcessor extends \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessor implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ApplicationLogger
     */
    protected $applicationLogger;

    /**
     * @var LockFactory
     */
    private $lockFactory;

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
     * @inheritdoc
     */
    public function commitOrderPayment(StatusInterface $paymentStatus, PaymentInterface $paymentProvider, AbstractOrder $sourceOrder = null)
    {
        // acquire lock to make sure only one process is committing order payment
        $lock = $this->lockFactory->createLock(self::LOCK_KEY . $paymentStatus->getInternalPaymentId());
        $lock->acquire(true);

        $event = new CommitOrderProcessorEvent($this, null, ['paymentStatus' => $paymentStatus]);
        $this->eventDispatcher->dispatch(CommitOrderProcessorEvents::PRE_COMMIT_ORDER_PAYMENT, $event);
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
        $this->eventDispatcher->dispatch(CommitOrderProcessorEvents::POST_COMMIT_ORDER_PAYMENT, $event);

        $lock->release();

        return $order;
    }

    /**
     * @inheritdoc
     */
    public function commitOrder(AbstractOrder $order)
    {
        $this->eventDispatcher->dispatch(CommitOrderProcessorEvents::PRE_COMMIT_ORDER, new CommitOrderProcessorEvent($this, $order));

        $this->processOrder($order);

        $order->setOrderState(AbstractOrder::ORDER_STATE_COMMITTED);
        $order->save(['versionNote' => 'CommitOrderProcessor::commitOrder - set state to committed before sending confirmation mail.']);

        try {
            $this->sendConfirmationMail($order);
        } catch (\Exception $e) {
            $this->logger->error('Error during sending confirmation e-mail: ' . $e);
        }

        $this->eventDispatcher->dispatch(CommitOrderProcessorEvents::POST_COMMIT_ORDER, new CommitOrderProcessorEvent($this, $order));

        return $order;
    }

    /**
     * @inheritdoc
     */
    protected function sendConfirmationMail(AbstractOrder $order)
    {
        $event = new SendConfirmationMailEvent($this, $order, $this->confirmationMail);
        $this->eventDispatcher->dispatch(CommitOrderProcessorEvents::SEND_CONFIRMATION_MAILS, $event);

        if (!$event->doSkipDefaultBehaviour()) {
            $params = [];
            $params['order'] = $order;
            $params['customer'] = $order->getCustomer();
            $params['ordernumber'] = $order->getOrdernumber();

            $mail = new \Pimcore\Mail(['document' => $event->getConfirmationMailConfig(), 'params' => $params]);
            if ($order->getCustomer()) {
                $mail->addTo($order->getCustomer()->getEmail());
                $mail->send();
            } else {
                $this->logger->error('No Customer found!');
            }
        }
    }
}
