# PayPal

## Integration of PayPal Smart Payment Button

The `PayPalSmartPaymentButton` provides an integration with the PayPal Smart Payment Buttons.  

### Integration Steps

1) Update your `composer.json`

Add `paypal/paypal-checkout-sdk:^1` to your `composer.json`. 

2) Setup API credentials and Pimcore E-Commerce Framework Configuration

Setup payment provider in e-commerce framework configuration tree and add PayPal API 
credentials to it: 
```yml
    payment_manager.
        providers: 
            paypal:
                provider_id: Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\PayPalSmartPaymentButton
                profile: sandbox
                profiles:
                    sandbox:
                        client_id: <YOUR PAYPAL REST API CLIENT ID>
                        client_secret: <YOUR PAYPAL REST API CLIENT SECRET>
```

For configuration details see further below. For additional information of PayPal API 
credentials see [API Docs](https://developer.paypal.com/docs/api/overview/) 


3) Integrate the PayPal payment button to your view template

Integrate PayPal payment button and overwrite a few methods like in the sample. At least
`createOrder` and `onApprove` need to be overwritten.  

```php

    <?php
        /** @var \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\PayPalSmartPaymentButton $payment */
     ?>
    <script src="<?= $payment->buildPaymentSDKLink() ?>">
    </script>

    <div id="paypal-button-container"></div>
    <script>
        paypal.Buttons({
            onCancel: function (data) {
                // e.g. redirect to a certain page or show message
                window.location.replace('...');
            },
            createOrder: function() {
                return fetch('/path/to/your/startPaymentAction', {
                    method: 'post',
                    headers: {
                        'content-type': 'application/json'
                    }
                }).then(function(res) {
                    return res.json();
                }).then(function(data) {
                    return data.id;
                });
            },
            onApprove: function(data) {
                
                // make sure you deliver orderID, payerID and paymentID to your 
                // handle response controller action, e.g. by creating a form and 
                // posting the data
                var form = document.createElement('form');
                document.body.appendChild(form);
                form.method = 'POST';
                form.action = '/path/to/your/handleResponseAction';

                var orderID = document.createElement('input');
                orderID.type = 'hidden';
                orderID.name = 'orderID';
                orderID.value = data['orderID'];
                form.appendChild(orderID);

                var payerID = document.createElement('input');
                payerID.type = 'hidden';
                payerID.name = 'payerID';
                payerID.value = data['payerID'];
                form.appendChild(payerID);

                var paymentID = document.createElement('input');
                paymentID.type = 'hidden';
                paymentID.name = 'paymentID';
                paymentID.value = data['paymentID'];
                form.appendChild(paymentID);

                form.submit();
            }
        }).render('#paypal-button-container');
    </script>


```

4) Create a startPaymentAction in your controller

Initialize checkout manager, call `startOrderPayment` and then `initPayment` of the payment 
implementation. It is creating an order at PayPal and its response is the default PayPal 
response, which need to be returned as a json response of the action.  

```php

//in your payment controller, e.g. startPaymentAction

public function startPaymentAction() {
    
    // ... some other stuff
    
    $checkoutManager = Factory::getInstance()->getCheckoutManager($cart);
    $paymentInformation = $checkoutManager->startOrderPayment();
    $payment = $checkoutManager->getPayment();
    
    $config = [
        'return_url' => $returnUrl,
        'cancel_url' => $cancelUrl . 'payment?error=cancel',
        'OrderDescription' => 'My Order ' . $order->getOrdernumber() . ' at pimcore.org',
        'InternalPaymentId' => $paymentInformation->getInternalPaymentId()
    ];
    
    $response = $payment->initPayment($cart->getPriceCalculator()->getGrandTotal(), $config);
    return new \Symfony\Component\HttpFoundation\JsonResponse($response);

} 

```

5) Handle Response of PayPal

In handle response just call `handlePaymentResponseAndCommitOrderPayment` of checkout manager.
It does the rest - which is checking at PayPal if payment was authorized by the payer and 
committing the order.

Depending on your settings (see below), the payment is also automatically captured. If not
you need to capture the payment manually by calling `$payment->executeDebit()`.  

```php

public function handleResponseAction() {

    // ... do some stuff 
    
    $checkoutManager = Factory::getInstance()->getCheckoutManager($cart);
    $params = array_merge($request->query->all(), $request->request->all());

    $order = $checkoutManager->handlePaymentResponseAndCommitOrderPayment($params);
    
    // optional to clear payment
    // if this call is necessary depends on payment provider and configuration.
    // its possible to execute this later (e.g. when shipment is done)
//  $payment = $checkoutManager->getPayment();
//  $paymentStatus = $payment->executeDebit();
//  $orderAgent = Factory::getInstance()->getOrderManager()->createOrderAgent($order);
//  $orderAgent->updatePayment($paymentStatus);    
    
    // ... check order state and redirect user to error page or order success page
    
} 
```

6) (Optional) Add `PaymentProviderPayPalSmartButton` ObjectBrick
If you are updating an existing Pimcore instance (with originated before 6.0.1), you might need to add the 
`PaymentProviderPayPalSmartButton` to your installation. It can be imported from 
[this json export file](https://github.com/pimcore/pimcore/tree/master/bundles/EcommerceFrameworkBundle/Resources/install/objectbrick_sources/objectbrick_PaymentProviderPayPalSmartButton_export.json). 


### Configuration Options
In payment configuration, following options are available: 

```yml
    payment_manager.
        providers: 
            paypal:
                provider_id: Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\PayPalSmartPaymentButton
                profile: sandbox
                profiles:
                    sandbox:
                        client_id: <YOUR PAYPAL REST API CLIENT ID>
                        client_secret: <YOUR PAYPAL REST API CLIENT SECRET>
                        
                        # defines, if payment caputure should take place automatic or manual, default is automatic
                        capture_strategy: automatic   
                        
                        # defines mode of PayPal API, default value is sandbox  
                        mode: sandbox                 
                        
                        # defines PayPal application context for shipping, default value is NO_SHIPPING
                        # see https://developer.paypal.com/docs/api/orders/v2/#definition-application_context 
                        shipping_preference: NO_SHIPPING

                        # defines PayPal application context for user action, default value is PAY_NOW
                        # see https://developer.paypal.com/docs/api/orders/v2/#definition-application_context                        
                        user_action: PAY_NOW

                    live:
                        client_id: <YOUR PAYPAL REST API CLIENT ID>
                        client_secret: <YOUR PAYPAL REST API CLIENT SECRET>
                        mode: live

```



### Further Information

For further information and/or more custom integrations, also have a look at following resources:
* [PayPal Developer Documentation](https://developer.paypal.com/docs/checkout/)
* [PayPal Rest APIs](https://developer.paypal.com/docs/api/overview/)
* [PayPal PHP SDK](https://github.com/paypal/PayPal-PHP-SDK)
 
