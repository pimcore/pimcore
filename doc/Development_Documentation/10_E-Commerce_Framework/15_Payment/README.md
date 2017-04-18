# Payment Manager

The Payment Manager is responsible for all aspects concerning payment. The main aspect is the implementation
of different Payment Provider to integrate them into the framework. 

## Basic workflow
   1. [SHOP] Init payment provider (`$payment->initPayment()`).
   2. [SHOP] User click pay button and is redirected to the payment provider page.
   3. [PAYMENT PROVIDER] User fill up payment information and is redirected back to the shop.
   4. [SHOP] Check if the payment is authorised (`$payment->handleResponse()`). At this step the order can be committed.
   5. [SHOP] Clearing payment if its required (`$payment->executeDebit()`)
   
For more information about integrating Payment into checkout processes see 
[Integrating Payment Docs](../13_Checkout_Manager/07_Integrating_Payment.md). 


## Configuration

Configuration of Payment Manager takes place in [EcommerceFrameworkConfig.php](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/install/EcommerceFrameworkConfig_sample.php#L127-L127). 

```php
'paymentmanager' => [
    'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\PaymentManager',
    'statusClass' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\Status',
    'config' => [
        'provider' => [
            [
                'name' => 'datatrans',
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\Payment\\Datatrans',
                'mode' => 'sandbox',
                'config' => [
                    'sandbox' => [
                        'merchantId' => '1000011011',
                        'sign' => '30916165706580013',
                        'digitalSignature' => '0'
                    ],
                    'live' => [ ... ]
                ]
            ],
            [
                /* https://integration.wirecard.at/doku.php/wcp:integration */
                /* https://integration.wirecard.at/doku.php/demo:demo_data */
                'name' => 'qpay',
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\Payment\\QPay',
                'mode' => 'sandbox',
                'config' => [
                    'sandbox' => [
                        'secret' => 'B8AKTPWBRMNBV455FG6M2DANE99WU2',
                        'customer' => 'D200001',
                        'toolkitPassword' => 'jcv45z',
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
                    'test' => [
                        'secret' => 'CHCSH7UGHVVX2P7EHDHSY4T2S4CGYK4QBE4M5YUUG2ND5BEZWNRZW5EJYVJQ',
                        'customer' => 'D200411',
                        'toolkitPassword' => '2g4fq2m'
                    ],
                    'live' => [ ... ]
                ]
            ],
            [
                'name' => 'paypal',
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\Payment\\PayPal',
                'mode' => 'sandbox',
                'config' => [
                    'sandbox' => [
                        'api_username' => 'paypal-facilitator_api1.i-2xdream.de',
                        'api_password' => '1375366858',
                        'api_signature' => 'AT2PJj7VTo5Rt.wM6enrwOFBoD1fACBe1RbAEMsSshWFRhpvjAuPR8wD'
                    ],
                    'live' => [ ... ]
                ]
            ],
            [
                'name' => 'seamless',
                'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\PaymentManager\\Payment\\WirecardSeamless',
                'mode' => 'sandbox',
                'partial' => 'PaymentSeamless/wirecard-seamless/payment-method-selection.html.php',
                'js' => '/website/static/js/payment/wirecard-seamless/frontend.js',
                'config' => [
                    'sandbox' => [
                        'customerId' => 'D200001',
                        'shopId' => 'qmore',
                        'secret' => 'B8AKTPWBRMNBV455FG6M2DANE99WU2',
                        'password' => 'jcv45z',
                        'iframeCssUrl' => '/website/static/css/payment-iframe.css?elementsclientauth=disabled',
                        'paymentMethods' => [
                            'PREPAYMENT' => [
                                'icon' => '/website/static/img/wirecard-seamless/prepayment.png',
                                'partial' => 'PaymentSeamless/wirecard-seamless/payment-method/prepayment.html.php'
                            ],
                            'CCARD' => [
                                'icon' => '/website/static/img/wirecard-seamless/ccard.png',
                                'partial' => 'PaymentSeamless/wirecard-seamless/payment-method/ccard.html.php'
                            ],
                            'PAYPAL' => [
                                'icon' => '/website/static/img/wirecard-seamless/paypal.png'
                            ],
                            'SOFORTUEBERWEISUNG' => [
                                'icon' => '/website/static/img/wirecard-seamless/sue.png'
                            ],
                            'INVOICE' => [
                                'icon' => '/website/static/img/wirecard-seamless/payolution.png',
                                'partial' => 'PaymentSeamless/wirecard-seamless/payment-method/invoice.html.php'
                            ]
                        ]
                    ],
                    'live' => [ ... ]
                    ]
                ]
            ]
        ]
    ]
]
```

## Payment Providers
Currently following Payment Providers are integrated into the framework: 

- [Wirecard QPay](./01_Wirecard_QPay.md)
- [Wirecard Seamless](./02_Wirecard_Seamless.md)
- [Datatrans](./03_Datatrans.md)
- [PayPal](./04_PayPal.md)
- [Klarna](./05_Klarna.md)


## Further Payment Aspects
- [Recurring Payments](10_Recurring_Payments.md)