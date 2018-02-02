# PayPal

* [Documentation](https://developer.paypal.com/docs/classic/api/)
* [Sandbox](https://developer.paypal.com/webapps/developer/docs/classic/lifecycle/ug_sandbox/)

```php
<?php
$url = 'http://'. $_SERVER["HTTP_HOST"] . "/en/checkout/";
$config = [
    'ReturnURL' => $url . 'payment-status?mode=success&internal_id=' . base64_encode($paymentInformation->getInternalPaymentId())
    , 'CancelURL' => $url . 'payment?error=cancel'
    , 'OrderDescription' => 'My Order at pimcore.org'
    , 'cpp-header-image' => '111b25'
    , 'cpp-header-border-color' => '111b25'
    , 'cpp-payflow-color' => 'f5f5f5'
    , 'cpp-cart-border-color' => 'f5f5f5'
    , 'cpp-logo-image' => 'http://'. $_SERVER["HTTP_HOST"] . '/static/images/logo_paypal.png'
];
```