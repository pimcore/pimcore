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

Configuration of Payment Manager takes place in the `pimcore_ecommerce_config.payment_manager` config section: 

```yaml
pimcore_ecommerce_config:
    payment_manager:
        # service ID of payment manager implementation - following value is default value an can be omitted
        payment_manager_id: Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\PaymentManager

        # configuration of payment providers, key is name of provider
        providers:
            datatrans:
                # service ID of payment provider implementation
                provider_id: Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\Datatrans

                # active profile - you can define multiple profiles in the section below 
                profile: sandbox

                # available profiles with options - options vary on the provider implementation as the
                profiles:
                    sandbox:
                        merchant_id: 1000011011
                        sign: 30916165706580013
                        use_digital_signature: false
                    live:
                        merchant_id: merchant_id_id
                        sign: sign_id
                        use_digital_signature: false
                        mode: live

            qpay:
                provider_id: Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\QPay
                profile: sandbox
                profiles:
                    sandbox:
                        secret: B8AKTPWBRMNBV455FG6M2DANE99WU2
                        customer: D200001
                        toolkit_password: jcv45z
                        # define optional properties which can be used in initPayment (see Wirecard documentation)
                        optional_payment_properties:
                            - paymentType
                            - financialInstitution

                        # set hash algorithm to HMAC-SHA512
                        hash_algorithm:
                            hmac_sha512

                    live:
                        secret: secret
                        customer: customer

            paypal:
                provider_id: Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\PayPal
                profile: sandbox
                profiles:
                    sandbox:
                        api_username: paypal-facilitator_api1.i-2xdream.de
                        api_password: 1375366858
                        api_signature: AT2PJj7VTo5Rt.wM6enrwOFBoD1fACBe1RbAEMsSshWFRhpvjAuPR8wD
                    live:
                        api_username: username
                        api_password: password
                        api_signature: signature
                        mode: live


            seamless:
                provider_id: Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\WirecardSeamless
                profile: sandbox
                profiles:
                    _defaults:
                        hash_algorithm: hmac_sha512
                        paypal_activate_item_level: true
                        partial: PaymentSeamless/wirecard-seamless/payment-method-selection.html.php
                        js: /static/js/payment/wirecard-seamless/frontend.js
                        iframe_css_url: /static/css/payment-iframe.css?elementsclientauth=disabled
                        payment_methods:
                            PREPAYMENT:
                                icon: /static/img/wirecard-seamless/prepayment.png
                                partial: PaymentSeamless/wirecard-seamless/payment-method/prepayment.html.php
                            CCARD:
                                icon: /static/img/wirecard-seamless/ccard.png
                                partial: PaymentSeamless/wirecard-seamless/payment-method/ccard.html.php
                            PAYPAL:
                                icon: /static/img/wirecard-seamless/paypal.png
                            SOFORTUEBERWEISUNG:
                                icon: /static/img/wirecard-seamless/sue.png
                            INVOICE:
                                icon: /static/img/wirecard-seamless/payolution.png
                                partial: PaymentSeamless/wirecard-seamless/payment-method/invoice.html.php
                    sandbox:
                        customer_id: D200001
                        shop_id: qmore
                        secret: B8AKTPWBRMNBV455FG6M2DANE99WU2
                        password: jcv45z
                    live:
                        customer_id: customer_id
                        shop_id: shop_id
                        secret: secret
                        password: password
            ogone:
                provider_id: Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\OGone
                profile: sandbox
                profiles:
                    sandbox:
                        secret: D343DDFD3434
                        pspid: MyTestAccount
                        mode: sandbox                        
#                       encryptionType: SHA256 or SHA512 (optional)                                              
                    live:
                        secret: D343DDFD3434
                        pspid: MyLiveAccount
                        mode: live                        
#                       encryptionType: SHA256 or SHA512 (optional)
            mpay24:
                provider_id: Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\Mpay24Seamless
                profile: testsystem
                profiles:
                  _defaults:
                      #paypal_activate_item_level: true
                      partial: Shared/Includes/Shop/Payment/paymentMethods.html.php
                      payment_methods:
                          cc:
                          paypal:
                          sofort:
                          invoice:
                  testsystem:
                      merchant_id: 95387
                      password: 7&jcQ%v6RB
                      testSystem: true
                      debugMode: true
                  live:
                      merchant_id: todo
                      password: todo
                      testSystem: false
                      debugMode: false                  
            hobex:
                    provider_id: Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\Hobex                    
                    profile: sandbox
                    profiles:
                        sandbox:
                            entityId: '8a829418530df1d201531299e097175c'
                            authorizationBearer: 'OGE4Mjk0MTg1MzBkZjFkMjAxNTMxMjk5ZTJjMTE3YWF8ZzJnU3BnS2hLUw=='
                            testSystem: true
                            payment_methods:
                                - VISA
                                - MASTER
                                - SOFORTUEBERWEISUNG
                                - SEPA                                                                 
```

The payment provider name will be referenced from the checkout manager configuration and can be used to fetch a specific
provider from the payment manager.

## Payment Providers
Currently following Payment Providers are integrated into the framework: 

- [Wirecard QPay](./01_Wirecard_QPay.md)
- [Wirecard Seamless](./02_Wirecard_Seamless.md)
- [Datatrans](./03_Datatrans.md)
- [PayPal](./04_PayPal.md)
- [Klarna](./05_Klarna.md)
- [OGone](./06_OGone.md)
- [MPay24](./07_MPay24.md)
- [PayU](./08_PayU.md)
- [Heidelpay](./09_Heidelpay.md)
- [Hobex](./11_Hobex.md)


## Further Payment Aspects
- [Recurring Payments](10_Recurring_Payments.md)
