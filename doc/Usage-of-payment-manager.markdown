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


The configuration takes place in the OnlineShopConfig.xml
```xml
<!-- general settings for cart manager -->
<paymentmanager class="\OnlineShop\Framework\PaymentManager\PaymentManager" statusClass="OnlineShop_Framework_Impl_Checkout_Payment_Status">
    <config>
        <provider name="datatrans" class="\OnlineShop\Framework\PaymentManager\Payment\Datatrans" mode="sandbox">
            <config>
                <sandbox>
                    <merchantId>1000011011</merchantId>
                    <sign>30916165706580013</sign>
                </sandbox>

                <elementsSandbox>
                    <!-- elements DataTrans test merchant (Zugangsdaten in PWS) -->
                    <merchantId>1100004971</merchantId>
                    <sign>150820082849579274</sign>
                </elementsSandbox>

                <live>
                    <merchantId></merchantId>
                    <sign></sign>
                </live>
            </config>
        </provider>

        <provider name="qpay" class="\OnlineShop\Framework\PaymentManager\Payment\QPay" mode="sandbox">
            <config>
                <sandbox>
                    <secret>B8AKTPWBRMNBV455FG6M2DANE99WU2</secret>
                    <customer>D200001</customer>
                    <toolkitPassword>jcv45z</toolkitPassword>
                </sandbox>
                <live>
                    <secret></secret>
                    <customer></customer>
                </live>
            </config>
        </provider>

        <provider name="paypal" class="\OnlineShop\Framework\PaymentManager\Payment\PayPal" mode="sandbox">
            <config>
                <sandbox>
                    <api_username>paypal-facilitator_api1.i-2xdream.de</api_username>
                    <api_password>1375366858</api_password>
                    <api_signature>AT2PJj7VTo5Rt.wM6enrwOFBoD1fACBe1RbAEMsSshWFRhpvjAuPR8wD</api_signature>
                </sandbox>
            </config>
        </provider>
    </config>
</paymentmanager>
```

 


## 2 - Provider configuration

#### Wirecard

* [Documentation](https://integration.wirecard.at/doku.php)
* [Day-End clearing](https://www.qenta.at/qpc/faq/faq.php#8)

> For testing use "sofortüberweisung".

> Dependent on Wirecard account settings, its possible to make a day end clearing on all open (authorised) payments. If this option is disabled, you have to do the clearning by your own (->executeDebit).

```php
<?php
$url = 'http://'. $_SERVER["HTTP_HOST"] . "/en/checkout/payment-status?mode=";
$config = [
    'language' => Zend_Registry::get("Zend_Locale")->getLanguage()
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
    'language' => Zend_Registry::get("Zend_Locale")->getLanguage()
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

## 3 - Recurring payment

CheckcoutController.php
```php
<?php
// commit payment
$paymentInfo = $payment->handleResponse( $this->getAllParams() );
$order = $this->checkoutManager->commitOrderPayment( $paymentInfo );

// save payment provider
$orderAgent = \OnlineShop\Framework\Factory::getInstance()->getOrderManager()->createOrderAgent($order);
$orderAgent->setPaymentProvider( $payment );

```

cron.php
```php
<?php

// init
$order = OnlineShopOrder::getById( $this->getParam('id') );
$orderAgent = \OnlineShop\Framework\Factory::getInstance()->getOrderManager()->createOrderAgent($order);


// start payment
$paymentProvider = $orderAgent->getPaymentProvider();
$paymentInfo = $orderAgent->startPayment();


// execute payment
$amount = new \OnlineShop\Framework\PriceSystem\Price( 125.95, $orderAgent->getCurrency() );
$paymentStatus = $paymentProvider->executeDebit( $amount, $paymentInfo->getInternalPaymentId() );


// save payment status
$orderAgent->updatePayment( $paymentStatus );


// check
if($paymentStatus->getStatus() == $paymentStatus::STATUS_CLEARED)
{
    ...
}

```
