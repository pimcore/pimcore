## 1 - Payment Manager configuration

### Basic workflow
1. [SHOP] init payment provider (```$payment->initPayment```)
2. [SHOP] user klick pay button and is redirected to the payment provider page
3. [PAYMENT PROVIDER] user fill up payment infos and is redirected back to the shop
4. [SHOP] check if the payment is authorised (```$payment->handleResponse```). At this step the order can be commited.
5. [SHOP] clearing payment if its required (```$payment->executeDebit```)


### Available Payment Provider
* Wirecard (qpay)
* Datatrans (datatrans)
* PayPal (paypal)


The configuration takes place in the EcommerceFrameworkConfig.php
```php
<!-- general settings for cart manager -->
"paymentmanager" => [
            "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\PaymentManager",
            "statusClass" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\Status",
            "config" => [
                "provider" => [
                    [
                        "name" => "datatrans",
                        "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\Payment\\Datatrans",
                        "mode" => "sandbox",
                        "config" => [
                            "sandbox" => [
                                "merchantId" => "1000011011",
                                "sign" => "30916165706580013",
                                "digitalSignature" => "0"
                            ],
                            "live" => [
                                "merchantId" => "",
                                "sign" => "",
                                "digitalSignature" => "0"
                            ]
                        ]
                    ],
                    [
                        /* https://integration.wirecard.at/doku.php/wcp:integration */
                        /* https://integration.wirecard.at/doku.php/demo:demo_data */
                        "name" => "qpay",
                        "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\Payment\\QPay",
                        "mode" => "sandbox",
                        "config" => [
                            "sandbox" => [
                                "secret" => "B8AKTPWBRMNBV455FG6M2DANE99WU2",
                                "customer" => "D200001",
                                "toolkitPassword" => "jcv45z",
                                /* define optional properties which can be used in initPayment (see Wirecard documentation)
                                https://git.io/v2ty1 */
                                /* "optionalPaymentProerties" => [
                                    "property" => [
                                        "paymentType",
                                        "financialInstitution"
                                    ]
                                ], */
                                /*  set hash algorithm to HMAC-SHA512
                                https://git.io/v2tyV */
                                /* ["hashAlgorithm" => "hmac_sha512"] */
                            ],
                            "test" => [
                                "secret" => "CHCSH7UGHVVX2P7EHDHSY4T2S4CGYK4QBE4M5YUUG2ND5BEZWNRZW5EJYVJQ",
                                "customer" => "D200411",
                                "toolkitPassword" => "2g4fq2m"
                            ],
                            "live" => [
                                "secret" => "",
                                "customer" => "",
                                "toolkitPassword" => ""
                            ]
                        ]
                    ],
                    [
                        "name" => "paypal",
                        "class" => "\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\Payment\\PayPal",
                        "mode" => "sandbox",
                        "config" => [
                            "sandbox" => [
                                "api_username" => "paypal-facilitator_api1.i-2xdream.de",
                                "api_password" => "1375366858",
                                "api_signature" => "AT2PJj7VTo5Rt.wM6enrwOFBoD1fACBe1RbAEMsSshWFRhpvjAuPR8wD"
                            ],
                            "live" => [
                                "api_username" => "",
                                "api_password" => "",
                                "api_signature" => ""
                            ]
                        ]
                    ]
                ]
            ]
        ],
```

## 2 - Provider configuration

#### Wirecard QPay

