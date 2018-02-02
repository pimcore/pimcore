# Wirecard seamless

> For testing credit cards use card-nr. 9500000000000001.

## Configuration

```php
<?php
$url = 'http://'. $_SERVER["HTTP_HOST"] . "/en/payment/complete?state=";

 // wirecard seamless
$config = [
    'view' => $this->view,
    'orderIdent' => $paymentInformation->getInternalPaymentId()
];
```
After selection of the payment type you can then build your redirect url by doing:

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
    "confirmURL" => 'http://' . $_SERVER["HTTP_HOST"] . $this->view->url(['action' => 'confirm-payment'], 'action', true),
    "paymentInfo" => $paymentInformation,
    "paymentType" => $this->getParam('paymentType'),
    "cart" => $this->getCart(),
    "orderDescription" => $orderNumber,
    "orderReference" => $orderNumber];

return $this->json(['url' => $payment->getInitPaymentRedirectUrl($config)]);
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

For more information also have a look at the sample implementation at the [E-Commerce Demo](https://github.com/pimcore/demo-ecommerce).
