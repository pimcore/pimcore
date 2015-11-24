<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


/**
 * Class OnlineShop_Framework_Impl_CheckoutManager
 */
class OnlineShop_Framework_Impl_CheckoutManager implements OnlineShop_Framework_ICheckoutManager
{

    /**
     * constants for custom environment item names for persisting state of checkout
     * always concatenated with current cart id
     */
    const CURRENT_STEP = "checkout_current_step";
    const FINISHED = "checkout_finished";
    const CART_READONLY_PREFIX = "checkout_cart_readonly";
    const COMMITTED = "checkout_committed";
    const TRACK_ECOMMERCE = "checkout_trackecommerce";
    const TRACK_ECOMMERCE_UNIVERSAL = "checkout_trackecommerce_universal";

    /**
     * needed for effective access to one specific checkout step
     *
     * @var OnlineShop_Framework_ICheckoutStep[]
     */
    protected $checkoutSteps;

    /**
     * needed for preserving order of checkout steps
     *
     * @var OnlineShop_Framework_ICheckoutStep[]
     */
    protected $checkoutStepOrder;

    /**
     * @var OnlineShop_Framework_ICheckoutStep
     */
    protected $currentStep;

    /**
     * @var bool
     */
    protected $finished = false;

    /**
     * @var bool
     */
    protected $committed = false;

    /**
     * @var bool
     */
    protected $paid = true;

    /**
     * @var string
     */
    protected $confirmationMail;

    /**
     * @var OnlineShop_Framework_ICommitOrderProcessor
     */
    protected $commitOrderProcessor;

    /**
     * @var string
     */
    protected $commitOrderProcessorClassname;

    /**
     * @var OnlineShop_Framework_ICart
     */
    protected $cart;

    /**
     * Payment Provider
     *
     * @var OnlineShop_Framework_IPayment
     */
    protected $payment;


    /**
     * @param OnlineShop_Framework_ICart $cart
     * @param                            $config
     */
    public function __construct(OnlineShop_Framework_ICart $cart, $config)
    {
        $this->cart = $cart;

        $config = new OnlineShop_Framework_Config_HelperContainer($config, "checkoutmanager");

        $this->commitOrderProcessorClassname = $config->commitorderprocessor->class;
        $this->confirmationMail = (string)$config->mails->confirmation;
        foreach ($config->steps as $step) {
            $step = new $step->class($this->cart);
            $this->checkoutStepOrder[] = $step;
            $this->checkoutSteps[$step->getName()] = $step;
        }


        //getting state information for checkout from custom environment items
        $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
        $this->finished = $env->getCustomItem(self::FINISHED . "_" . $this->cart->getId());
        $this->committed = $env->getCustomItem(self::COMMITTED . "_" . $this->cart->getId());
        $this->currentStep = $this->checkoutSteps[$env->getCustomItem(self::CURRENT_STEP . "_" . $this->cart->getId())];

        //if no step is set and cart is not finished -> set current step to first step of checkout
        if (empty($this->currentStep) && !$this->isFinished()) {
            $this->currentStep = $this->checkoutStepOrder[0];
        }

        // init payment provider
        if ($config->payment) {
            $this->payment = OnlineShop_Framework_Factory::getInstance()->getPaymentManager()->getProvider($config->payment->provider);
        }

    }

    /**
     * creates, configures and returns commit order processor
     *
     * @return OnlineShop_Framework_ICommitOrderProcessor
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
     * @return OnlineShop_Framework_AbstractPaymentInformation
     * @throws Exception
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function startOrderPayment()
    {
        if ($this->committed) {
            throw new OnlineShop_Framework_Exception_UnsupportedException("Cart already committed.");
        }

        if (!$this->isFinished()) {
            throw new OnlineShop_Framework_Exception_UnsupportedException("Checkout not finished yet.");
        }

        if (!$this->payment) {
            throw new OnlineShop_Framework_Exception_UnsupportedException("Payment is not activated");
        }

        //Create Order and PaymentInformation
        $orderManager = \OnlineShop_Framework_Factory::getInstance()->getOrderManager();
        $order = $orderManager->getOrCreateOrderFromCart($this->cart);

        if ($order->getOrderState() == OnlineShop_Framework_AbstractOrder::ORDER_STATE_COMMITTED) {
            throw new OnlineShop_Framework_Exception_UnsupportedException("Order already committed");
        }

        $paymentInfo = $this->getCommitOrderProcessor()->getOrCreateActivePaymentInfo($order);

        //make cart read only -> cart is now in payment mode (cart must not be changed during active payment)
        $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
        $env->setCustomItem(self::CART_READONLY_PREFIX . "_" . $this->cart->getId(), "READONLY");
        $env->save();

        return $paymentInfo;
    }

    /**
     * @return OnlineShop_Framework_AbstractOrder
     */
    public function getOrder()
    {
        $orderManager = \OnlineShop_Framework_Factory::getInstance()->getOrderManager();
        return $orderManager->getOrCreateOrderFromCart($this->cart);
    }


