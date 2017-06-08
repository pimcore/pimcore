# Cart Manager

The Cart Manager is responsible for all aspects concerning carts and can manage multiple carts. 
Important to know is, that in the E-Commerce Framework every user specific product collection is a cart. No matter 
how it is called (cart, wish list, compare list, ...), all these product collections need the same base 
functionality. Therefore all different product collections are carts with a specific name.
 

## Working with Carts

#### Creating carts
```php
<?php
$manager = Factory::getInstance()->getCartManager();
$cartId = $manager->createCart(array('name' => $cartName));
$cart = $manager->getCart( $cartId );
```

#### Adding and removing products
```php
<?php
$manager = Factory::getInstance()->getCartManager();
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
$manager = Factory::getInstance()->getCartManager();
$cart = $manager->getCartByName($cartName);

if (count($cart->getItems()) > 0) {
  foreach ($cart->getItems() as $item) {
    $product = $item->getProduct();
 
    //item key
    $cartItemId = $item->getItemKey();
 
    //item amount
    $amount = $item->getCount();
 
    //price info and price
    $priceInfo = $item->getPriceInfo();
    $price = $item->getPrice(); 
  }
}
```


## Price Calculation in Carts
Each cart has a `CartPriceCalculator` (configuration see below) who is responsible for calculating total sums of the 
cart. The `CartPriceCalculator` sums up all product prices to a sub total, adds (or subtracts) so called price 
modificators like shipping costs, cart discounts, etc. and then calculates a grand total. 

See sample below for how to get the different sums: 
```php
<?php
// delivers sum without any price modifications
$subTotal = $cart->getPriceCalculator()->getSubTotal();
 
// iterates through all price modifications
foreach ($cart->getPriceCalculator()->getPriceModifications() as $name => $modification) {
    // $name is the label of a modification
    // $modification is a OnlineShop_Framework_IModificatedPrice
}
 
// delivers sum including all price modifications
$grandTotal = $cart->getPriceCalculator()->getGrandTotal();
``` 


## Configuration of Cart Manager

The configuration takes place in the [EcommerceFrameworkConfig.php](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/Resources/install/EcommerceFrameworkConfig_sample.php#L13-L13): 
```php
 /* general settings for cart manager */
 'cartmanager' => [
     'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\MultiCartManager',
     'config' => [
         /* default cart implementation that is used */
         'cart' => [
             'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\Cart',
             'guest' => [
                 'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\SessionCart'
             ]
         ],
         /* default price calculator for cart */
         'pricecalculator' => [
             'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\CartPriceCalculator',
             'config' => [
                 /* price modificators for cart, e.g. for shipping-cost, special discounts, ... */
                 'modificators' => [
                     'shipping' => [
                         'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\CartPriceModificator\\Shipping',
                         'config' => [
                             'charge' => '5.90'
                         ]
                     ]
                 ]
             ]
         ],
     ]
 ],
```


Following elements are configured: 
* **Implementation of the cart manager**: The cart manager is the basic entry point for working with carts. It is 
  responsible for all interactions with different carts and provides functionality as creating carts, 
  adding/removing products and also creates the corresponding price calculator. 
* **Implementation of the cart**
* **Implementation of the price calculator**: The price calculator is a framework for calculation and modification
  (shipping costs, discounts, ...) of prices on cart level. Each modification is implemented in a 
  [`ICartPriceModificator` class](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/CartManager/CartPriceModificator/ICartPriceModificator.php). 
  See [Shipping](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/CartManager/CartPriceModificator/Shipping.php)
  or [Discount](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/CartManager/CartPriceModificator/Discount.php)
  for examples. This should be self speaking. 


## Checkout Tenants for Carts
The E-Commerce Framework has the concept of so called Checkout Tenants which allow different cart manager and 
checkout manager configurations based on a currently active checkout tenant.
 
The current checkout tenant is set in the framework environment as follows: 

```php
<?php
$environment = Factory::getInstance()->getEnvironment();
$environment->setCurrentCheckoutTenant('default');
$environment->save();

$environment->setCurrentCheckoutTenant('noShipping');
$environment->save();
```

Once set, the cart manager uses all specific settings of the currently active checkout tenant which are configured
in the [EcommerceFrameworkConfig.php](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/Resources/install/EcommerceFrameworkConfig_sample.php#L41-L41)
as follows: 

```php
 /* general settings for cart manager */
 'cartmanager' => [
     'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\MultiCartManager',
     'config' => [
         /* default cart implementation that is used */
         'cart' => [...],
         /* default price calculator for cart */
         'pricecalculator' => [...],
         
         /*  special configuration for specific checkout tenants
             - for not specified elements the default configuration is used as fallback
             - active tenant is set at \Pimcore\Bundle\EcommerceFrameworkBundle\IEnvironment::setCurrentCheckoutTenant() */
         'tenants' => [
             'noShipping' => [
                 'pricecalculator' => [
                     'class' => '\\Pimcore\\Bundle\\EcommerceFrameworkBundle\\CartManager\\CartPriceCalculator',
                     'config' => [
                         'modificators' => []
                     ]
                 ]
             ]
             /* you also can use external files for additional configuration */
             /* "expensiveShipping" =>[ "file" => "\\eommerce\\cartmanager-expensiveShipping.php ] */
         ],

     ]
 ],
```

See also [E-Commerce Demo](https://github.com/pimcore/demo-ecommerce/blob/master/app/config/pimcore/EcommerceFrameworkConfig.php#L33) for some examples.  
