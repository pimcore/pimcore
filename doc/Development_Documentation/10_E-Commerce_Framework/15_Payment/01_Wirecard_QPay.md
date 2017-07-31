# Wirecard QPay

* [Documentation](https://integration.wirecard.at/doku.php)
* [Day-End clearing](https://www.qenta.at/qpc/faq/faq.php#8)

> For testing use "sofortüberweisung".

> Dependent on Wirecard account settings, its possible to make a day end clearing on all open (authorised) payments. 
> If this option is disabled, you have to do the clearning by your own (->executeDebit).

```php
<?php
$url = 'http://'. $_SERVER["HTTP_HOST"] . "/en/checkout/payment-status?mode=";
$config = [
    'language' => $language
    , 'successURL' => $url . 'success'
    , 'cancelURL' => $url . 'cancel'
    , 'failureURL' => $url . 'failure'
    , 'serviceURL' => $url . 'service'
    , 'confirmURL' => $urlToServerSideConfirmation
    , 'orderDescription' => 'Meine Bestellung bei pimcore.org'
    , 'imageURL' => 'http://'. $_SERVER["HTTP_HOST"] . '/static/images/logo-white.png'
    , 'orderIdent' => $paymentInformation->getInternalPaymentId()
];
```

If additional parameters should be allowed for initializing the payment, 
 they can be configured named in optional_payment_properties section in 
 payment provider configuration. 

*Configuration sample* 
```yml 
sandbox:
    secret: B8AKTPWBRMNBV455FG6M2DANE99WU2
    customer: D200001
    toolkit_password: jcv45z
    
    # define optional properties which can be used in initPayment (see Wirecard documentation)
    optional_payment_properties:
        - paymentType
        - financialInstitution

    # set hash algorithm to HMAC-SHA512
    hashAlgorithm: 
        hmac_sha512
```

*usage sample* 
```php 
<?php
$payment->initPayment($price, [
    ...,
    'paymentType' => 'CCARD',
    'financialInstitution' => 'Visa'
]);
```

