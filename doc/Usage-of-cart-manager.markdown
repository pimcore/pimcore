## 1 - Cart Manager configuration

> Basically, every user specific product collection is a cart. No matter how it is called (cart, wish list, compare list, ...), all these product collections need the same base functionality. Therefore all different product collections are carts with a specific name. 

The configuration takes place in the OnlineShopConfig.xml
```xml
<!-- general settings for cart manager -->
<cartmanager class="OnlineShop_Framework_Impl_MultiCartManager">
    <config>
        <!-- default cart implementation that is used -->
        <cart class="OnlineShop_Framework_Impl_Cart">
            <!--
                cart implementation that is used when system is in guest-checkout mode
                -> OnlineShop_Framework_IEnvironment::getUseGuestCart()
            -->
            <guest class="OnlineShop_Framework_Impl_SessionCart"/>
        </cart>

        <!-- default price calculator for cart -->
        <pricecalculator class="OnlineShop_Framework_Impl_CartPriceCalculator">
            <config>
                <!-- price modificators for cart, e.g. for shipping-cost, special discounts, ... -->
                <modificators>
                    <shipping class="OnlineShop_Framework_Impl_CartPriceModificator_Shipping">
                        <config charge="5.90"/>
                    </shipping>
                </modificators>
            </config>
        </pricecalculator>

        <!--
            special configuration for specific checkout tenants
            - for not specified elements the default configuration is used as fallback 
            - active tenant is set at OnlineShop_Framework_IEnvironment::setCurrentCheckoutTenant()
        -->
        <tenants>
            <noShipping>
                <pricecalculator class="OnlineShop_Framework_Impl_CartPriceCalculator">
                    <config>
                        <modificators>
                        </modificators>
                    </config>
                </pricecalculator>
            </noShipping>

            <expensiveShipping file="/website/var/plugins/OnlineShopConfig/cartmanager-expensiveShipping.xml" />
        </tenants>

    </config>
</cartmanager>
```

Following elements are configured: 
* **Implementation of the cart manager**: The cart manager is the basic entry point for working with carts. It is responsible for all interactions with different carts and provides functionality as creating carts, adding/removing products and also creates the corresponding price calculator. 
* **Implementation of the cart**
* **Implementation of the price calculator**: The price calculator is a framework for calculation and modification (shipping costs, discounts, ...) of prices on cart level. Each modification is implemented in a price modificator class. 


## 2 - Working with carts
#### Creating carts
```php
<?php
$manager = OnlineShop_Framework_Factory::getInstance()->getCartManager();
$cartId = $manager->createCart(array('name' => $cartName));
$cart = $manager->getCart( $cartId );
```

#### Adding and removing products
```php
<?php
$manager = OnlineShop_Framework_Factory::getInstance()->getCartManager();
$cart = $manager->getCartByName($cartName);

$id = $cart->addItem( $product, $amount );
$cart->save();

$cart->removeItem( $id );
$cart->save();

//alternative way
$cartItemId = $manager->addToCart( $product, $amount, $cart->getId() );
//save is done automatically 

$manager->removeFromCart($cartItemId, $cartId);
//save is done automatically 
```

#### List products of cart
```php
<?php
$manager = OnlineShop_Framework_Factory::getInstance()->getCartManager();
$cart = $manager->getCartByName($cartName);

if (count($cart->getItems()) > 0) {
  foreach ($cart->getItems() as $item) {
    $product = $item->getProduct();
 
    //item key
    $cartItemId = $item->getItemKey();
 
    //item amount
    $amount = $item->getCount();
 
    //price info
    $priceInfo = $item->getPriceInfo();
    $price = $item->getPrice(); 
  }
}
```



## 3 - Price calculation in carts
```php
<?php
// delivers sum without any price modifications
$subTotal = $cart->getPriceCalculator()->getSubTotal();
 
// iterates through all price modifications
foreach ($cart->getPriceCalculator()->getPriceModifications() as $name => $modification) {
    // $name is a label for modification
    // $modification is a OnlineShop_Framework_IModificatedPrice
}
 
// delivers sum including all price modifications
$grandTotal = $cart->getPriceCalculator()->getGrandTotal();
```

## 4 - Checkout tenants for carts
The e-commerce framework has the concept of checkout tenants which allow different cart manager and checkout manager configurations based on a currently active checkout tenant. 
The current checkout tenant is set in the framework environment as follows. Once set, the cart manager uses all specific settings of the currently active checkout tenant. 

```php
<?php
$environment = OnlineShop_Framework_Factory::getInstance()->getEnvironment();
$environment->setCurrentCheckoutTenant('default');
$environment->save();

$environment->setCurrentCheckoutTenant('noShipping');
$environment->save();
```
