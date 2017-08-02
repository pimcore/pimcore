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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderManagerLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IStatus;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\IPayment;

class CheckoutManager implements ICheckoutManager
{
    /**
     * Constants for custom environment item names for persisting state of checkout
     * always concatenated with current cart id
     */
    const CURRENT_STEP = 'checkout_current_step';
    const FINISHED = 'checkout_finished';
    const TRACK_ECOMMERCE = 'checkout_trackecommerce';
    const TRACK_ECOMMERCE_UNIVERSAL = 'checkout_trackecommerce_universal';

    /**
     * @var ICart
     */
    protected $cart;

    /**
     * @var IEnvironment
     */
    protected $environment;

    /**
     * @var IOrderManagerLocator
     */
    protected $orderManagers;

    /**
     * @var ICommitOrderProcessorLocator
     */
    protected $commitOrderProcessors;

    /**
     * Payment Provider
     *
     * @var IPayment
     */
    protected $payment;

    /**
     * Needed for effective access to one specific checkout step
     *
     * @var ICheckoutStep[]
     */
    protected $checkoutSteps = [];

    /**
     * Needed for preserving order of checkout steps
     *
     * @var ICheckoutStep[]
     */
    protected $checkoutStepOrder = [];

    /**
     * @var ICheckoutStep
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
     * @param ICart $cart
     * @param IEnvironment $environment
     * @param IOrderManagerLocator $orderManagers
     * @param ICommitOrderProcessorLocator $commitOrderProcessors
     * @param ICheckoutStep[] $checkoutSteps
     * @param IPayment|null $paymentProvider
     */
    public function __construct(
        ICart $cart,
        IEnvironment $environment,
        IOrderManagerLocator $orderManagers,
        ICommitOrderProcessorLocator $commitOrderProcessors,
        array $checkoutSteps,
        IPayment $paymentProvider = null
    ) {
        $this->cart = $cart;
        $this->environment = $environment;

        $this->orderManagers         = $orderManagers;
        $this->commitOrderProcessors = $commitOrderProcessors;

        $this->payment = $paymentProvider;

        $this->setCheckoutSteps($checkoutSteps);
    }

    /**
     * @param ICheckoutStep[] $checkoutSteps
     */
    protected function setCheckoutSteps(array $checkoutSteps)
    {
        if (0 === count($checkoutSteps)) {
            throw new \InvalidArgumentException('Checkout manager needs at least one checkout step');
        }

        foreach ($checkoutSteps as $checkoutStep) {
            $this->addCheckoutStep($checkoutStep);
        }

        $this->initializeStepState();
    }

