<?php


namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7;


use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\PaymentNotSuccessfulException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\PaymentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Log\ApplicationLogger;
use Pimcore\Log\FileObject;
use Pimcore\Logger;
use Pimcore\Model\Tool\Lock;

class CommitOrderProcessor extends \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessor
{

    /**
     * @inheritdoc
     */
    public function commitOrderPayment(StatusInterface $paymentStatus, PaymentInterface $paymentProvider, AbstractOrder $sourceOrder = null)
    {
        // acquire lock to make sure only one process is committing order payment
        Lock::acquire(self::LOCK_KEY . $paymentStatus->getInternalPaymentId());

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

        Lock::release(self::LOCK_KEY . $paymentStatus->getInternalPaymentId());

        return $order;
    }

}