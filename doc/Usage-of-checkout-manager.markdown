## 1 - Checkout Manager configuration

The checkout manager is not an out-of-the-box checkout process! 

It is a tool for the developer to create a use case specific checkout process and consists of checkout steps and a commit order processor, which is responsible for completing the order and doing use case specific work. 

The configuration takes place in the OnlineShopConfig.php
```php
/* general settings for checkout manager */
        "checkoutmanager" => [
            "class" => "\\OnlineShop\\Framework\\CheckoutManager\\CheckoutManager",
            "config" => [
                /* define different checkout steps which need to be committed before commit of order is possible */
                "steps" => [
                    "deliveryaddress" => [
                        "class" => "\\OnlineShop\\Framework\\CheckoutManager\\DeliveryAddress"
                    ],
                    "confirm" => [
                        "class" => "Website_OnlineShop_Checkout_Confirm"
                    ]
                ],
                /* optional
                     -> define payment provider which should be used for payment.
                     -> payment providers are defined in payment manager section. */
                "payment" => [
                    "provider" => "qpay"
                ],
                /* define used commit order processor */
                "commitorderprocessor" => [
                    "class" => "Website_OnlineShop_Order_Processor"
                ],
                /* settings for confirmation mail sent to customer after order is finished.
                     also could be defined defined directly in commit order processor (e.g. when different languages are necessary)
                 */
                "mails" => [
                    "confirmation" => "/en/emails/order-confirmation"
                ],
                /* special configuration for specific checkout tenants */
                "tenants" => [
                    "paypal" => [
                        "payment" => [
                            "provider" => "paypal"
                        ]
                    ],
                    "datatrans" => [
                        "payment" => [
                            "provider" => "datatrans"
                        ]
                    ]
                ]
            ]
        ],
```

> For older Versions check [OnlineShopConfig_sample.xml](/config/OnlineShopConfig_sample.xml)

Following elements are configured: 
* **Implementation of the checkout manager**: The Checkout Manager is a central player of the checkout process. It checks the state of single checkout steps, is responsible for the payment integration and also calls the commit order processor in the end. 
* **Checkout steps and their implementation**: Each checkout step (e.g. Delivery address, delivery date, ...) needs a concrete checkout step implementation. The implementation is responsible for storing and validating the necessary data, is project dependent and has to be implemented for each project. 
* **Implementation of the commit order processor**: When finalization of the order is done by the commit order processor. This is the places, where custom ERP integrations and other project dependent order finishing stuff should be placed. 
* **Additional stuff like**: 
   * Mail configuration

## 2 - Setting up Checkout Steps
For each checkout step (e.g. delivery address, delivery date, ...) there has to be a concrete checkout step implementation. This implementation is responsible for storage and loading of necessary checkout data for each step. It needs to extend `\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\AbstractStep` and implement `\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\ICheckoutStep`.

Following methods have to be implemented: 
* commit($data): is called when step is finished and data needs to be saved
* getData(): returns saved data for this step
* getName(): returns name of the step


#### Sample implementation of a checkout step:
```php
<?php

namespace OnlineShop\Framework\CheckoutManager;

/**
 * Class \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\DeliveryAddress
 *
 * sample implementation for delivery address
 */
class DeliveryAddress extends AbstractStep implements ICheckoutStep {

    /**
     * namespace key
     */
    const PRIVATE_NAMESPACE = 'delivery_address';


    /**
     * @return string
     */
    public function getName() {
        return "deliveryaddress";
    }

    /**
     * sets delivered data and commits step
     *
     * @param  $data
     * @return bool
     */
    public function commit($data) {
        $this->cart->setCheckoutData(self::PRIVATE_NAMESPACE, json_encode($data));
        return true;
    }

    /**
     * returns saved data of step
     *
     * @return mixed
     */
    public function getData() {
        $data = json_decode($this->cart->getCheckoutData(self::PRIVATE_NAMESPACE));
        return $data;
    }
}
```