    protected function addCheckoutStep(ICheckoutStep $checkoutStep)
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
                    $currentStepItem
                ));
            }

            $this->currentStep = $this->checkoutSteps[$currentStepItem];
        }

        // if no step is set and cart is not finished -> set current step to first step of checkout
        if (null === $this->currentStep && !$this->isFinished()) {
            $this->currentStep = $this->checkoutStepOrder[0];
        }
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
     * @inheritdoc
     */
    public function startOrderPayment()
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

        $orderAgent = $orderManager->createOrderAgent($order);
        $paymentInfo = $orderAgent->startPayment();

        // always set order state to payment pending when calling start payment
        if ($order->getOrderState() != $order::ORDER_STATE_PAYMENT_PENDING) {
            $order->setOrderState($order::ORDER_STATE_PAYMENT_PENDING);
            $order->save();
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
    protected function updateEnvironmentAfterOrderCommit(AbstractOrder $order)
    {
        if (empty($order->getOrderState())) {
            // if payment not successful -> set current checkout step to last step and checkout to not finished
            // last step must be committed again in order to restart payment or e.g. commit without payment?
            $this->currentStep = $this->checkoutStepOrder[count($this->checkoutStepOrder) - 1];

            $this->environment->setCustomItem(self::CURRENT_STEP . '_' . $this->cart->getId(), $this->currentStep->getName());
        } else {
            $this->cart->delete();
            $this->environment->removeCustomItem(self::CURRENT_STEP . '_' . $this->cart->getId());

            // TODO deprecated?
            // setting e-commerce tracking information to environment for later use in view
            $this->environment->setCustomItem(self::TRACK_ECOMMERCE . '_' . $order->getOrdernumber(), $this->generateGaEcommerceCode($order));
            $this->environment->setCustomItem(self::TRACK_ECOMMERCE_UNIVERSAL . '_' . $order->getOrdernumber(), $this->generateUniversalEcommerceCode($order));
        }

        $this->environment->save();
    }

    /**
     * @inheritdoc
     */
    public function handlePaymentResponseAndCommitOrderPayment($paymentResponseParams)
    {
        $commitOrderProcessor = $this->commitOrderProcessors->getCommitOrderProcessor();

        // check if order is already committed and payment information with same internal payment id has same state
        // if so, do nothing and return order
        if ($committedOrder = $commitOrderProcessor->committedOrderWithSamePaymentExists($paymentResponseParams, $this->getPayment())) {
            return $committedOrder;
        }

        if (!$this->payment) {
            throw new UnsupportedException('Payment is not activated');
        }

        if ($this->isCommitted()) {
            throw new UnsupportedException('Order is already committed.');
        }

        if (!$this->isFinished()) {
            throw new UnsupportedException('Checkout is not finished yet.');
        }

        // delegate commit order to commit order processor
        $order = $commitOrderProcessor->handlePaymentResponseAndCommitOrderPayment($paymentResponseParams, $this->getPayment());
        $this->updateEnvironmentAfterOrderCommit($order);

        return $order;
    }

    /**
     * @inheritdoc
     */
    public function commitOrderPayment(IStatus $status)
    {
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
        $order = $this->commitOrderProcessors->getCommitOrderProcessor()->commitOrderPayment($status, $this->getPayment());
        $this->updateEnvironmentAfterOrderCommit($order);

        return $order;
    }

    /**
     * @inheritdoc
     */
    public function commitOrder()
    {
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
     * TODO deprecated?
     *
     * generates classic google analytics e-commerce tracking code
     *
     * @param AbstractOrder $order
     *
     * @return string
     *
     * @throws UnsupportedException
     */
    protected function generateGaEcommerceCode(AbstractOrder $order)
    {
        $code = '';

        $shipping = 0;
        $modifications = $order->getPriceModifications();
        if (null !== $modifications) {
            foreach ($modifications as $modification) {
                if ($modification->getName() == 'shipping') {
                    $shipping = $modification->getAmount();
                    break;
                }
            }
        }

        $code .= "
            _gaq.push(['_addTrans',
              '" . $order->getOrdernumber() . "',  // order ID - required
              '',                                  // affiliation or store name
              '" . $order->getTotalPrice() . "',   // total - required
              '',                                  // tax
              '" . $shipping . "',                 // shipping
              '',                                  // city
              '',                                  // state or province
              ''                                   // country
            ]);
        \n";

        $items = $order->getItems();
        if (!empty($items)) {
            foreach ($items as $item) {
                $category = '';
                $p = $item->getProduct();
                if ($p && method_exists($p, 'getCategories')) {
                    $categories = $p->getCategories();
                    if ($categories) {
                        $category = $categories[0];
                        if (method_exists($category, 'getName')) {
                            $category = $category->getName();
                        }
                    }
                }

                $code .= "
                    _gaq.push(['_addItem',
                        '" . $order->getOrdernumber() . "',                                      // order ID - required
                        '" . $item->getProductNumber() . "',                                     // SKU/code - required
                        '" . str_replace(["\n"], [' '], $item->getProductName()) . "', // product name
                        '" . $category . "',                                                     // category or variation
                        '" . $item->getTotalPrice() / $item->getAmount() . "',                   // unit price - required
                        '" . $item->getAmount() . "'                                             // quantity - required
                    ]);
                \n";
            }
        }

        $code .= "_gaq.push(['_trackTrans']);";

        return $code;
    }

    /**
     * TODO deprecated?
     *
     * generates universal google analytics e-commerce tracking code
     *
     * @param AbstractOrder $order
     *
     * @return string
     *
     * @throws UnsupportedException
     */
    protected function generateUniversalEcommerceCode(AbstractOrder $order)
    {
        $code = "ga('require', 'ecommerce', 'ecommerce.js');\n";

        $shipping = 0;
        $modifications = $order->getPriceModifications();
        if (null !== $modifications) {
            foreach ($modifications as $modification) {
                if ($modification->getName() == 'shipping') {
                    $shipping = $modification->getAmount();
                    break;
                }
            }
        }

        $code .= "
            ga('ecommerce:addTransaction', {
              'id': '" . $order->getOrdernumber() . "',         // Transaction ID. Required.
              'affiliation': '',                                // Affiliation or store name.
              'revenue': '" . $order->getTotalPrice() . "',     // Grand Total.
              'shipping': '" . $shipping . "',                  // Shipping.
              'tax': ''                                         // Tax.
            });
        \n";

        $items = $order->getItems();
        if (!empty($items)) {
            foreach ($items as $item) {
                $category = '';
                $p = $item->getProduct();
                if ($p && method_exists($p, 'getCategories')) {
                    $categories = $p->getCategories();
                    if ($categories) {
                        $category = $categories[0];
                        if (method_exists($category, 'getName')) {
                            $category = $category->getName();
                        }
                    }
                }

                $code .= "
                    ga('ecommerce:addItem', {
                      'id': '" . $order->getOrdernumber() . "',                      // Transaction ID. Required.
                      'name': '" . str_replace(["\n"], [' '], $item->getProductName()) . "',                      // Product name. Required.
                      'sku': '" . $item->getProductNumber() . "',                     // SKU/code.
                      'category': '" . $category . "',                                // Category or variation.
                      'price': '" . $item->getTotalPrice() / $item->getAmount() . "', // Unit price.
                      'quantity': '" . $item->getAmount() . "'                        // Quantity.
                    });
                \n";
            }
        }

        $code .= "ga('ecommerce:send');\n";

        return $code;
    }

    /**
     * @inheritdoc
     */
    public function commitStep(ICheckoutStep $step, $data)
    {
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
        return $this->checkoutSteps[$stepName];
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
        return $this->currentStep;
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
