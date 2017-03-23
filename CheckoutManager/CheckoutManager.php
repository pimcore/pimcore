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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\Payment\IPayment;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tools\Config\HelperContainer;

/**
 * Class \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\CheckoutManager
 */
class CheckoutManager implements ICheckoutManager
{

    /**
     * constants for custom environment item names for persisting state of checkout
     * always concatenated with current cart id
     */
    const CURRENT_STEP = "checkout_current_step";
    const FINISHED = "checkout_finished";
    const TRACK_ECOMMERCE = "checkout_trackecommerce";
    const TRACK_ECOMMERCE_UNIVERSAL = "checkout_trackecommerce_universal";

    /**
     * needed for effective access to one specific checkout step
     *
     * @var ICheckoutStep[]
     */
    protected $checkoutSteps;

    /**
     * needed for preserving order of checkout steps
     *
     * @var ICheckoutStep[]
     */
    protected $checkoutStepOrder;

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
     * @var string
     */
    protected $confirmationMail;

    /**
     * @var ICommitOrderProcessor
     */
    protected $commitOrderProcessor;

    /**
     * @var string
     */
    protected $commitOrderProcessorClassname;

    /**
     * @var ICart
     */
    protected $cart;

    /**
     * Payment Provider
     *
     * @var IPayment
     */
    protected $payment;


    /**
     * @param ICart $cart
     * @param                            $config
     */
    public function __construct(ICart $cart, $config)
    {
        $this->cart = $cart;

        $config = new HelperContainer($config, "checkoutmanager");

        $this->commitOrderProcessorClassname = $config->commitorderprocessor->class;
        $this->confirmationMail = (string)$config->mails->confirmation;
        foreach ($config->steps as $step) {
            $step = new $step->class($this->cart);
            $this->checkoutStepOrder[] = $step;
            $this->checkoutSteps[$step->getName()] = $step;
        }


        //getting state information for checkout from custom environment items
        $env = Factory::getInstance()->getEnvironment();
        $this->finished = $env->getCustomItem(self::FINISHED . "_" . $this->cart->getId()) ? $env->getCustomItem(self::FINISHED . "_" . $this->cart->getId()) : false;
        $this->currentStep = $this->checkoutSteps[$env->getCustomItem(self::CURRENT_STEP . "_" . $this->cart->getId())];

        //if no step is set and cart is not finished -> set current step to first step of checkout
        if (empty($this->currentStep) && !$this->isFinished()) {
            $this->currentStep = $this->checkoutStepOrder[0];
        }

        // init payment provider
        if ($config->payment) {
            $this->payment = Factory::getInstance()->getPaymentManager()->getProvider($config->payment->provider);
        }

    }

    /**
     * creates, configures and returns commit order processor
     *
     * @return ICommitOrderProcessor
     */
    protected function getCommitOrderProcessor()
    {
        if (!$this->commitOrderProcessor) {
            $this->commitOrderProcessor = new $this->commitOrderProcessorClassname();
            $this->commitOrderProcessor->setConfirmationMail($this->confirmationMail);
        }
        return $this->commitOrderProcessor;
    }


    /**
     * @return bool
     */
    public function hasActivePayment()
    {
        $orderManager = Factory::getInstance()->getOrderManager();
        $order = $orderManager->getOrderFromCart($this->cart);
        if($order) {
            $paymentInfo = $orderManager->createOrderAgent($order)->getCurrentPendingPaymentInfo();
            return !empty($paymentInfo);
        }
        return false;
    }

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractPaymentInformation
     * @throws \Exception
     * @throws UnsupportedException
     */
    public function startOrderPayment()
    {
        if (!$this->isFinished()) {
            throw new UnsupportedException("Checkout not finished yet.");
        }

        if (!$this->payment) {
            throw new UnsupportedException("Payment is not activated");
        }

        //Create Order
        $orderManager = Factory::getInstance()->getOrderManager();
        $order = $orderManager->getOrCreateOrderFromCart($this->cart);

        if ($order->getOrderState() == AbstractOrder::ORDER_STATE_COMMITTED) {
            throw new UnsupportedException("Order already committed");
        }

        $orderAgent = Factory::getInstance()->getOrderManager()->createOrderAgent( $order );
        $paymentInfo = $orderAgent->startPayment();

        //always set order state to payment pending when calling start payment
        if($order->getOrderState() != $order::ORDER_STATE_PAYMENT_PENDING) {
            $order->setOrderState( $order::ORDER_STATE_PAYMENT_PENDING );
            $order->save();
        }
        
        return $paymentInfo;
    }

