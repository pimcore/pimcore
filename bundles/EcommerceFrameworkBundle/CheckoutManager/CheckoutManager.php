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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Agent;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManagerLocatorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\PaymentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\QPay;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\Event\Ecommerce\CheckoutManagerEvents;
use Pimcore\Event\Model\Ecommerce\CheckoutManagerStepsEvent;
use Pimcore\Model\DataObject\Fieldcollection\Data\PaymentInfo;
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
     * @var PaymentInterface
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
     * CheckoutManager constructor.
     *
     * @param CartInterface $cart
     * @param EnvironmentInterface $environment
     * @param OrderManagerLocatorInterface $orderManagers
     * @param CommitOrderProcessorLocatorInterface $commitOrderProcessors
     * @param array $checkoutSteps
     * @param EventDispatcherInterface $eventDispatcher
     * @param PaymentInterface|null $paymentProvider
     */
    public function __construct(
        CartInterface $cart,
        EnvironmentInterface $environment,
        OrderManagerLocatorInterface $orderManagers,
        CommitOrderProcessorLocatorInterface $commitOrderProcessors,
        array $checkoutSteps,
        EventDispatcherInterface $eventDispatcher,
        PaymentInterface $paymentProvider = null
    ) {
        @trigger_error(
            'Class ' . self::class . ' is deprecated since version 6.1.0 and will be removed in 7.0.0. ' .
            ' Use ' . \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\CheckoutManager::class . ' class instead.',
            E_USER_DEPRECATED
        );

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
        $this->eventDispatcher->dispatch(CheckoutManagerEvents::INITIALIZE_STEP_STATE, $event);
        $this->currentStep = $event->getCurrentStep();
    }

    /**
     * @inheritdoc
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

        // create order
        $orderManager = $this->orderManagers->getOrderManager();
        $order = $orderManager->getOrCreateOrderFromCart($this->cart);

        if ($order->getOrderState() === AbstractOrder::ORDER_STATE_COMMITTED) {
            throw new UnsupportedException('Order is already committed');
        }

        return $order;
    }

    /**
     * @inheritdoc
     */
    public function initOrderPayment()
    {
        $order = $this->checkIfPaymentIsPossible();

        $orderManager = $this->orderManagers->getOrderManager();
        $orderAgent = $orderManager->createOrderAgent($order);
        $paymentInfo = $orderAgent->initPayment();

        return $paymentInfo;
    }

    /**
     * @inheritdoc
     */
    public function startOrderPayment()
    {
        $order = $this->checkIfPaymentIsPossible();

        $orderManager = $this->orderManagers->getOrderManager();
        $orderAgent = $orderManager->createOrderAgent($order);
        $paymentInfo = $orderAgent->startPayment();

        // always set order state to payment pending when calling start payment
        if ($order->getOrderState() != $order::ORDER_STATE_PAYMENT_PENDING) {
            $order->setOrderState($order::ORDER_STATE_PAYMENT_PENDING);
            $order->save(['versionNote' => 'CheckoutManager::startOrderPayment - set order state to ' . $order::ORDER_STATE_PAYMENT_PENDING . '.']);
        }

        return $paymentInfo;
    }

    /**
     * @inheritdoc
     */
    public function cancelStartedOrderPayment()
    {
        $orderManager = $this->orderManagers->getOrderManager();
        $order = $orderManager->getOrderFromCart($this->cart);

        if ($order) {
            $orderAgent = $orderManager->createOrderAgent($order);

            return $orderAgent->cancelStartedOrderPayment();
        }
    }

    /**
     * @inheritdoc
     */
    public function getOrder()
    {
        return $this->orderManagers->getOrderManager()->getOrCreateOrderFromCart($this->cart);
    }

    /**
     * Updates and cleans up environment after order is committed
     *
     * @param AbstractOrder $order
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
     * @inheritdoc
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
     * @param PaymentInterface $provider
     * @param AbstractOrder $sourceOrder
     *
     * @throws \Exception
     */
    protected function verifyRecurringPayment(PaymentInterface $provider, AbstractOrder $sourceOrder, string $customerId)
    {

        /* @var OrderManager $orderManager */
        $orderManager = $this->orderManagers->getOrderManager();

        if (!$provider->isRecurringPaymentEnabled()) {
            throw new \Exception("Recurring Payment is not enabled or is not supported by payment provider [{$provider->getName()}].");
        }

        if (!$orderManager->isValidOrderForRecurringPayment($sourceOrder, $this->getPayment(), $customerId)) {
            throw new \Exception('The given source order is not valid for recurring payment.');
        }
    }

    /**
     * @param AbstractOrder $sourceOrder
     * @param string $customerId
     *
     * @return null|AbstractOrder
     */
    public function startAndCommitRecurringOrderPayment(AbstractOrder $sourceOrder, string $customerId)
    {
        /* @var PaymentInfo $targetPaymentInfo */
        $targetPaymentInfo = $this->startOrderPayment();

        /* @var OrderManager $orderManager */
        $orderManager = $this->orderManagers->getOrderManager();

        /* @var Agent $sourceOrderAgent */
        $sourceOrderAgent = $orderManager->createOrderAgent($sourceOrder);

        /* @var QPay $paymentProvider */
        $paymentProvider = $sourceOrderAgent->getPaymentProvider();
        $this->verifyRecurringPayment($paymentProvider, $sourceOrder, $customerId);

        $targetOrder = $orderManager->getOrderFromCart($this->getCart());

        /* @var Agent $targetOrderAgent */
        $targetOrderAgent = $orderManager->createOrderAgent($targetOrder);

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
     * @inheritdoc
     */
    public function commitOrderPayment(StatusInterface $status, AbstractOrder $sourceOrder = null)
    {
        $this->validateCheckoutSteps();

        if (!$this->payment) {
            throw new UnsupportedException('Payment is not activated');
        }

        if ($this->isCommitted()) {
            throw new UnsupportedException('Order already committed.');
        }

        if (!$this->isFinished()) {
            throw new UnsupportedException('Checkout not finished yet.');
        }

        // delegate commit order to commit order processor
        $order = $this->commitOrderProcessors->getCommitOrderProcessor()->commitOrderPayment($status, $this->getPayment(), $sourceOrder);
        $this->updateEnvironmentAfterOrderCommit($order);

        return $order;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function commitStep(CheckoutStepInterface $step, $data)
    {
        $this->validateCheckoutSteps();

        $event = new CheckoutManagerStepsEvent($this, $step, ['data' => $data]);
        $this->eventDispatcher->dispatch(CheckoutManagerEvents::PRE_COMMIT_STEP, $event);
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
        $this->eventDispatcher->dispatch(CheckoutManagerEvents::POST_COMMIT_STEP, $event);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @inheritdoc
     */
    public function getCheckoutStep($stepName)
    {
        return $this->checkoutSteps[$stepName] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getCheckoutSteps()
    {
        return $this->checkoutStepOrder;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function isFinished()
    {
        return $this->finished;
    }

    /**
     * @inheritdoc
     */
    public function isCommitted()
    {
        $order = $this->orderManagers->getOrderManager()->getOrderFromCart($this->cart);

        return $order && $order->getOrderState() === $order::ORDER_STATE_COMMITTED;
    }

    /**
     * @inheritdoc
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @inheritdoc
     */
    public function cleanUpPendingOrders()
    {
        $this->commitOrderProcessors->getCommitOrderProcessor()->cleanUpPendingOrders();
    }
}
