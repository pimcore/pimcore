# Datatrans

* [Documentation](https://www.datatrans.ch/showcase/authorisation/payment-method-selection-on-merchant-website)
* [Test card numbers](https://www.datatrans.ch/showcase/test-cc-numbers)

> It's possible to make a authorisation and clearing in one step. Default behavior is authorisation only. 
> For automatic clearing set the option "reqtype" to "CAA"

```php
<?php
$url = 'http://'. $_SERVER["HTTP_HOST"] . "/en/checkout/payment-status?mode=";
$config = [
    // checkout config
    'language' => $language
    , 'refno' => $paymentInformation->getInternalPaymentId()
    , 'useAlias' => true
    , 'reqtype' => 'CAA'    // Authorisation and settlement

    // system
    , 'successUrl' => $url . 'success'
    , 'errorUrl' => $url . 'error'
    , 'cancelUrl' => $url . 'cancel'
];
```