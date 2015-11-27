#Update Notices
Please consider following update notices when updating the e-commerce framework.
 
## 0.9.0 - 0.9.1
This version contains some significant refactorings concerning committing orders. These refactorings made some changes necessary 
 that need to be addressed during an update:  

#### OnlineShopConfig.xml
The configuration for `orderstorage` and `parentorderfolder` moved from section `checkoutmanager` to section `ordermanager`. 
For details see [Sample OnlineShopConfig.xml](/config/OnlineShopConfig_sample.xml)

#### Class OnlineShopOrder
The object class OnlineShopOrder needs as additional number field `subTotalPrice`. In addition, lots of fields became not editable by default. 
For details see [Class Defintiion](/install/class_source/class_OnlineShopOrder_export.json)

#### Changes in combination with `IOrderManager`, `ICart`, `ICheckoutManager` and `ICommitOrderProcessor`
 
* `IOrderManager` is now responsible for creating and retrieving order objects. This has the consequence, that adding custom data to 
  an order object from the cart needs to be moved from the `ICommitOrderProcessor` to an extension of the `IOrderManager`. 
  Following methods were added (or moved from other classes): 
    * `setParentOrderFolder()`: added
    * `setOrderClass()`: added
    * `setOrderItemClass()`: added
    * `getOrCreateOrderFromCart()`: added
    * `getOrderFromCart()`: added
    * `protected applyCustomCheckoutDataToOrder()`: use this method for adding custom checkout information to order objects.
    
     For details see [IOrderManager](/lib/OnlineShop/Framework/IOrderManager.php) and [OrderManager](/lib/OnlineShop/Framework/Impl/OrderManager.php).

* `ICart` now has a new method `isCartReadOnly` which returns if cart is read only or not - is not based on the environment entry anymore. 

* `ICheckoutManager` should be the one-stop API for a checkout controller. Following methods/constants were added or removed:
    * `hasActivePayment()`: added, use this to check, if there is an active payment ongoing. If so, the best thing would be to redirect to the payment page. 
    * `cancelStartedOrderPayment()`: added, use this to to cancel payment when user cancels payment (keep in mind, here no request to payment is sent). 
    * `handlePaymentResponseAndCommitOrderPayment()`: added, combines handling payment response and committing order payment. use this in your controller. 
    * The constants `FINISHED`, `CART_READONLY_PREFIX` and `COMMITTED` were removed, use the getter methods instead. The information 
    for these states is now calcualted based on the order object.
    
     For details see [ICheckoutManager](/lib/OnlineShop/Framework/ICheckoutManager.php) and  [CheckoutManager](/lib/OnlineShop/Framework/Impl/CheckoutManager.php). 


* `ICommitOrderProcessor` is the only place for committing orders and now works without cart. This has the consequence, that the cart is not available in the 
  `ICommitOrderProcessor` anymore which might have some impact on custom implementations! As a positive consequence, the `CommitOrderProcessor` can now be 
   instantiated without a `CheckoutManager` and without a cart and therefore can be used for server-by-server payment commits. Following methods have changed:  
    * `getOrCreateOrder()`: and depending methods were moved to `IOrderManager`
    * `createOrderNumber()`: moved to `IOrderManager`
    * `getOrCreateActivePaymentInfo()`: removed
    * `updateOrderPayment()`: removed
    * `committedOrderWithSamePaymentExists()`: added, use this to check, if order with same payment is already committed. 
    * `handlePaymentResponseAndCommitOrderPayment()`: added, basically does the work of handling payment response and commit the order
    * `processOrder` and `sendConfirmationMail` do not have access to the cart anymore.  
    
     For details see [ICommitOrderProcessor](/lib/OnlineShop/Framework/ICommitOrderProcessor.php) and [CommitOrderProcessor](/lib/OnlineShop/Framework/Impl/CommitOrderProcessor.php)
