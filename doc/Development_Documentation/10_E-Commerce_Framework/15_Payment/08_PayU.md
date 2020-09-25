# PayU

* [Documentation](http://developers.payu.com/en/restapi.html)

*Configuration* 
```yaml
pimcore_ecommerce_framework:
    payment_manager:
        providers:
            payment.method.payu:
                provider_id: Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\PayU
                profile: 'sandbox'
                profiles:
                sandbox:
                    pos_id: '1234'
                    md5_key: 'c077211eecaf832644edc5a564a68015'
                    oauth_client_id: '1234'
                    oauth_client_secret: '0c68dfa4f61b65fa534b48d95e1c9d91'
```

*usage sample* 
```php
<?php
$config = [
    'extOrderId'  => $paymentId,
    'notifyUrl'   => $baseUrl . $this->router->generate('payment_payu_status'),
    'customerIp'  => $clientIp,
    'description' => 'My order',
    'continueUrl' => $baseUrl . $this->router->generate('payment_payu_continue'),
    'order'       => $order
];

$payment->initPayment($price, $config);
```

