# Recurring Payment
  
Following sample shows how to implement recurring payments:   
  
### CheckoutController.php
```php
<?php
// commit payment
$paymentInfo = $payment->handleResponse( $this->getAllParams() );
$order = $this->checkoutManager->commitOrderPayment( $paymentInfo );

// save payment provider
$orderAgent = Factory::getInstance()->getOrderManager()->createOrderAgent($order);
$orderAgent->setPaymentProvider( $payment );

```
  
### cron.php
```php
<?php

// init
$order = OnlineShopOrder::getById( $this->getParam('id') );
$orderAgent = Factory::getInstance()->getOrderManager()->createOrderAgent($order);


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