    /**
     * @return null|AbstractOrder
     */
    public function cancelStartedOrderPayment()
    {
        $orderManager = Factory::getInstance()->getOrderManager();
        $order = $orderManager->getOrderFromCart($this->cart);
        if($order) {
            $orderAgent = $orderManager->createOrderAgent( $order );
            return $orderAgent->cancelStartedOrderPayment();
        } else {
            return null;
        }
    }

    /**
     * @return AbstractOrder
     */
    public function getOrder()
    {
        $orderManager = Factory::getInstance()->getOrderManager();
        return $orderManager->getOrCreateOrderFromCart($this->cart);
    }

    /**
     * updates and cleans up environment after order is committed
     *
     * @param AbstractOrder $order
     * @throws UnsupportedException
     */
    protected function updateEnvironmentAfterOrderCommit(AbstractOrder $order) {
        $env = Factory::getInstance()->getEnvironment();
        if(empty($order->getOrderState())) {
            //if payment not successful -> set current checkout step to last step and checkout to not finished
            //last step must be committed again in order to restart payment or e.g. commit without payment?
            $this->currentStep = $this->checkoutStepOrder[count($this->checkoutStepOrder) - 1];

            $env->setCustomItem(self::CURRENT_STEP . "_" . $this->cart->getId(), $this->currentStep->getName());
        } else {
            $this->cart->delete();

            $env->removeCustomItem(self::CURRENT_STEP . "_" . $this->cart->getId());

            //setting e-commerce tracking information to environment for later use in view
            $env->setCustomItem(self::TRACK_ECOMMERCE . "_" . $order->getOrdernumber(), $this->generateGaEcommerceCode($order));
            $env->setCustomItem(self::TRACK_ECOMMERCE_UNIVERSAL . "_" . $order->getOrdernumber(), $this->generateUniversalEcommerceCode($order));
        }
        $env->save();
    }

    /**
     * @param $paymentResponseParams
     * @return AbstractOrder
     * @throws UnsupportedException
     */
    public function handlePaymentResponseAndCommitOrderPayment($paymentResponseParams) {

        //check if order is already committed and payment information with same internal payment id has same state
        //if so, do nothing and return order
        if($committedOrder = $this->getCommitOrderProcessor()->committedOrderWithSamePaymentExists($paymentResponseParams, $this->getPayment())) {
            return $committedOrder;
        }

        if (!$this->payment) {
            throw new UnsupportedException("Payment is not activated");
        }

        if ($this->isCommitted()) {
            throw new UnsupportedException("Order already committed.");
        }

        if (!$this->isFinished()) {
            throw new UnsupportedException("Checkout not finished yet.");
        }

        //delegate commit order to commit order processor
        $order = $this->getCommitOrderProcessor()->handlePaymentResponseAndCommitOrderPayment($paymentResponseParams, $this->getPayment());
        $this->updateEnvironmentAfterOrderCommit($order);

        return $order;
    }

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\IStatus $status
     * @return AbstractOrder
     * @throws UnsupportedException
     */
    public function commitOrderPayment(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\IStatus $status)
    {
        if (!$this->payment) {
            throw new UnsupportedException("Payment is not activated");
        }

        if ($this->isCommitted()) {
            throw new UnsupportedException("Order already committed.");
        }

        if (!$this->isFinished()) {
            throw new UnsupportedException("Checkout not finished yet.");
        }

        //delegate commit order to commit order processor
        $order = $this->getCommitOrderProcessor()->commitOrderPayment($status, $this->getPayment());
        $this->updateEnvironmentAfterOrderCommit($order);

        return $order;
    }

    /**
     * @return AbstractOrder
     * @throws UnsupportedException
     */
    public function commitOrder()
    {
        if ($this->isCommitted()) {
            throw new UnsupportedException("Order already committed.");
        }

        if (!$this->isFinished()) {
            throw new UnsupportedException("Checkout not finished yet.");
        }

        //delegate commit order to commit order processor
        $order = Factory::getInstance()->getOrderManager()->getOrCreateOrderFromCart($this->cart);
        $order = $this->getCommitOrderProcessor()->commitOrder($order);

        $this->updateEnvironmentAfterOrderCommit($order);

        return $order;
    }

