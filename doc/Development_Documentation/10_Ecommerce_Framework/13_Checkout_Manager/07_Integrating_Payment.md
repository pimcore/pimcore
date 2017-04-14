# Payment Integration

To integrate payment into the checkout process, instead of calling ```$manager->commitOrder();``` like described 
in [Committing Orders](./05_Committing_Orders.md), a few more steps are necessary. 


## Initialize Payment in Controller
After all checkout steps are completed, the payment can be started. This is done as follows: 
```php
<?php
$manager = Factory::getInstance()->getCheckoutManager($cart);

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
$config['confirmURL'] = $urlForServersidePaymentConfirmation;
$config['orderDescription'] = 'My order at pimcore.org';
$config['imageURL'] = URL-TO-LOGO-OF-WEBSITE;

// initialize payment - returns a zend form in most cases for view script
$this->view->paymentForm = $payment->initPayment( $cart->getPriceCalculator()->getGrandTotal(), $config );
```

## Build payment view
Once the payment is started, the created payment form needs to be integrated into the view script. Depending on the 
payment provider, also other data structures can be created:
 
```php
<?php
$form = $this->payment;
$container = $this->app->getContainer();
echo $container->get('templating.helper.form')->form($form->getForm()->createView());
```
For more samples see [E-Commerce Demo](https://github.com/pimcore/demo-ecommerce/blob/master/app/Resources/views/Payment/paymentFrame.html.php)


## Handle Payment Response
When the user finishes the payment, the given response (either via redirect or via server side call) has to be handled 
as follows. If payment handling was successful, the order needs to be committed.

A client side handling could look like as follows: 
```php
<?php

    /**
     * got response from payment provider
     */
    public function paymentStatusAction(Request $request)
    {
        // init
        $cart = $this->getCart();
        $checkoutManager = Factory::getInstance()->getCheckoutManager($cart);

        $language = substr($request->getLocale(), 0, 2);

        if($request->get('mode') == "cancel") {

            try {
                $checkoutManager->cancelStartedOrderPayment();
            } catch (\Exception $e) {
                //it seems that payment already canceled due to server side call.
            }

            $this->view->goto = $this->generateUrl('checkout', ["action" => "confirm", "language" => $language, "error" => strip_tags($request->get('mode'))]);
            return;
        }
        if($request->get('mode') == "pending") {
            $this->view->goto = $this->generateUrl('checkout', ["action" => "pending", "language" => $language]);
            return;
        }

        $params = array_merge($request->query->all(), $request->request->all());

        try
        {
            $order = $checkoutManager->handlePaymentResponseAndCommitOrderPayment( $params );

            // optional to clear payment
            // if this call is necessary depends on payment provider and configuration.
            // its possible to execute this later (e.g. when shipment is done)
            $payment = $checkoutManager->getPayment();
            $paymentStatus = $payment->executeDebit();
            $orderAgent = Factory::getInstance()->getOrderManager()->createOrderAgent($order);
            $orderAgent->updatePayment($paymentStatus);

            if($order && $order->getOrderState() == $order::ORDER_STATE_COMMITTED) {
                $this->view->goto = $this->generateUrl('checkout', ["action" => "completed", "language" => $language, "id" => $order->getId()]);
            } else {
                $this->view->goto = $this->generateUrl('checkout', ["action" => "confirm", "language" => $language, "error" => strip_tags($request->get('mode'))]);
            }

        }
        catch(\Exception $e)
        {
            $this->view->goto = $this->generateUrl('checkout', ["action" => "confirm", "language" => $language, "error" => $e->getMessage()]);
            return;
        }

    }

```

A server side handling could look as follows: 
 
```php
<?php

    public function serverSideQPayAction(Request $request) {

        Logger::info("Starting server side call");

        $params = array_merge($request->query->all(), $request->request->all());

        $factory = Factory::getInstance();
        $environment = $factory->getEnvironment();

        //if checkout tenant is set via param, use that one for this request
        if($params['checkouttenant']) {
            $environment->setCurrentCheckoutTenant($params['checkouttenant'], false);
        }

        $commitOrderProcessor = $factory->getCommitOrderProcessor();
        $paymentProvider = $factory->getPaymentManager()->getProvider("qpay");

        if($committedOrder = $commitOrderProcessor->committedOrderWithSamePaymentExists($params, $paymentProvider)) {
            Logger::info("Order with same payment is already committed, doing nothing. OrderId is " . $committedOrder->getId());
        } else {
            $order = $commitOrderProcessor->handlePaymentResponseAndCommitOrderPayment( $params, $paymentProvider );
            Logger::info("Finished server side call. OrderId is " . $order->getId());
        }


        exit("success");
    }

```

For more details see [Payment Docs ](../15_Payment) section. 
