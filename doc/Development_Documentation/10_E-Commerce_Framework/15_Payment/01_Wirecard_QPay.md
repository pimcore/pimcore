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