    /**
     * generates classic google analytics e-commerce tracking code
     *
     * @param AbstractOrder $order
     * @return string
     * @throws UnsupportedException
     */
    protected function generateGaEcommerceCode(AbstractOrder $order)
    {
        $code = "";

        $shipping = 0;
        $modifications = $order->getPriceModifications();
        if (null !== $modifications) {
            foreach ($modifications as $modification) {
                if ($modification->getName() == "shipping") {
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

                $category = "";
                $p = $item->getProduct();
                if ($p && method_exists($p, "getCategories")) {
                    $categories = $p->getCategories();
                    if ($categories) {
                        $category = $categories[0];
                        if (method_exists($category, "getName")) {
                            $category = $category->getName();
                        }
                    }
                }

                $code .= "
                    _gaq.push(['_addItem',
                        '" . $order->getOrdernumber() . "',                                      // order ID - required
                        '" . $item->getProductNumber() . "',                                     // SKU/code - required
                        '" . str_replace(array("\n"), array(" "), $item->getProductName()) . "', // product name
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
     * generates universal google analytics e-commerce tracking code
     *
     * @param AbstractOrder $order
     * @return string
     * @throws UnsupportedException
     */
    protected function generateUniversalEcommerceCode(AbstractOrder $order)
    {
        $code = "ga('require', 'ecommerce', 'ecommerce.js');\n";

        $shipping = 0;
        $modifications = $order->getPriceModifications();
        if (null !== $modifications) {
            foreach ($modifications as $modification) {
                if ($modification->getName() == "shipping") {
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

                $category = "";
                $p = $item->getProduct();
                if ($p && method_exists($p, "getCategories")) {
                    $categories = $p->getCategories();
                    if ($categories) {
                        $category = $categories[0];
                        if (method_exists($category, "getName")) {
                            $category = $category->getName();
                        }
                    }
                }

                $code .= "
                    ga('ecommerce:addItem', {
                      'id': '" . $order->getOrdernumber() . "',                      // Transaction ID. Required.
                      'name': '" . str_replace(array("\n"), array(" "), $item->getProductName()) . "',                      // Product name. Required.
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
     * @param ICheckoutStep $step
     * @param mixed $data
     * @return bool
     * @throws UnsupportedException
     */
    public function commitStep(ICheckoutStep $step, $data)
    {
        //get index of current step and index of step to commit
        $indexCurrentStep = array_search($this->currentStep, $this->checkoutStepOrder);
        $index = array_search($step, $this->checkoutStepOrder);

        // if indexCurrentStep is < index -> there are uncommitted previous steps
        if ($indexCurrentStep < $index) {
            throw new UnsupportedException("There are uncommitted previous steps.");
        }

        //delegate commit to step implementation (for data storage etc.)
        $result = $step->commit($data);

        if ($result) {
            $env = Factory::getInstance()->getEnvironment();

            $index++;
            if (count($this->checkoutStepOrder) > $index) {
                //setting checkout manager to next step
                $this->currentStep = $this->checkoutStepOrder[$index];

                $env->setCustomItem(self::CURRENT_STEP . "_" . $this->cart->getId(), $this->currentStep->getName());

                //checkout NOT finished
                $this->finished = false;
            } else {
                //checkout is finished
                $this->finished = true;
            }
            $env->setCustomItem(self::FINISHED . "_" . $this->cart->getId(), $this->finished);

            $this->cart->save();
            $env->save();
        }
        return $result;
    }


    /**
     * @return ICart
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @param string $stepname
     * @return ICheckoutStep
     */
    public function getCheckoutStep($stepname)
    {
        return $this->checkoutSteps[$stepname];
    }

    /**
     * @return ICheckoutStep[]
     */
    public function getCheckoutSteps()
    {
        return $this->checkoutStepOrder;
    }

    /**
     * @return ICheckoutStep
     */
    public function getCurrentStep()
    {
        return $this->currentStep;
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        return $this->finished;
    }

    /**
     * @return bool
     */
    public function isCommitted()
    {
        $orderManager = Factory::getInstance()->getOrderManager();
        $order = $orderManager->getOrderFromCart($this->cart);

        return $order && $order->getOrderState() == $order::ORDER_STATE_COMMITTED;
    }


    /**
     * @return IPayment
     */
    public function getPayment()
    {
        return $this->payment;
    }


    /**
     *
     */
    public function cleanUpPendingOrders()
    {
        $this->getCommitOrderProcessor()->cleanUpPendingOrders();
    }
}
