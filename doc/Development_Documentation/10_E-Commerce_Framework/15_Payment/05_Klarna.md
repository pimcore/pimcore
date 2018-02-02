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

## The cart items 
The Klarna provider implementation has currently no fallback to the default checkout manager to extract the cart items. So you have to pass them in the config array ```$config['cart']['items'] ```. 
See under __"1. Add the cart items"__ https://developers.klarna.com/en/at/kco-v2/checkout/2-embed-the-checkout 

Example implementation:

```php
    $items = [];

    foreach ( $cart->getItems() as $cartItem){

            $item = [];
            $item['reference'] = $cartItem->getProduct()->getOSProductNumber(); // a unique reference for this product / variant
            $item['name'] = $cartItem->getProduct()->getOSName(); 
            $item['quantity'] = (int) $cartItem->getCount();

            $unitPrice = $cartItem->getProduct()->getOSPrice(); // the price for 1 piece
            $item['unit_price'] =  (int) $unitPrice->getAmount()->asRawValue() / 100; // format to integer
            
            $item['discount_rate'] = 0; // mostly implemented via an cart modificator
            $item['tax_rate'] = 0; // can be null, if you don't need tax calculation in the Klarna checkout
            $items[]  = $item; // push it to the items array
     }
     
     $config['cart']['items'] = $items;
```

Most important is the formatting of the prices, they have to be integers. For example: ``` 20.00 € ``` has to become ``` 2000```. Klarna only accepts integers because of avoiding rounding mistakes. 

Here is a list for all accepted item fields: https://developers.klarna.com/en/at/kco-v2/checkout-api#cart-item-object-properties 













