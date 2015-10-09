## 1 - Checkout Manager configuration

The checkout manager is not a out-of-the-box checkout process! 

It is a tool for the developer to create a use case specific checkout process and consists of checkout steps and a commit order processor, which is responsible for completing the order and doing use case specific work. 

The configuration takes place in the OnlineShopConfig.xml
```xml
<!-- general settings for checkout manager -->
<checkoutmanager class="OnlineShop_Framework_Impl_CheckoutManager">
    <config>
        <!-- define different checkout steps which need to be committed before commit of order is possible -->
        <steps>
            <deliveryaddress class="OnlineShop_Framework_Impl_Checkout_DeliveryAddress"/>
            <confirm class="Website_OnlineShop_Checkout_Confirm"/>
        </steps>

        <!-- optional
             -> define payment provider which should be used for payment.
             -> payment providers are defined in payment manager section.
        -->
        <payment provider="qpay" />

        <!-- define used commit order processor -->
        <commitorderprocessor class="Website_OnlineShop_Order_Processor"/>

        <!-- settings for order storage - pimcore class names for oder and order items -->
        <orderstorage orderClass="Object_OnlineShopOrder" orderItemClass="Object_OnlineShopOrderItem"/>

        <!-- parent folder for order objects - either ID or path can be specified. path is parsed by strftime. -->
        <parentorderfolder>/order/%Y/%m/%d</parentorderfolder>

        <!-- settings for confirmation mail sent to customer after order is finished.
             also could be defined defined directly in commit order processor (e.g. when different languages are necessary)
        -->
        <mails confirmation="/en/emails/order-confirmation" />


        <!-- special configuration for specific checkout tenants -->
        <tenants>
            <paypal>
                <payment provider="paypal" />
            </paypal>
            <datatrans>
                <payment provider="datatrans" />
            </datatrans>
            <otherFolder>
                <parentorderfolder>/order_otherfolder/%Y/%m/%d</parentorderfolder>
            </otherFolder>
        </tenants>
    </config>
</checkoutmanager>
```

Following elements are configured: 
* **Implementation of the checkout manager**: The Checkout Manager is a central player of the checkout process. It checks the state of single checkout steps, it responsible for the payment integration and also calls the commit order processor in the end. 
* **Checkout steps and their implementation**: Each checkout step (e.g. Delivery address, delivery date, ...) needs a concrete checkout step implementation. The implementation is responsible for storing and validating the necessary data, is project dependent and has to be implemented for each project. 
* **Implementation of the commit order processor**: When finalization of the order is done by the commit order processor. This is the places, where custom ERP integrations and other project dependent order finishing stuff should be placed. 
* **Additional stuff like**: 
   * Order storage configuration
   * Parent order folder
   * Mail configuration

## 2 - Setting up Checkout Steps
For each checkout step (e.g. delivery address, delivery date, ...) there has to be a concrete checkout step implementation. This implementation is responsible for storage and loading of neccessary checkout data for each step. It needs to extend `OnlineShop_Framework_Impl_Checkout_AbstractStep` and implement `OnlineShop_Framework_ICheckoutStep`. 

Following methods have to be implemented: 
* commit($data): is called when step is finished and data needs to be saved
* getData(): returns saved data for this step
* getName(): returns name of the step


#### Sample implementation of a checkout step:
```php
<?php

/**
 * Class OnlineShop_Framework_Impl_Checkout_DeliveryAddress
 *
 * sample implementation for delivery address
 */
class OnlineShop_Framework_Impl_Checkout_DeliveryAddress extends OnlineShop_Framework_Impl_Checkout_AbstractStep implements OnlineShop_Framework_ICheckoutStep {

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

$manager = $this->of->getCheckoutManager($cart);
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
$manager = $this->of->getCheckoutManager($cart);
$order = $manager->commitOrder();
```
While committing the order, the checkout manager delegates it to the specified commit order processor implementation, which needs to implement `OnlineShop_Framework_ICommitOrderProcessor`. This is the place where all functionality for committing the order (e.g. creating order objects, sending orders to erp systems, sending order confirmation mails, ...) is bundled. 