#### Working with steps: 
```php
<?php

$manager = \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory::getInstance()->getCheckoutManager($cart);
$step = $manager->getCheckoutStep("deliveryaddress");
$address = new stdClass();
//fill address
$manager->commitStep($step, $address);
 
$step = $manager->getCheckoutStep("deliverydate");
$manager->commitStep($step, $data);
$cart->save();
```


## 3 - Commit Order and Commit Order Processor
After each checkout step is completed, the order can be committed. If no payment is needed, this is done as follows: 
```php
<?php
$manager = \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory::getInstance()->getCheckoutManager($cart);
$order = $manager->commitOrder();
```
While committing the order, the checkout manager delegates it to the specified commit order processor implementation, which needs to implement `\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\ICommitOrderProcessor`.
This is the place where all functionality for committing the order (e.g. sending orders to erp systems, sending order confirmation mails, ...) is bundled. 

The default implementation `\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessor` provides basic functionality like creating an order object and sending an order confirmation mail.
 Order creation it self is delegated to the `\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\IOrderManager`.
In simple use cases a website specific implementation needs 

* to extend `\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\OrderManager` and overwrite the method `applyCustomCheckoutDataToOrder` to add additional fields to the order object and
* to extend `\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessor` and overwrite the method `processOrder` where website specific functionality is integrated (sending orders to erp systems, ...).

If additional information needs to be stored into the order, the OrderManager has to be 
 extended. For more Information 
 
A simple implementation of `Website_OnlineShop_Order_OrderManager` could look like:

```php
<?php
class Website_OnlineShop_Order_OrderManager extends \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OrderManager\OrderManager {

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart $cart
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder $order
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder
     * @throws \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException
     */
    public function applyCustomCheckoutDataToOrder(OnlineShop\Framework\CartManager\ICart $cart, \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder $order)
    {
        $order = parent::applyCustomCheckoutDataToOrder($cart, $order);

        /* @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractOrder $order*/

        $checkout = \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory::getInstance()->getCheckoutManager( $cart );
        $deliveryAddress = $checkout->getCheckoutStep('deliveryaddress')->getData();
        /* @var Website_OnlineShop_Order_DeliveryAddress $deliveryAddress */

        // insert delivery address
        $order->setCustomerName( $deliveryAddress->firstname . ' ' . $deliveryAddress->lastname );
        $order->setCustomerCompany( $deliveryAddress->company );
        $order->setCustomerStreet( $deliveryAddress->address );
        $order->setCustomerZip( $deliveryAddress->zip );
        $order->setCustomerCity( $deliveryAddress->city );
        $order->setCustomerCountry( $deliveryAddress->country );
        $order->setCustomerEmail( $deliveryAddress->email );
        return $order;
    }
}
```


A simple implementation of `Website_OnlineShop_Order_Processor` could look like: 

```php
<?php
class OnlineShop_CommitOrderProcessor extends \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessor {
 
   protected function processOrder(Object_OnlineShopOrder $order) {
      //send order to ERP-System
      try {
          $connector = Website_ERPConnector::getInstance();
          $erpOrderNumber = $connector->sendOrder($order);
          $order->setOrderNumber($erpOrderNumber);
      } catch(Exception $e) {
          Logger::error($e->getMessage()); 
          throw $e;
      }
   }
}
```
 
If needed, further methods can be overwritten. E.g. `sendConfirmationMail` if special e-mails should be sent to specific persons.
After commit order was successful, the user can be directed to a success-page. 


## 4 - Integrate Payment
To integrate payment into the checkout process instead of calling ```$manager->commitOrder();``` like described above a few more steps are necessary. 