* [Documentation](https://integration.wirecard.at/doku.php)
* [Day-End clearing](https://www.qenta.at/qpc/faq/faq.php#8)

> For testing use "sofortüberweisung".

> Dependent on Wirecard account settings, its possible to make a day end clearing on all open (authorised) payments. If this option is disabled, you have to do the clearning by your own (->executeDebit).

```php
<?php
$url = 'http://'. $_SERVER["HTTP_HOST"] . "/en/checkout/payment-status?mode=";
$config = [
    'language' => 'en'
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

#### Datatrans

* [Documentation](https://www.datatrans.ch/showcase/authorisation/payment-method-selection-on-merchant-website)
* [Test card numbers](https://www.datatrans.ch/showcase/test-cc-numbers)

> It's possible to make a authorisation and clearing in one step. Default behavior is authorisation only. For automatic clearing set the option "reqtype" to "CAA"

```php
<?php
$url = 'http://'. $_SERVER["HTTP_HOST"] . "/en/checkout/payment-status?mode=";
$config = [
    // checkout config
    'language' => 'en'
    , 'refno' => $paymentInformation->getInternalPaymentId()
    , 'useAlias' => true
    , 'reqtype' => 'CAA'    // Authorisation and settlement

    // system
    , 'successUrl' => $url . 'success'
    , 'errorUrl' => $url . 'error'
    , 'cancelUrl' => $url . 'cancel'
];
```


#### PayPal

* [Documentation](https://developer.paypal.com/docs/classic/api/)
* [Sandbox](https://developer.paypal.com/webapps/developer/docs/classic/lifecycle/ug_sandbox/)

```php
<?php
$url = 'http://'. $_SERVER["HTTP_HOST"] . "/en/checkout/";
$config = [
    'ReturnURL' => $url . 'payment-status?mode=success&internal_id=' . base64_encode($paymentInformation->getInternalPaymentId())
    , 'CancelURL' => $url . 'payment?error=cancel'
    , 'OrderDescription' => 'Meine Bestellung bei pimcore.org'
    , 'cpp-header-image' => '111b25'
    , 'cpp-header-border-color' => '111b25'
    , 'cpp-payflow-color' => 'f5f5f5'
    , 'cpp-cart-border-color' => 'f5f5f5'
    , 'cpp-logo-image' => 'http://'. $_SERVER["HTTP_HOST"] . '/static/images/logo_paypal.png'
];
```


#### Klarna

* [Documentation](https://www.developers.klarna.com/en/de/kco-v2/klarna-checkout-overview-v1)
* [Test card numbers](https://www.developers.klarna.com/en/de/kco-v2/test-credentials)

Test E-Mail Account
- klarna@green.com -> offene Rechnung ohne zahlartenabfrage
- klarna@yellow.com -> zahlartenabfrage offene Rechnung + CC
- klarna@red.com -> nur CC

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
                , 'validation_uri' => $this->view->serverUrl() . '/plugin/Expert/checkout/validate?klarna_order={checkout.order.uri}'
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


#### Wirecard seamless

> For testing creditcards use card-nr. 9500000000000001.

##### Configuration

```php
<?php
$url = 'http://'. $_SERVER["HTTP_HOST"] . "/en/payment/complete?state=";

 // wirecard seamless
  $config = [
                'view' => $this->view,
                'orderIdent' => $paymentInformation->getInternalPaymentId()
            ];
```

After selection of the payment-type you can then form your redirect url by doing:

```php
<?php

        $config = [
            "successURL" => 'http://' .$_SERVER["HTTP_HOST"] . $this->view->url(['action' => 'complete', 'id' => base64_encode($paymentInformation->getObject()->getId()),
                'state' => \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\WirecardSeamless::PAYMENT_RETURN_STATE_SUCCESS, 'prefix' => $this->language], 'action', true),
            "failureURL" => 'http://' . $_SERVER["HTTP_HOST"] . $this->view->url(['action' => 'complete', 'id' => base64_encode($paymentInformation->getObject()->getId()),
                    'state' => \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\WirecardSeamless::PAYMENT_RETURN_STATE_FAILURE, 'prefix' => $this->language], 'action', true),
            "cancelURL" => 'http://' . $_SERVER["HTTP_HOST"] . $this->view->url(['action' => 'complete', 'id' => base64_encode($paymentInformation->getObject()->getId()),
                    'state' => \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\WirecardSeamless::PAYMENT_RETURN_STATE_CANCEL, 'prefix' => $this->language], 'action', true),
            "serviceURL" => Pimcore\Tool::getHostUrl(),
            "pendingURL" => 'http://' . $_SERVER["HTTP_HOST"] . $this->view->url(['action' => 'complete', 'id' => base64_encode($paymentInformation->getObject()->getId()),
                    'state' => \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\WirecardSeamless::PAYMENT_RETURN_STATE_PENDING, 'prefix' => $this->language], 'action', true),
            "confirmURL" => 'http://' . $_SERVER["HTTP_HOST"] . $this->view->url(['action' => 'confirm-payment', 'elementsclientauth' => 'disabled'], 'action', true),
            "paymentInfo" => $paymentInformation,
            "paymentType" => $this->getParam('paymentType'),
            "cart" => $this->getCart(),
            "orderDescription" => $orderNumber,
            "orderReference" => $orderNumber];

        $this->_helper->json(['url' => $payment->getInitPaymentRedirectUrl($config)]);

```

In view script of your _completeAction_ you could then handle your response as follows:

```php
<?php
$isCommited = $this->order && $this->order->getOrderState() == \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder::ORDER_STATE_COMMITTED;
$state = $this->getParam('state');
?>

<?php if($isCommited) { ?>

    <!-- redirect to order completed page -->

<? } elseif (in_array($state, [
        \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\WirecardSeamless::PAYMENT_RETURN_STATE_FAILURE,
        \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\WirecardSeamless::PAYMENT_RETURN_STATE_CANCEL
    ])) { ?>

    <!-- output errors and handle failures and cancel  -->
    <!-- give retry possibility -->

<? } elseif ($state == \Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\WirecardSeamless::PAYMENT_RETURN_STATE_PENDING) { ?>
    <!-- handle payment pending state -->
<? } else { ?>
    <!-- payment still running, poll for status updates (ie. refresh page) -->
<? } ?>
```

For more information also have a look at the sample implementation at the [ecommerce demo](https://github.com/pimcore-partner/ecommerce-framework-demo).


## 3 - Recurring payment

CheckoutController.php
```php
<?php
// commit payment
$paymentInfo = $payment->handleResponse( $this->getAllParams() );
$order = $this->checkoutManager->commitOrderPayment( $paymentInfo );

// save payment provider
$orderAgent = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance()->getOrderManager()->createOrderAgent($order);
$orderAgent->setPaymentProvider( $payment );

```

cron.php
```php
<?php

// init
$order = OnlineShopOrder::getById( $this->getParam('id') );
$orderAgent = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance()->getOrderManager()->createOrderAgent($order);


// start payment
$paymentProvider = $orderAgent->getPaymentProvider();
$paymentInfo = $orderAgent->startPayment();


// execute payment
$amount = new \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price( 125.95, $orderAgent->getCurrency() );
$paymentStatus = $paymentProvider->executeDebit( $amount, $paymentInfo->getInternalPaymentId() );


// save payment status
$orderAgent->updatePayment( $paymentStatus );


// check
if($paymentStatus->getStatus() == $paymentStatus::STATUS_CLEARED)
{
    ...
}

```