    /**
     * @param OnlineShop_Framework_Payment_IStatus $status
     * @return OnlineShop_Framework_AbstractOrder
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function commitOrderPayment(OnlineShop_Framework_Payment_IStatus $status)
    {
        if (!$this->payment) {
            throw new OnlineShop_Framework_Exception_UnsupportedException("Payment is not activated");
        }

        //update order payment -> e.g. setting all available payment information to order object
        $order = $this->getCommitOrderProcessor()->updateOrderPayment($status);

        //remove read only state for cart since payment process is finished (with or without success)
        $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
        $env->removeCustomItem(self::CART_READONLY_PREFIX . "_" . $this->cart->getId());
        $env->save();


        if (in_array($status->getStatus(), [OnlineShop_Framework_AbstractOrder::ORDER_STATE_COMMITTED, OnlineShop_Framework_AbstractOrder::ORDER_STATE_PAYMENT_AUTHORIZED])) {

            //only when payment state is committed or authorized -> proceed and commit order
            $order = $this->commitOrder();
        } else {

            //if payment not successful -> set current checkout step to last step and checkout to not finished
            //last step must be committed again in order to restart payment or e.g. commit without payment?
            $this->currentStep = $this->checkoutStepOrder[count($this->checkoutStepOrder) - 1];
            $this->finished = false;

            $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
            $env->setCustomItem(self::CURRENT_STEP . "_" . $this->cart->getId(), $this->currentStep->getName());
            $env->setCustomItem(self::FINISHED . "_" . $this->cart->getId(), $this->finished);
            $env->save();
        }


        return $order;
    }

    /**
     * @return OnlineShop_Framework_AbstractOrder
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function commitOrder()
    {
        if ($this->committed) {
            throw new OnlineShop_Framework_Exception_UnsupportedException("Cart already committed.");
        }

        if (!$this->isFinished()) {
            throw new OnlineShop_Framework_Exception_UnsupportedException("Checkout not finished yet.");
        }

        //delegate commit order to commit order processor
        $result = $this->getCommitOrderProcessor()->commitOrder($this->cart);

        $this->committed = true;

        //updating environment with checkout state
        $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
        $env->removeCustomItem(self::CURRENT_STEP . "_" . $this->cart->getId());
        $env->removeCustomItem(self::FINISHED . "_" . $this->cart->getId());
        $env->removeCustomItem(self::COMMITTED . "_" . $this->cart->getId());

        //setting e-commerce tracking information to environment for later use in view
        $env->setCustomItem(self::TRACK_ECOMMERCE . "_" . $result->getOrdernumber(), $this->generateGaEcommerceCode($result));
        $env->setCustomItem(self::TRACK_ECOMMERCE_UNIVERSAL . "_" . $result->getOrdernumber(), $this->generateUniversalEcommerceCode($result));

        $env->save();

        return $result;
    }

    /**
     * generates classic google analytics e-commerce tracking code
     *
     * @param OnlineShop_Framework_AbstractOrder $order
     * @return string
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    protected function generateGaEcommerceCode(OnlineShop_Framework_AbstractOrder $order)
    {
        $code = "";

        $shipping = 0;
        $modifications = $order->getPriceModifications();
        foreach ($modifications as $modification) {
            if ($modification->getName() == "shipping") {
                $shipping = $modification->getAmount();
                break;
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
     * @param OnlineShop_Framework_AbstractOrder $order
     * @return string
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    protected function generateUniversalEcommerceCode(OnlineShop_Framework_AbstractOrder $order)
    {
        $code = "ga('require', 'ecommerce', 'ecommerce.js');\n";


        $shipping = 0;
        $modifications = $order->getPriceModifications();
        foreach ($modifications as $modification) {
            if ($modification->getName() == "shipping") {
                $shipping = $modification->getAmount();
                break;
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
     * @param OnlineShop_Framework_ICheckoutStep $step
     * @param mixed $data
     * @return bool
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function commitStep(OnlineShop_Framework_ICheckoutStep $step, $data)
    {
        //get index of current step and index of step to commit
        $indexCurrentStep = array_search($this->currentStep, $this->checkoutStepOrder);
        $index = array_search($step, $this->checkoutStepOrder);

        // if indexCurrentStep is < index -> there are uncommitted previous steps
        if ($indexCurrentStep < $index) {
            throw new OnlineShop_Framework_Exception_UnsupportedException("There are uncommitted previous steps.");
        }

        //delegate commit to step implementation (for data storage etc.)
        $result = $step->commit($data);

        if ($result) {
            $env = OnlineShop_Framework_Factory::getInstance()->getEnvironment();

            $index++;
            if (count($this->checkoutStepOrder) > $index) {
                //setting checkout manager to next step
                $this->currentStep = $this->checkoutStepOrder[$index];
                $this->finished = false;

                $env->setCustomItem(self::CURRENT_STEP . "_" . $this->cart->getId(), $this->currentStep->getName());
            } else {
                //checkout is finished
                $this->finished = true;
            }

            $env->setCustomItem(self::FINISHED . "_" . $this->cart->getId(), $this->finished);
            $env->setCustomItem(self::COMMITTED . "_" . $this->cart->getId(), $this->committed);

            $this->cart->save();
            $env->save();
        }
        return $result;
    }


    /**
     * @return OnlineShop_Framework_ICart
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @param string $stepname
     * @return OnlineShop_Framework_ICheckoutStep
     */
    public function getCheckoutStep($stepname)
    {
        return $this->checkoutSteps[$stepname];
    }

    /**
     * @return OnlineShop_Framework_ICheckoutStep[]
     */
    public function getCheckoutSteps()
    {
        return $this->checkoutStepOrder;
    }

    /**
     * @return OnlineShop_Framework_ICheckoutStep
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
        return $this->committed;
    }


    /**
     * @return OnlineShop_Framework_IPayment
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