#### Initialize payment in controller
After each checkout step is completed, the payment can be started. This is done as follows: 
```php
<?php
$manager = \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory::getInstance()->getCheckoutManager($cart);

// start order payment
$paymentInformation= $manager->startOrderPayment();

//get payment instance
$payment = $manager->getPayment();

//configure payment - this depends on the payment provider
// sample payment config - wirecard
$config['language'] = 'en';
$config['successURL'] = $url . 'success';
$config['cancelURL'] = $url . 'cancel';
$config['failureURL'] = $url . 'failure';
$config['serviceURL'] = $url . 'service';
$config['confirmURL'] = $urlForServersidePaymentConfirmation;
$config['orderDescription'] = 'My order at pimcore.org';
$config['imageURL'] = URL-TO-LOGO-OF-WEBSITE;

// initialize payment - returns a zend form in most cases for view script
$this->view->paymentForm = $payment->initPayment( $cart->getPriceCalculator()->getGrandTotal(), $config );
```

#### Build payment view
Once the payment is started, the created payment form needs to be integrated into the view script. Depending on the payment provider, also other data structures can be created: 
```php
<?php
$form = $this->payment
echo $form;
```

#### Handle payment response
When the user finishes the payment, the given response (either via redirect or via server side call) has to be handled as follows. If payment handling was successful, the order needs to be committed.

A client side handling could look like as follows: 
```php
<?php

    /**
     * got response from payment provider
     */
    public function paymentStatusAction()
    {
        // init
        $cart = $this->getCart();
        $checkoutManager = \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory::getInstance()->getCheckoutManager( $cart );

        if($this->getParam('mode') == "cancel") {
            $checkoutManager->cancelStartedOrderPayment();
            $this->view->goto = '/en/checkout/confirm?error=' . $this->getParam('mode');
            return;
        }

        $params = $this->getAllParams();

        try
        {
            $order = $checkoutManager->handlePaymentResponseAndCommitOrderPayment( $params );

            // optional to clear payment
            // if this call is necessary depends on payment provider and configuration.
            // its possible to execute this later (e.g. when shipment is done) - which is preferred
            $payment = $checkoutManager->getPayment();
            $paymentStatus = $payment->executeDebit();
            $orderAgent = \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory::getInstance()->getOrderManager()->createOrderAgent($order);
            $orderAgent->updatePayment($paymentStatus);

            if($order && $order->getOrderState() == $order::ORDER_STATE_COMMITTED) {
                $this->view->goto = '/en/checkout/completed?id=' . $order->getId();
            } else {
                $this->view->goto = '/en/checkout/confirm?error=' . $this->getParam('mode');
            }
            
        }
        catch(Exception $e)
        {
            $this->view->goto = '/en/checkout/confirm?error=' . $e->getMessage();
            return;
        }
        
    }

```

A server side handling could look as follows: 
 
```php
<?php

    public function serverSideQPayAction() {

        Logger::info("Starting server side call");

        $params = $this->getAllParams();

        $commitOrderProcessor = \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory::getInstance()->getCommitOrderProcessor();
        $paymentProvider = \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory::getInstance()->getPaymentManager()->getProvider("qpay");

        if($committedOrder = $commitOrderProcessor->committedOrderWithSamePaymentExists($params, $paymentProvider)) {
            Logger::info("Order with same payment is already committed, doing nothing. OrderId is " . $committedOrder->getId());
        } else {
            $order = $commitOrderProcessor->handlePaymentResponseAndCommitOrderPayment( $params, $paymentProvider );

            Logger::info("Finished server side call. OrderId is " . $order->getId());
        }

        exit("success");
    }

```

For more details see [Usage of payment manager](Usage-of-payment-manager.markdown)


## 5 - Checkout tenants for checkout
The e-commerce framework has the concept of checkout tenants which allow different cart manager and checkout manager configurations based on a currently active checkout tenant. 
The current checkout tenant is set in the framework environment as follows. Once set, the cart manager uses all specific settings of the currently active checkout tenant. 

So different checkout steps, different payment providers etc. can be implemented within one shop. 

```php
<?php
$environment = \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Factory::getInstance()->getEnvironment();
$environment->setCurrentCheckoutTenant('default');
$environment->save();

$environment->setCurrentCheckoutTenant('noShipping');
$environment->save();
```

> When using server-by-server payment confirmation communication, make sure that the correct tenant is set during the response handling! 