The default implementation `OnlineShop_Framework_Impl_CommitOrderProcessor` provides basic functionality like creating an order object and sending a order confirmation mail. In simple use cases a website specific implementation needs to extend `OnlineShop_Framework_Impl_CommitOrderProcessor` and overwrite the method `processOrder` where website specific functionality is integrated (adding additional data to the order object, sending orders to erp systems, ...). 

A simple implementation could look like: 
```php
<?php
class OnlineShop_CommitOrderProcessor extends OnlineShop_Framework_Impl_CommitOrderProcessor {
 
   protected function processOrder(OnlineShop_Framework_ICart $cart, Object_OnlineShopOrder $order) {
      if($cart->getCheckoutData(OnlineShop_Framework_Impl_Checkout_DeliveryDate::DATE)) {
         $order->setDeliverydate(new Zend_Date($cart->getCheckoutData(OnlineShop_Framework_Impl_Checkout_DeliveryDate::DATE), Zend_Date::TIMESTAMP));
      }
      $order->setDeliveryinstantly($cart->getCheckoutData(OnlineShop_Framework_Impl_Checkout_DeliveryDate::INSTANTLY));
      $order->setDeliveryAddressLine1($cart->getCheckoutData(OnlineShop_Framework_Impl_Checkout_DeliveryAddress::LINE1));
      $order->setCustomerOrderData($cart->getCheckoutData("customerOrderData"));
      $order->setCustomerOrderNumber($cart->getCheckoutData("customerOrderNumber"));
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
$manager = $this->of->getCheckoutManager($cart);

// start order payment
$paymentInformation= $manager->startOrderPayment();

//get payment instance
$payment = $manager->getPayment();

//configure payment - this depends on the payment provider
// sample payment config - wirecard
$config['language'] = Zend_Registry::get("Zend_Locale")->getLanguage();
$config['successURL'] = $url . 'success';
$config['cancelURL'] = $url . 'cancel';
$config['failureURL'] = $url . 'failure';
$config['serviceURL'] = $url . 'service';
$config['orderDescription'] = 'My order at pimcore.org';
$config['imageURL'] = URL-TO-LOGO-OF-WEBSITE;

// initialize payment - returns a zend form in most cases for view script
$this->view->paymentForm = $payment->initPayment( $cart->getPriceCalculator()->getGrandTotal(), $config );
```

#### Build payment view
One the payment is started, the created payment form need to be integrated into the view script. Depending on the payment provider, also other data structures an be created: 
```php
<?php
$form = $this->payment
echo $form;
```

#### Handle payment response
When the user finishes the payment, the given response (either via redirect or via server side call) has to be handled as follows. If payment handling was successful, the order needs to be committed. 
```php
<?php
$manager = $this->of->getCheckoutManager($cart);
$payment = $manager->getPayment();

// check if payment is authorised
$payment->handleResponse( $params );

// optional to clear payment
// if this call is necessary depends on payment provider and configuration.
// its possible to execute this later (e.g. when shipment is done)
$paymentStatus = $payment->executeDebit();


//commit order
$order = $checkout->commitOrderPayment($paymentStatus);

```
For more details see [Usage of payment manager](Usage-of-payment-manager)

## 5 - Checkout tenants for checkout
The e-commerce framework has the concept of checkout tenants which allow different cart manager and checkout manager configurations based on a currently active checkout tenant. 
The current checkout tenant is set in the framework environment as follows. Once set, the cart manager uses all specific settings of the currently active checkout tenant. 

So different checkout steps, different payment providers etc. can be implemented within one shop. 

```php
<?php
$environment = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
$environment->setCurrentCheckoutTenant('default');
$environment->save();

$environment->setCurrentCheckoutTenant('noShipping');
$environment->save();
```

