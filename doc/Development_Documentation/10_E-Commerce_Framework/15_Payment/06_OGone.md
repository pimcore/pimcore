# OGone

* [Documentation](https://payment-services.ingenico.com/int/en/ogone/support/guides/integration%20guides/e-commerce/introduction)
* [Sandbox](https://payment-services.ingenico.com/int/en/ogone/support/guides/user%20guides/test-account-creation) / on request


## Configuration

Inside your ecommerce.yml configuration, active the ogone provider and select either sandbox or live environment.  

## Implementation

Somewhere in your checkout controller you will need to create the payment configuration for the `initPayment()`
method of the provider:

```php
<?php
    $url = 'https://'. $_SERVER["HTTP_HOST"] . "/en/checkout/confirm?state=";
    $paymentConfig = [                   
                    'language'            => "en",
                    'orderIdent'          => $paymentInfo->getInternalPaymentId(),                   
                    'customerStatement'   => $paymentMessage,
                    'successUrl'          => "https://my-server-name.com/shop/payment/confirm?provider=ogone&state=success",
                    'cancelUrl'           => "https://my-server-name.com/shop/payment/confirm?provider=ogone&state=cancel",
                    'errorUrl'            => "https://my-server-name.com/shop/payment/confirm?provider=ogone&state=error",
                    'paymentInfo'         => $order->getPaymentInfo()->getItems()[0]
                ];
```

You must configure the callback URLs within the OGone backend so that these are called server-by-server.

You can pass additional parameters in the configuration based on the OGone documentation. For instance the color and 
the appearance of th Ogone UI can be controlled, and additional customer data may be passed.