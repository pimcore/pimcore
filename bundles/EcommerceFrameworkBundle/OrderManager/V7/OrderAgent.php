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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\V7;

use Exception;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\PaymentNotAllowedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder as Order;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Agent;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\PaymentManagerInterface;
use Pimcore\Event\Ecommerce\OrderAgentEvents;
use Pimcore\Event\Model\Ecommerce\OrderAgentEvent;
use Pimcore\Model\DataObject\Fieldcollection\Data\PaymentInfo;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderAgent extends Agent
{
    public function __construct(
        Order $order,
        EnvironmentInterface $environment,
        PaymentManagerInterface $paymentManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->order = $order;
        $this->environment = $environment;
        $this->paymentManager = $paymentManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @inheritdoc
     */
    public function initPayment()
    {
        $currentPaymentInformation = $this->getCurrentPendingPaymentInfo();
        $order = $this->getOrder();

        $event = new OrderAgentEvent($this, ['currentPaymentInformation' => $currentPaymentInformation]);
        $this->eventDispatcher->dispatch(OrderAgentEvents::PRE_INIT_PAYMENT, $event);
        $currentPaymentInformation = $event->getArgument('currentPaymentInformation');

        if ($currentPaymentInformation) {
            if ($currentPaymentInformation->getPaymentState() == order::ORDER_STATE_PAYMENT_PENDING) {
                throw new PaymentNotAllowedException(
                    'Init payment not allowed because there is currently a payment pending. Cancel payment or recreate order.',
                    $order
                );
            }

            if ($currentPaymentInformation->getPaymentState() == order::ORDER_STATE_PAYMENT_INIT) {
                $internalPaymentIdForCurrentOrderVersion = $this->generateInternalPaymentId();

                //if order fingerprint changed, abort initialized payment and create new payment information (so set it to null)
                if ($currentPaymentInformation->getInternalPaymentId() != $internalPaymentIdForCurrentOrderVersion) {
                    $currentPaymentInformation->setPaymentState($order::ORDER_STATE_ABORTED);
                    $currentPaymentInformation->setMessage($currentPaymentInformation->getMessage() . ' - aborted be because order changed after payment was initialized.');
                    $order->save(['versionNote' => 'Agent::initPayment - save order to abort existing PaymentInformation.']);

                    $currentPaymentInformation = null;
                }
            }
        }

        //if no payment information available, create new one
        if (empty($currentPaymentInformation)) {
            $currentPaymentInformation = $this->createNewOrderInformation($order, order::ORDER_STATE_PAYMENT_INIT);
            $order->save(['versionNote' => 'Agent::initPayment - save order to add new PaymentInformation.']);
        }

        $this->eventDispatcher->dispatch(OrderAgentEvents::POST_INIT_PAYMENT, new OrderAgentEvent($this, ['currentPaymentInformation' => $currentPaymentInformation]));

        return $currentPaymentInformation;
    }

    /**
     * @return null|AbstractPaymentInformation|PaymentInfo
     *
     * @throws Exception
     * @throws UnsupportedException
     */
    public function startPayment()
    {
        //initialize payment (if not already done before)
        $currentPaymentInformation = $this->initPayment();

        $event = new OrderAgentEvent($this, ['currentPaymentInformation' => $currentPaymentInformation]);
        $this->eventDispatcher->dispatch(OrderAgentEvents::PRE_START_PAYMENT, $event);
        $currentPaymentInformation = $event->getArgument('currentPaymentInformation');

        $order = $this->getOrder();

        //set payment information state to pending
        $currentPaymentInformation->setPaymentState($order::ORDER_STATE_PAYMENT_PENDING);
        $order->save(['versionNote' => 'Agent::startPayment - save order to update PaymentInformation.']);

        $this->eventDispatcher->dispatch(OrderAgentEvents::POST_START_PAYMENT, new OrderAgentEvent($this, ['currentPaymentInformation' => $currentPaymentInformation]));

        return $currentPaymentInformation;
    }
}
