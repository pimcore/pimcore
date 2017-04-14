# Klarna

* [Documentation](https://www.developers.klarna.com/en/de/kco-v2/klarna-checkout-overview-v1)
* [Test card numbers](https://www.developers.klarna.com/en/de/kco-v2/test-credentials)

Test E-Mail Accounts
- klarna@green.com -> Open Invoice without selection of payment method
- klarna@yellow.com -> selection of payment methods: open invoice + credit cart
- klarna@red.com -> only credit card

```php
<?php
$url = $this->view->serverUrl();
$config = [
    'purchase_country' => 'AT'
    , 'merchant_reference' => [
        'orderid2' => $paymentInfo->getInternalPaymentId()
    ]
    , 'locale' => 'de-at'
    , 'merchant' => [
        'back_to_store_uri' => $url(['action' => 'payment'])
        , 'terms_uri' => $this->view->serverUrl() . '/agb'
        , 'checkout_uri' => $url(['action' => 'payment']) . '?klarna_order={checkout.order.uri}'
        , 'confirmation_uri' => $url(['action' => 'confirm'], 'payment') . '?klarna_order={checkout.order.uri}'
        , 'push_uri' => $url(['action' => 'confirm'], 'payment') . '?klarna_order={checkout.order.uri}'
        , 'validation_uri' => $this->view->serverUrl() . '/<LINK TO VALIDATE ORDER>?klarna_order={checkout.order.uri}'
    ]
    , 'options' => [
        'color_button' => '#557F0D'
        , 'color_button_text' => '#FFFFFF'
        , 'color_checkbox' => '#FF0000'
        , 'color_checkbox_checkmark' => '#FF0000'
        , 'color_header' => '#EA5B0C'
        , 'color_link' => '#FF0000'
        , 'allow_separate_shipping_address' => true
    ]
];
```
