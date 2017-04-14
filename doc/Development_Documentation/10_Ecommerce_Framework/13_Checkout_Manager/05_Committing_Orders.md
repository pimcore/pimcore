# Committing Orders

After all checkout steps are completed, the order can be committed. If no payment is involved, this is done as follows.
If payment is involved, have a look at the [Payment Integration docs](./07_Integrating_Payment.md).

```php
<?php
$manager = Factory::getInstance()->getCheckoutManager($cart);
$order = $manager->commitOrder();
```

While committing the order, the checkout manager delegates it to the specified commit order processor implementation, 
which needs to implement `\Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICommitOrderProcessor`.
 
This is the place where all functionality for committing the order (e.g. sending orders to erp systems, sending order 
confirmation mails, ...) is located. 

The default implementation `\Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessor` provides 
basic functionality like creating a Pimcore order object and sending an order confirmation mail.

Order creation it self is delegated to the `\Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\IOrderManager`.
 
 
## Typically needed Custom Extensions

In simple use cases a project specific implementation needs 

* ...to extend `\Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManager` and overwrite the method `applyCustomCheckoutDataToOrder` 
  to add additional custom fields to the order object and 
* ...to extend `\Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CommitOrderProcessor` and overwrite the method 
  `processOrder` where website specific functionality is integrated (sending orders to erp systems, ...).

See following examples for details. 
 
### Order Manager
If additional information needs to be stored into the order, the OrderManager has to be extended. For more Information
concerning the [OrderManager](../17_Order_Manager/README.md) see the [documentation](../17_Order_Manager/README.md). 
 
A simple implementation of `AppBundle\Ecommerce\Order\OrderManager` could look like:

```php
<?php
class OrderManager extends \Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderManager {

    /**
     * @param ICart $cart
     * @param AbstractOrder $order
     * @return AbstractOrder
     * @throws InvalidConfigException
     */
    public function applyCustomCheckoutDataToOrder(ICart $cart, AbstractOrder $order)
    {
        $order = parent::applyCustomCheckoutDataToOrder($cart, $order);

        /* @var AbstractOrder $order*/

        $checkout = Factory::getInstance()->getCheckoutManager( $cart );
        $deliveryAddress = $checkout->getCheckoutStep('deliveryaddress') ? $checkout->getCheckoutStep('deliveryaddress')->getData() : null;
        $confirm = $checkout->getCheckoutStep('confirm') ? $checkout->getCheckoutStep('confirm')->getData() : null;


        if($deliveryAddress) {

            //inserting delivery and billing address from checkout step delivery

            $order->setCustomerFirstname( $deliveryAddress->firstname );
            $order->setCustomerLastname( $deliveryAddress->lastname );
            $order->setCustomerCompany( $deliveryAddress->company );
            $order->setCustomerStreet( $deliveryAddress->address );
            $order->setCustomerZip( $deliveryAddress->zip );
            $order->setCustomerCity( $deliveryAddress->city );
            $order->setCustomerCountry( $deliveryAddress->country );
            $order->setCustomerEmail( $deliveryAddress->email );

            $order->setDeliveryFirstname( $deliveryAddress->firstname );
            $order->setDeliveryLastname( $deliveryAddress->lastname );
            $order->setDeliveryCompany( $deliveryAddress->company );
            $order->setDeliveryStreet( $deliveryAddress->address );
            $order->setDeliveryZip( $deliveryAddress->zip );
            $order->setDeliveryCity( $deliveryAddress->city );
            $order->setDeliveryCountry( $deliveryAddress->country );
        } else if($confirm) {

            //in quick checkout - only get email-adress from confirm step
            $order->setCustomerEmail( $confirm );

        }

        return $order;
    }

}
```

### Commit Order Processor
A simple implementation of `Website_OnlineShop_Order_Processor` could look like: 

```php
<?php
class Processor extends CommitOrderProcessor {
 
   protected function processOrder(AbstractOrder $order) {
      //send order to ERP-System
      try {
          $connector = ERPConnector::getInstance();
          $erpOrderNumber = $connector->sendOrder($order);
          $order->setOrderNumber($erpOrderNumber);
      } catch(Exception $e) {
          Logger::error($e->getMessage()); 
          throw $e;
      }
   }
}
```
 
If needed, further methods can be overwritten. E.g. `sendConfirmationMail` if special e-mails should be sent to 
specific persons.

After commit order was successful, the user can be directed to a success-page. 
