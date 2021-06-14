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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutStepInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessorLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\HandlePendingPayments\HandlePendingPaymentsStrategyInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManagerLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\V7\OrderManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\V7\OrderManagerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\PaymentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\RecurringPaymentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentRequest\AbstractRequest;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse\StartPaymentResponseInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Event\Ecommerce\CheckoutManagerEvents;
use Pimcore\Event\Model\Ecommerce\CheckoutManagerStepsEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CheckoutManager implements CheckoutManagerInterface
{
    /**
     * Constants for custom environment item names for persisting state of checkout
     * always concatenated with current cart id
     */
    const CURRENT_STEP = 'checkout_current_step';

    const FINISHED = 'checkout_finished';

    /**
     * @var CartInterface
     */
    protected $cart;

    /**
     * @var EnvironmentInterface
     */
    protected $environment;

    /**
     * @var OrderManagerLocatorInterface
     */
    protected $orderManagers;

    /**
     * @var CommitOrderProcessorLocatorInterface
     */
    protected $commitOrderProcessors;

    /**
     * Payment Provider
     *
     * @var PaymentInterface|null
     */
    protected $payment;

    /**
     * Needed for effective access to one specific checkout step
     *
     * @var CheckoutStepInterface[]
     */
    protected $checkoutSteps = [];

    /**
     * Needed for preserving order of checkout steps
     *
     * @var CheckoutStepInterface[]
     */
    protected $checkoutStepOrder = [];

    /**
     * @var CheckoutStepInterface
     */
    protected $currentStep;

    /**
     * @var bool
     */
    protected $finished = false;

    /**
     * @var bool
     */
    protected $paid = true;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var HandlePendingPaymentsStrategyInterface
     */
    protected $handlePendingPaymentsStrategy = null;

    public function __construct(
        CartInterface $cart,
        EnvironmentInterface $environment,
        OrderManagerLocatorInterface $orderManagers,
        CommitOrderProcessorLocatorInterface $commitOrderProcessors,
        array $checkoutSteps,
        EventDispatcherInterface $eventDispatcher,
        PaymentInterface $paymentProvider = null
    ) {
        $this->cart = $cart;
        $this->environment = $environment;

        $this->orderManagers = $orderManagers;
        $this->commitOrderProcessors = $commitOrderProcessors;

        $this->payment = $paymentProvider;
        $this->eventDispatcher = $eventDispatcher;

        $this->setCheckoutSteps($checkoutSteps);
    }

    /**
     * @param CheckoutStepInterface[] $checkoutSteps
     */
    protected function setCheckoutSteps(array $checkoutSteps)
    {
        if (empty($checkoutSteps)) {
            return;
        }

        foreach ($checkoutSteps as $checkoutStep) {
            $this->addCheckoutStep($checkoutStep);
        }

        $this->initializeStepState();
    }

    protected function addCheckoutStep(CheckoutStepInterface $checkoutStep)
    {
        $this->checkoutStepOrder[] = $checkoutStep;
        $this->checkoutSteps[$checkoutStep->getName()] = $checkoutStep;
    }

    protected function initializeStepState()
    {
        // getting state information for checkout from custom environment items
        $this->finished = (bool) ($this->environment->getCustomItem(self::FINISHED . '_' . $this->cart->getId(), false));

        if ($currentStepItem = $this->environment->getCustomItem(self::CURRENT_STEP . '_' . $this->cart->getId())) {
            if (!isset($this->checkoutSteps[$currentStepItem])) {
                throw new \RuntimeException(sprintf(
                    'Environment defines current step as "%s", but step "%s" does not exist',
                    $currentStepItem,
                    $currentStepItem
                ));
            }

            $this->currentStep = $this->checkoutSteps[$currentStepItem];
        }

        // if no step is set and cart is not finished -> set current step to first step of checkout
        if (null === $this->currentStep && !$this->isFinished()) {
            $this->currentStep = $this->checkoutStepOrder[0];
        }

        $event = new CheckoutManagerStepsEvent($this, $this->currentStep);
        $this->eventDispatcher->dispatch($event, CheckoutManagerEvents::INITIALIZE_STEP_STATE);
        $this->currentStep = $event->getCurrentStep();
    }

    /**
     * {@inheritdoc}
     */
    public function hasActivePayment()
    {
        $orderManager = $this->orderManagers->getOrderManager();
        $order = $orderManager->getOrderFromCart($this->cart);

        if ($order) {
            $paymentInfo = $orderManager->createOrderAgent($order)->getCurrentPendingPaymentInfo();

            return !empty($paymentInfo);
        }

        return false;
    }

    /**
     * @return AbstractOrder
     *
     * @throws UnsupportedException
     */
    protected function checkIfPaymentIsPossible()
    {
        if (!$this->isFinished()) {
            throw new UnsupportedException('Checkout is not finished yet.');
        }

        if (!$this->payment) {
            throw new UnsupportedException('Payment is not activated');
        }

        /** @var OrderManagerInterface $orderManager */
        $orderManager = $this->orderManagers->getOrderManager();

        // create order
        $cart = $this->getCart();
        $order = $orderManager->getOrderFromCart($cart);

        if ($order) {
            $notAllowedOrderStates = [AbstractOrder::ORDER_STATE_ABORTED, AbstractOrder::ORDER_STATE_CANCELLED, AbstractOrder::ORDER_STATE_COMMITTED];
            if (in_array($order->getOrderState(), $notAllowedOrderStates)) {
                // recreate order if trying to start a payment with an aborted, cancelled or committed order
                $orderManager->recreateOrder($cart);
            } elseif ($orderManager->cartHasPendingPayments($cart)) {
                $this->getHandlePendingPaymentsStrategy()->handlePaymentNotAllowed(
                    $order,
                    $cart,
                    $orderManager
                );
            }
        }

        $order = $orderManager->getOrCreateOrderFromCart($cart);

        return $order;
    }

    /**
     * {@inheritdoc}
     */
    public function initOrderPayment()
    {
        $order = $this->checkIfPaymentIsPossible();

        $orderManager = $this->orderManagers->getOrderManager();
        $orderAgent = $orderManager->createOrderAgent($order);

        return $orderAgent->initPayment();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function startOrderPaymentWithPaymentProvider(AbstractRequest $paymentConfig): StartPaymentResponseInterface
    {
        $order = $this->checkIfPaymentIsPossible();

        $orderManager = $this->orderManagers->getOrderManager();
        $orderAgent = $orderManager->createOrderAgent($order);
        $orderAgent->startPayment();

        // always set order state to payment pending when calling start payment
        if ($order->getOrderState() != $order::ORDER_STATE_PAYMENT_PENDING) {
            $order->setOrderState($order::ORDER_STATE_PAYMENT_PENDING);
            $order->save(['versionNote' => 'CheckoutManager::startOrderPayment - set order state to ' . $order::ORDER_STATE_PAYMENT_PENDING . '.']);
        }

        $cart = $this->getCart();

        /** @var PaymentInterface $paymentProvider */
        $paymentProvider = $this->getPayment();

        return $paymentProvider->startPayment($orderAgent, $cart->getPriceCalculator()->getGrandTotal(), $paymentConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function cancelStartedOrderPayment()
    {
        $orderManager = $this->orderManagers->getOrderManager();
        $order = $orderManager->getOrderFromCart($this->cart);

        if ($order) {
            $orderAgent = $orderManager->createOrderAgent($order);

            return $orderAgent->cancelStartedOrderPayment();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return $this->orderManagers->getOrderManager()->getOrCreateOrderFromCart($this->cart);
    }

    /**
     * Updates and cleans up environment after order is committed
     *
     * @param AbstractOrder|null $order
     */
    protected function updateEnvironmentAfterOrderCommit(?AbstractOrder $order)
    {
        $this->validateCheckoutSteps();

        if (empty($order) || empty($order->getOrderState())) {
            // if payment not successful -> set current checkout step to last step and checkout to not finished
            // last step must be committed again in order to restart payment or e.g. commit without payment?
            $this->currentStep = $this->checkoutStepOrder[count($this->checkoutStepOrder) - 1];

            $this->environment->setCustomItem(self::CURRENT_STEP . '_' . $this->cart->getId(), $this->currentStep->getName());
        } else {
            $this->cart->delete();
            $this->environment->removeCustomItem(self::CURRENT_STEP . '_' . $this->cart->getId());
        }

        $this->environment->save();
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnsupportedException
     * @throws \Exception
     */
    public function handlePaymentResponseAndCommitOrderPayment($paymentResponseParams)
    {
        $this->validateCheckoutSteps();

        $commitOrderProcessor = $this->commitOrderProcessors->getCommitOrderProcessor();

        // check if order is already committed and payment information with same internal payment id has same state
        // if so, do nothing and return order
        if ($committedOrder = $commitOrderProcessor->committedOrderWithSamePaymentExists($paymentResponseParams, $this->getPayment())) {
            return $committedOrder;
        }

        if (!$this->payment) {
            throw new UnsupportedException('Payment is not activated');
        }

        // delegate commit order to commit order processor
        $order = null;

        try {
            $order = $commitOrderProcessor->handlePaymentResponseAndCommitOrderPayment($paymentResponseParams, $this->getPayment());
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $this->updateEnvironmentAfterOrderCommit($order);
        }

        return $order;
    }

    /**
     * Verifies if the payment provider is supported for recurring payment
     *
     * @param RecurringPaymentInterface $provider
     * @param AbstractOrder $sourceOrder
     * @param string $customerId
     *
     * @throws \Exception
     */
    protected function verifyRecurringPayment(RecurringPaymentInterface $provider, AbstractOrder $sourceOrder, string $customerId)
    {

        // @var OrderManager $orderManager
        $orderManager = $this->orderManagers->getOrderManager();

        if (!$provider->isRecurringPaymentEnabled()) {
            throw new \Exception("Recurring Payment is not enabled or is not supported by payment provider [{$provider->getName()}].");
        }

        $payment = $this->getPayment();
        if (!$payment instanceof RecurringPaymentInterface) {
            throw new \Exception("Recurring Payment is not supported by payment provider [{$payment->getName()}].");
        }

        if ($orderManager instanceof OrderManager && !$orderManager->isValidOrderForRecurringPayment($sourceOrder, $payment, $customerId)) {
            throw new \Exception('The given source order is not valid for recurring payment.');
        }
    }

    /**
     * @param AbstractOrder $sourceOrder
     * @param string $customerId
     *
     * @return null|AbstractOrder
     *
     * @throws UnsupportedException
     * @throws \Exception
     */
    public function startAndCommitRecurringOrderPayment(AbstractOrder $sourceOrder, string $customerId)
    {
        $targetOrder = $this->checkIfPaymentIsPossible();

        //verify recurring payment
        $orderManager = $this->orderManagers->getOrderManager();
        $sourceOrderAgent = $orderManager->createOrderAgent($sourceOrder);
        // @var RecurringPaymentInterface $paymentProvider
        $paymentProvider = $sourceOrderAgent->getPaymentProvider();
        $this->verifyRecurringPayment($paymentProvider, $sourceOrder, $customerId);

        //start order payment
        $targetOrderAgent = $orderManager->createOrderAgent($targetOrder);
        $targetPaymentInfo = $targetOrderAgent->startPayment();

        // always set order state to payment pending when calling start payment
        if ($targetOrder->getOrderState() != $targetOrder::ORDER_STATE_PAYMENT_PENDING) {
            $targetOrder->setOrderState($targetOrder::ORDER_STATE_PAYMENT_PENDING);
            $targetOrder->save(['versionNote' => 'CheckoutManager::startAndCommitRecurringOrderPayment - set order state to ' . $targetOrder::ORDER_STATE_PAYMENT_PENDING . '.']);
        }

        $targetOrderAgent->setPaymentProvider($paymentProvider, $sourceOrder);
        $price = new Price(
            Decimal::create($targetOrder->getTotalPrice(), 2),
            $sourceOrderAgent->getCurrency()
        );

        // execute recurPayment operation
        $paymentStatus = $paymentProvider->executeDebit(
            $price,
            $targetPaymentInfo->getInternalPaymentId()
        );

        $targetOrderAgent->updatePayment($paymentStatus);

        // delegate commit order to commit order processor
        $targetOrder = $this->commitOrderProcessors->getCommitOrderProcessor()->commitOrderPayment($paymentStatus, $this->getPayment());
        $this->updateEnvironmentAfterOrderCommit($targetOrder);

        return $targetOrder;
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnsupportedException
     */
    public function commitOrder()
    {
        $this->validateCheckoutSteps();

        if ($this->isCommitted()) {
            throw new UnsupportedException('Order already committed.');
        }

        if (!$this->isFinished()) {
            throw new UnsupportedException('Checkout not finished yet.');
        }

        // delegate commit order to commit order processor
        $order = $this->orderManagers->getOrderManager()->getOrCreateOrderFromCart($this->cart);
        $order = $this->commitOrderProcessors->getCommitOrderProcessor()->commitOrder($order);

        $this->updateEnvironmentAfterOrderCommit($order);

        return $order;
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnsupportedException
     */
    public function commitStep(CheckoutStepInterface $step, $data)
    {
        $this->validateCheckoutSteps();

        $event = new CheckoutManagerStepsEvent($this, $step, ['data' => $data]);
        $this->eventDispatcher->dispatch($event, CheckoutManagerEvents::PRE_COMMIT_STEP);
        $data = $event->getArgument('data');

        // get index of current step and index of step to commit
        $indexCurrentStep = array_search($this->currentStep, $this->checkoutStepOrder);
        $index = array_search($step, $this->checkoutStepOrder);

        // if indexCurrentStep is < index -> there are uncommitted previous steps
        if ($indexCurrentStep < $index) {
            throw new UnsupportedException('There are uncommitted previous steps.');
        }

        // delegate commit to step implementation (for data storage etc.)
        $result = $step->commit($data);

        if ($result) {
            $index++;

            if (count($this->checkoutStepOrder) > $index) {
                //setting checkout manager to next step
                $this->currentStep = $this->checkoutStepOrder[$index];

                $this->environment->setCustomItem(
                    self::CURRENT_STEP . '_' . $this->cart->getId(),
                    $this->currentStep->getName()
                );

                // checkout NOT finished
                $this->finished = false;
            } else {
                // checkout is finished
                $this->finished = true;
            }

            $this->environment->setCustomItem(
                self::FINISHED . '_' . $this->cart->getId(),
                $this->finished
            );

            $this->cart->save();
            $this->environment->save();
        }

        $event = new CheckoutManagerStepsEvent($this, $step, ['data' => $data]);
        $this->eventDispatcher->dispatch($event, CheckoutManagerEvents::POST_COMMIT_STEP);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * {@inheritdoc}
     */
    public function getCheckoutStep($stepName)
    {
        return $this->checkoutSteps[$stepName] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCheckoutSteps()
    {
        return $this->checkoutStepOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentStep()
    {
        $this->validateCheckoutSteps();

        return $this->currentStep;
    }

    protected function validateCheckoutSteps()
    {
        if (empty($this->checkoutSteps)) {
            throw new \RuntimeException('Checkout manager does not define any checkout steps');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isFinished()
    {
        return $this->finished;
    }

    /**
     * {@inheritdoc}
     */
    public function isCommitted()
    {
        $order = $this->orderManagers->getOrderManager()->getOrderFromCart($this->cart);

        return $order && $order->getOrderState() === $order::ORDER_STATE_COMMITTED;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanUpPendingOrders()
    {
        $this->commitOrderProcessors->getCommitOrderProcessor()->cleanUpPendingOrders();
    }

    /**
     * @return HandlePendingPaymentsStrategyInterface
     */
    public function getHandlePendingPaymentsStrategy(): HandlePendingPaymentsStrategyInterface
    {
        return $this->handlePendingPaymentsStrategy;
    }

    /**
     * @param HandlePendingPaymentsStrategyInterface $handlePendingPaymentsStrategy
     */
    public function setHandlePendingPaymentsStrategy(HandlePendingPaymentsStrategyInterface $handlePendingPaymentsStrategy): void
    {
        $this->handlePendingPaymentsStrategy = $handlePendingPaymentsStrategy;
    }
}
