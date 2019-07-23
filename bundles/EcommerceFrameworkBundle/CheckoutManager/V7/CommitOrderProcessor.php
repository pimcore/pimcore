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
use Pimcore\Logger;
use Pimcore\Model\Tool\Lock;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommitOrderProcessor extends \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessor
{

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(OrderManagerLocatorInterface $orderManagers, EventDispatcherInterface $eventDispatcher, array $options = [])
    {
        $this->orderManagers = $orderManagers;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->processOptions($resolver->resolve($options));

        $this->eventDispatcher = $eventDispatcher;
    }


    /**
     * @inheritdoc
     */
    public function commitOrderPayment(StatusInterface $paymentStatus, PaymentInterface $paymentProvider, AbstractOrder $sourceOrder = null)
    {
        // acquire lock to make sure only one process is committing order payment
        Lock::acquire(self::LOCK_KEY . $paymentStatus->getInternalPaymentId());

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
                    'relatedObject' => $order
                ]
            );
            Lock::release(self::LOCK_KEY . $paymentStatus->getInternalPaymentId());

            throw new UnsupportedException($message);
        }

        if (in_array($paymentStatus->getStatus(), [AbstractOrder::ORDER_STATE_COMMITTED, AbstractOrder::ORDER_STATE_PAYMENT_AUTHORIZED])) {
            // only when payment state is committed or authorized -> proceed and commit order
            $order = $this->commitOrder($order);
        } else {
            $order->setOrderState(null);
            $order->save(['versionNote' => 'CommitOrderProcessor::commitOrderPayment - set order state to null.']);

            throw new PaymentNotSuccessfulException($order, $paymentStatus, 'Payment not successful, state was ' . $paymentStatus->getStatus());
        }

        $event = new CommitOrderProcessorEvent($this, $order, ['paymentStatus' => $paymentStatus]);
        $this->eventDispatcher->dispatch(CommitOrderProcessorEvents::POST_COMMIT_ORDER_PAYMENT, $event);

        Lock::release(self::LOCK_KEY . $paymentStatus->getInternalPaymentId());

        return $order;
    }

    /**
     * @inheritdoc
     */
    public function commitOrder(AbstractOrder $order)
    {
        $this->eventDispatcher->dispatch(CommitOrderProcessorEvents::PRE_COMMIT_ORDER, new CommitOrderProcessorEvent($this, $order));

        $order = parent::commitOrder($order);

        $this->eventDispatcher->dispatch(CommitOrderProcessorEvents::POST_COMMIT_ORDER, new CommitOrderProcessorEvent($this, $order));

        return $order;
    }

    /**
     * @inheritdoc
     */
    protected function sendConfirmationMail(AbstractOrder $order)
    {
        $event =  new SendConfirmationMailEvent($this, $order, $this->confirmationMail);
        $this->eventDispatcher->dispatch(CommitOrderProcessorEvents::SEND_CONFIRMATION_MAILS, $event);

        if(!$event->doSkipDefaultBehaviour()) {
            $params = [];
            $params['order'] = $order;
            $params['customer'] = $order->getCustomer();
            $params['ordernumber'] = $order->getOrdernumber();

            $mail = new \Pimcore\Mail(['document' => $event->getConfirmationMailConfig(), 'params' => $params]);
            if ($order->getCustomer()) {
                $mail->addTo($order->getCustomer()->getEmail());
                $mail->send();
            } else {
                Logger::err('No Customer found!');
            }
        }
    }

    public function cleanUpPendingOrders()
    {
        parent::cleanUpPendingOrders(); // TODO: Change the autogenerated stub
    }


}