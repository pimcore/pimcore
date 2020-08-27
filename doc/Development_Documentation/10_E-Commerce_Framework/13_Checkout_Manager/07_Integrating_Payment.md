# Payment Integration

To integrate payment into the checkout process, instead of calling `$manager->commitOrder();` like described 
in [Committing Orders](./05_Committing_Orders.md), a few more steps are necessary. 


## Initialize Payment in Controller
After all checkout steps are completed, the payment can be started. This is done as follows: 

```php
<?php
/**
 * @Route("/checkout-init-payment", name="shop-checkout-init-payment")
 */
public function initPaymentAction(Request $request, Factory $factory) {
    
    // ... do some stuff, and get $cart
 
    /** @var CheckoutManagerInterface $checkoutManager */
    $checkoutManager = $factory->getCheckoutManager($cart);
 
    //optional - init payment and get Pimcore internal payment ID (e.g. if needed for config of payment provider)
    $paymentInformation = $checkoutManager->initOrderPayment();
    $config = new DatatransRequest([
        //all options needed for payment provider - you also can use setters of the corresponding object
    ]);
    
 
    // start payment
    $startPaymentResponse = $checkoutManager->startOrderPaymentWithPaymentProvider($config);
 
    // depending on response type handle start payment response - e.g. render form, render snippet, etc.
    $paymentForm = $startPaymentResponse->getForm();
    $this->view->form = $paymentForm->getForm()->createView();
}
```

## Build payment view
Once the payment is started, the created payment form needs to be integrated into the view script. Depending on the 
payment provider, also other data structures can be created:
 
```twig
<p>{{ 'Starting Payment' }}</p>
{{ form(form) }}
```

For more samples see [E-Commerce Demo](https://github.com/pimcore/demo-ecommerce/blob/master/app/Resources/views/Payment/paymentFrame.html.php)


## Handle Payment Response
When the user finishes the payment, the given response (either via redirect or via server side call) has to be handled 
as follows. If payment handling was successful, the order needs to be committed.

A client side handling could look like as follows: 

```php
<?php
/**
 * @Route("/checkout-payment-response", name="shop-checkout-payment-response")
 */
public function paymentResponseAction(Request $request, Factory $factory, SessionInterface $session) {
     
    // ... do some stuff, and get $cart
     
    $checkoutManager = $factory->getCheckoutManager($cart);
 
    $params = []; // ... get all necessary parameters for payment provider, e.g. by array_merge($request->query->all(), $request->request->all());
 
    try {
        $order = $checkoutManager->handlePaymentResponseAndCommitOrderPayment($params);
 
        // optional to clear payment
        // if this call is necessary depends on payment provider and configuration.
        // its possible to execute this later (e.g. when shipment is done)
        // $payment = $checkoutManager->getPayment();
        // $paymentStatus = $payment->executeDebit();
        // $orderAgent = Factory::getInstance()->getOrderManager()->createOrderAgent($order);
        // $orderAgent->updatePayment($paymentStatus);
 
        $session->set("last_order_id", $order->getId());
        $this->view->goto = $this->generateUrl('shop-checkout-completed');
         
    } catch (\Exception $e) {
 
        $this->addFlash('danger', $e->getMessage());
        $this->view->goto = $this->generateUrl('shop-checkout-address');
 
    }

```

A server side handling could look as follows: 
 
```php
<?php

    public function serverSideQPayAction(Request $request, Factory $factory) {

        Logger::info("Starting server side call");

        $params = array_merge($request->query->all(), $request->request->all());

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

## Dealing with Pending Payments (Starting with Pimcore 6.1)

Depending on the shops user journey, it might be possible, that a user starts multiple payments. Typical use cases for
that can be: 
* User starts payment → user goes back to shop and changes cart → user starts checkout and payment with modified cart again
* User has multiple tabs open → user starts payment in first tab → user starts another payment in second tab → user finishes first payment → user finishes second payment

The ecommerce application needs a strategy how to deal with that. Prior to Pimcore 6.1, Pimcore set the cart to read only 
as soon as a payment was started and did not allow any cart changes. Starting with Pimcore 6.1 and using the V7 checkout
manager architecture, carts can be configured not to become readonly any more and the checkout manager can be configured
with one of the following strategies how to handle pending payments when new payment is started 
(with `handle_pending_payments_strategy` factory option):  

  * **RecreateOrder**: Create new order every time a payment is started and leave old orders untouched. 
  
```yml
factory_options:
    class: Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\CheckoutManager
    handle_pending_payments_strategy: Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\HandlePendingPayments\AlwaysRecreateOrderStrategy
```
  
  * **CancelPaymentOrRecreateOrder** (default value): Cancel payments if possible and cart has not changed, create new order when cart has changed.

```yml
factory_options:
    class: Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\CheckoutManager
    handle_pending_payments_strategy: Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\HandlePendingPayments\CancelPaymentOrRecreateOrderStrategy
```  

  * **ThrowException**: Throw exceptions to make handling of these cases in controller possible. 

```yml
factory_options:
    class: Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\CheckoutManager
    handle_pending_payments_strategy: Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\V7\HandlePendingPayments\ThrowExceptionStrategy
```

Above mentioned use cases will now result in following behavior: 

* User starts payment → user goes back to shop and changes cart → user starts checkout and payment with modified cart again:
  * Cart can be changed despite pending payments.
  * When user restarts payment, new order is created for new payment (with new cart content):
    * If user finishes the first payment, first order is finished → all post order processes are done based on first order.
    * If user finishes second payment, second order is finished. 
    * If user finishes both payments, user has two orders. 
* User has multiple tabs open → user starts payment in first tab → user starts another payment in second tab → user finishes first payment → user finishes second payment: 
  * Depending on configured CancelPaymentOrRecreateOrderStrategy when user starts second payment following things can happen: 
    * RecreateOrder: Another order Is created and user has two orders when he finishes both payments. 
    * CancelPaymentOrRecreateOrder: First payment would be cancelled, user only has one order. When user finishes both payments, both payment information entries will be in one order. 
    * ThrowException: When starting second payment an exception will be thrown and controller needs to decide what to do. 

It is possible to implement custom strategies as they are just services implementing `HandlePendingPaymentsStrategyInterface`. 


For more details see [Payment Docs ](../15_Payment) section. 
